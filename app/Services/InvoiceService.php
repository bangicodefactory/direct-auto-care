<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\StockMovementType;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
     *   notes, converted_from_quote_id, status], items[]
     *
     * Each item: {description, quantity, unit_price_ht, [product_id]}
     * line_total_ht is computed here; callers must not pass it.
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
                'status'                  => $data['status'] ?? InvoiceStatus::Draft->value,
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

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function insertItems(Invoice $invoice, array $items, User $user): void
    {
        foreach ($items as $item) {
            $quantity  = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price_ht'];

            $invoice->items()->create([
                'product_id'    => $item['product_id'] ?? null,
                'description'   => $item['description'],
                'quantity'      => $quantity,
                'unit_price_ht' => $unitPrice,
                'line_total_ht' => round($quantity * $unitPrice, 2),
            ]);

            // Only invoices (not quotes) decrement stock.
            if (! empty($item['product_id']) && $invoice->type === InvoiceType::Invoice) {
                $this->stock->recordMovement(
                    product: Product::findOrFail((int) $item['product_id']),
                    type: StockMovementType::Out,
                    quantity: (int) round($quantity),
                    reason: null,
                    invoice: $invoice,
                    user: $user,
                );
            }
        }
    }

    private function reverseItemStock(Invoice $invoice, User $user): void
    {
        // Quotes never had stock decremented, nothing to reverse.
        if ($invoice->type !== InvoiceType::Invoice) {
            return;
        }

        $invoice->loadMissing('items');

        foreach ($invoice->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            // Product may have been soft-deleted since the invoice was created.
            $product = Product::find($item->product_id);

            if ($product === null) {
                continue;
            }

            $this->stock->recordMovement(
                product: $product,
                type: StockMovementType::In,
                quantity: (int) round((float) $item->quantity),
                reason: 'Correction facture',
                invoice: $invoice,
                user: $user,
            );
        }
    }
}
