<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\StockMovementType;
use App\Exceptions\ProductUnavailableException;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceNumberingService $numbering,
        private readonly StockService $stock,
    ) {
    }

    /**
     * Create a new invoice (or quote) and its items inside a single transaction.
     *
     * Expected $data keys:
     *   type, customer_id, issue_date, [vehicle_id, due_date, discount, tva_rate,
     *   notes, converted_from_quote_id], items[]
     *
     * Each item: {description, quantity, unit_price_ht, [product_id], [vehicle_id]}
     * line_total_ht is computed here; callers must not pass it.
     * New invoices are always created as Draft; status transitions are managed elsewhere.
     */
    public function create(array $data, User $user): Invoice
    {
        return DB::transaction(function () use ($data, $user): Invoice {
            $type       = InvoiceType::from($data['type']);
            $issueDate  = Carbon::parse($data['issue_date']);
            $fiscalYear = $issueDate->year;

            $invoice = Invoice::create([
                'type'                    => $type->value,
                'number'                  => $this->numbering->next($type, $fiscalYear),
                'customer_id'             => $data['customer_id'],
                'vehicle_id'              => $data['vehicle_id'] ?? null,
                'issue_date'              => $issueDate->toDateString(),
                'due_date'                => isset($data['due_date']) ? Carbon::parse($data['due_date'])->toDateString() : null,
                'fiscal_year'             => $fiscalYear,
                'discount'                => $data['discount'] ?? 0,
                'tva_rate'                => $data['tva_rate'] ?? 20,
                'notes'                   => $data['notes'] ?? null,
                // Always Draft on creation — status transitions are managed separately.
                'status'                  => InvoiceStatus::Draft->value,
                'created_by'              => $user->id,
                'converted_from_quote_id' => $data['converted_from_quote_id'] ?? null,
                // Placeholders — overwritten by recomputeTotals() below.
                'subtotal_ht'             => 0,
                'tva_amount'              => 0,
                'total_ttc'               => 0,
            ]);

            $this->insertItems($invoice, $data['items'] ?? [], $user);
            $invoice->recomputeTotals()->save();

            return $invoice->load('items');
        });
    }

    /**
     * Replace an existing invoice's mutable fields and all its items.
     *
     * type, number, fiscal_year, and created_by are immutable after creation.
     * Stock movements from old items are reversed before new items are applied.
     */
    public function update(Invoice $invoice, array $data, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $user): Invoice {
            // Lines that survive the edit carry their `unit` snapshot back from the
            // form (validated by UpdateInvoiceRequest); new or re-picked product
            // lines omit it and take the product's current unit in insertItems().
            $this->reverseItemStock($invoice, $user);

            $invoice->items()->delete();
            // Clear the stale items cache so recomputeTotals() re-queries.
            $invoice->unsetRelation('items');

            $invoice->update([
                'customer_id' => $data['customer_id'],
                'vehicle_id'  => $data['vehicle_id'] ?? null,
                'issue_date'  => Carbon::parse($data['issue_date'])->toDateString(),
                'due_date'    => isset($data['due_date']) ? Carbon::parse($data['due_date'])->toDateString() : null,
                'discount'    => $data['discount'] ?? 0,
                'tva_rate'    => $data['tva_rate'] ?? 20,
                'notes'       => $data['notes'] ?? null,
            ]);

            $this->insertItems($invoice, $data['items'] ?? [], $user);
            $invoice->recomputeTotals()->save();

            return $invoice->load('items');
        });
    }

    /**
     * Cancel an invoice, returning consumed stock to inventory.
     *
     * Restore + status change share one transaction so the document can never be
     * cancelled without its stock coming back (or vice-versa). The policy blocks
     * cancelling an already-cancelled document, but the status guard makes the
     * restore idempotent regardless of caller.
     */
    public function cancel(Invoice $invoice, User $user): Invoice
    {
        return DB::transaction(function () use ($invoice, $user): Invoice {
            // Re-read under a row lock: the route-bound model's status predates the
            // transaction, and two concurrent cancel/delete requests must not both
            // observe a non-cancelled status and restore stock twice. A concurrent
            // delete that already committed makes findOrFail throw (soft-deleted
            // rows are excluded), aborting this cancel — which is the right outcome.
            $current = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($current->status !== InvoiceStatus::Cancelled) {
                $this->reverseItemStock($invoice, $user, 'Annulation facture');
            }

            $invoice->update(['status' => InvoiceStatus::Cancelled->value]);

            return $invoice;
        });
    }

    /**
     * Soft-delete an invoice, returning consumed stock to inventory.
     *
     * A cancelled facture already had its stock restored at cancellation time, so
     * deleting one must not restore again — that is the cancel→delete guard.
     * (Factures cancelled before this behaviour existed never had stock restored;
     * deleting those still skips the restore — square manually via an adjustment.)
     */
    public function delete(Invoice $invoice, User $user): void
    {
        DB::transaction(function () use ($invoice, $user): void {
            // Same row-locked re-read as cancel(): a cancel that commits first is
            // seen here as Cancelled (restore skipped); a delete that commits first
            // makes findOrFail throw and this request no-ops.
            $current = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($current->status !== InvoiceStatus::Cancelled) {
                $this->reverseItemStock($invoice, $user, 'Suppression facture');
            }

            // Tail numbers are reclaimed so a just-created mistake doesn't burn a
            // fiscal number. The trashed row is renamed to free the slot — the
            // unique index on invoices.number spans soft-deleted rows. Mid-sequence
            // deletions release nothing and simply leave a gap.
            if ($this->numbering->release($current->type, $current->fiscal_year, $current->number)) {
                $invoice->update(['number' => "{$current->number}#{$invoice->id}"]);
            }

            $invoice->delete();
        });
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function insertItems(Invoice $invoice, array $items, User $user): void
    {
        // Pre-load all referenced products in one query to avoid N+1. Trashed
        // products are included so a quote line whose product was deleted since
        // still resolves (for its unit) and fails with a readable message below
        // instead of a bare ModelNotFound.
        $productIds = collect($items)->pluck('product_id')->filter()->unique()->values()->all();
        $products   = Product::withTrashed()->findMany($productIds)->keyBy('id');

        foreach ($items as $item) {
            $quantity  = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price_ht'];
            $productId = ! empty($item['product_id']) ? (int) $item['product_id'] : null;
            $product   = $productId !== null ? $products->get($productId) : null;

            if ($product?->trashed() && $invoice->type === InvoiceType::Invoice) {
                throw new ProductUnavailableException(
                    trans('ui.invoice_product_unavailable', ['name' => $product->name])
                );
            }

            $invoice->items()->create([
                'product_id'    => $productId,
                'service_id'    => $item['service_id'] ?? null,
                'vehicle_id'    => $item['vehicle_id'] ?? null,
                'description'   => $item['description'],
                // Snapshot the stock unit so the line survives later product
                // edits/deletes. A caller-supplied unit (quote conversion) wins.
                'unit'          => $item['unit'] ?? $product?->unit,
                'quantity'      => $quantity,
                'unit_price_ht' => $unitPrice,
                'line_total_ht' => round($quantity * $unitPrice, 2),
            ]);

            // Only invoices (not quotes) decrement stock.
            if ($productId !== null && $invoice->type === InvoiceType::Invoice) {
                $product ?? throw new ModelNotFoundException("Product [{$productId}] not found.");

                // stock_movements.quantity is int — fractional item quantities are rounded.
                $this->stock->recordMovement(
                    product: $product,
                    type: StockMovementType::Out,
                    quantity: (int) round($quantity),
                    reason: null,
                    invoice: $invoice,
                    user: $user,
                );
            }
        }
    }

    private function reverseItemStock(Invoice $invoice, User $user, string $reason = 'Correction facture'): void
    {
        // Quotes never had stock decremented, nothing to reverse.
        if ($invoice->type !== InvoiceType::Invoice) {
            return;
        }

        $invoice->loadMissing('items');

        // Pre-load active products in one query to avoid N+1.
        $productIds = $invoice->items->pluck('product_id')->filter()->unique()->all();
        $products   = Product::findMany($productIds)->keyBy('id');

        foreach ($invoice->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = $products->get((int) $item->product_id);

            if ($product === null) {
                // Product was soft-deleted after the invoice was created.
                // The original Out movement can no longer be reversed — flag for admin review.
                Log::warning('Stock reversal skipped: product soft-deleted after invoicing.', [
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                ]);
                continue;
            }

            // stock_movements.quantity is int — fractional item quantities are rounded.
            $this->stock->recordMovement(
                product: $product,
                type: StockMovementType::In,
                quantity: (int) round((float) $item->quantity),
                reason: $reason,
                invoice: $invoice,
                user: $user,
            );
        }
    }
}
