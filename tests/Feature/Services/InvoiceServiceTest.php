<?php

namespace Tests\Feature\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceNumberingService;
use App\Services\InvoiceService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;
    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service  = new InvoiceService(new InvoiceNumberingService(), new StockService());
        $this->user     = User::factory()->create();
        $this->customer = Customer::factory()->create();
    }

    // =========================================================================
    // create()
    // =========================================================================

    // -------------------------------------------------------------------------
    // Numbering
    // -------------------------------------------------------------------------

    public function test_create_invoice_generates_fac_number(): void
    {
        $invoice = $this->service->create($this->invoiceData(), $this->user);

        $this->assertMatchesRegularExpression('/^FAC-\d{4}-\d{5}$/', $invoice->number);
    }

    public function test_create_quote_generates_dev_number(): void
    {
        $invoice = $this->service->create($this->invoiceData(['type' => 'quote']), $this->user);

        $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{5}$/', $invoice->number);
    }

    public function test_fiscal_year_is_derived_from_issue_date(): void
    {
        $invoice = $this->service->create($this->invoiceData(['issue_date' => '2025-06-15']), $this->user);

        $this->assertSame(2025, $invoice->fiscal_year);
        $this->assertStringContainsString('-2025-', $invoice->number);
    }

    // -------------------------------------------------------------------------
    // Items
    // -------------------------------------------------------------------------

    public function test_create_stores_items_with_computed_line_total(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);

        $invoice = $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $product, quantity: 2, unitPrice: 150),
            $this->itemData(quantity: 1, unitPrice: 200),
        ]), $this->user);

        $this->assertCount(2, $invoice->items);
        $this->assertEquals('300.00', $invoice->items[0]->line_total_ht);
        $this->assertEquals('200.00', $invoice->items[1]->line_total_ht);
    }

    // -------------------------------------------------------------------------
    // Totals
    // -------------------------------------------------------------------------

    public function test_create_recomputes_totals_correctly(): void
    {
        // subtotal = 300 + 200 = 500 | discount = 50 | tva 20%
        // tva_amount = (500 - 50) * 0.20 = 90 | total = 450 + 90 = 540
        $product = Product::factory()->create(['quantity_on_hand' => 10]);

        $invoice = $this->service->create($this->invoiceData(
            overrides: ['discount' => 50, 'tva_rate' => 20],
            items: [
                $this->itemData(product: $product, quantity: 2, unitPrice: 150),
                $this->itemData(quantity: 1, unitPrice: 200),
            ]
        ), $this->user);

        $this->assertEquals('500.00', $invoice->subtotal_ht);
        $this->assertEquals('90.00', $invoice->tva_amount);
        $this->assertEquals('540.00', $invoice->total_ttc);
    }

    // -------------------------------------------------------------------------
    // Stock movements on create
    // -------------------------------------------------------------------------

    public function test_create_invoice_decrements_stock_for_product_lines(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);

        $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $product, quantity: 3, unitPrice: 100),
        ]), $this->user);

        $this->assertSame(7, $product->fresh()->quantity_on_hand);
    }

    public function test_create_invoice_does_not_decrement_stock_for_service_lines(): void
    {
        $this->service->create($this->invoiceData(items: [
            $this->itemData(quantity: 1, unitPrice: 200), // no product_id
        ]), $this->user);

        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_create_quote_does_not_decrement_stock(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 5]);

        $this->service->create($this->invoiceData(
            overrides: ['type' => 'quote'],
            items: [$this->itemData(product: $product, quantity: 2, unitPrice: 100)],
        ), $this->user);

        $this->assertSame(5, $product->fresh()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    // -------------------------------------------------------------------------
    // Rollback on insufficient stock
    // -------------------------------------------------------------------------

    public function test_create_rolls_back_entirely_on_insufficient_stock(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 1]);

        $this->expectException(InsufficientStockException::class);

        $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $product, quantity: 5, unitPrice: 100),
        ]), $this->user);

        // Assertions after the exception would not run — verified via side-effects.
    }

    public function test_create_rollback_leaves_stock_and_invoice_table_unchanged(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 1]);

        try {
            $this->service->create($this->invoiceData(items: [
                $this->itemData(product: $product, quantity: 5, unitPrice: 100),
            ]), $this->user);
        } catch (InsufficientStockException) {
        }

        $this->assertSame(1, $product->fresh()->quantity_on_hand);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    // =========================================================================
    // update()
    // =========================================================================

    // -------------------------------------------------------------------------
    // Immutable fields
    // -------------------------------------------------------------------------

    public function test_update_preserves_number_type_and_fiscal_year(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $product, quantity: 1, unitPrice: 100),
        ]), $this->user);

        $originalNumber     = $invoice->number;
        $originalType       = $invoice->type;
        $originalFiscalYear = $invoice->fiscal_year;

        $this->service->update($invoice, $this->invoiceData(items: [
            $this->itemData(quantity: 1, unitPrice: 50),
        ]), $this->user);

        $fresh = $invoice->fresh();
        $this->assertSame($originalNumber, $fresh->number);
        $this->assertEquals($originalType, $fresh->type);
        $this->assertSame($originalFiscalYear, $fresh->fiscal_year);
    }

    // -------------------------------------------------------------------------
    // Item replacement
    // -------------------------------------------------------------------------

    public function test_update_replaces_all_items(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $product, quantity: 1, unitPrice: 100),
            $this->itemData(quantity: 1, unitPrice: 50),
        ]), $this->user);

        $this->service->update($invoice, $this->invoiceData(items: [
            $this->itemData(quantity: 1, unitPrice: 300),
        ]), $this->user);

        $this->assertCount(1, $invoice->fresh()->items);
    }

    // -------------------------------------------------------------------------
    // Totals recomputed on update
    // -------------------------------------------------------------------------

    public function test_update_recomputes_totals(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 10]);
        $invoice = $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $product, quantity: 1, unitPrice: 100),
        ]), $this->user);

        $this->service->update($invoice, $this->invoiceData(
            overrides: ['discount' => 0, 'tva_rate' => 20],
            items: [$this->itemData(quantity: 1, unitPrice: 400)],
        ), $this->user);

        $fresh = $invoice->fresh();
        $this->assertEquals('400.00', $fresh->subtotal_ht);
        $this->assertEquals('80.00', $fresh->tva_amount);
        $this->assertEquals('480.00', $fresh->total_ttc);
    }

    // -------------------------------------------------------------------------
    // Stock reversal + re-apply on update
    // -------------------------------------------------------------------------

    public function test_update_reverses_old_stock_and_applies_new(): void
    {
        $productA = Product::factory()->create(['quantity_on_hand' => 10]);
        $productB = Product::factory()->create(['quantity_on_hand' => 20]);

        $invoice = $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $productA, quantity: 3, unitPrice: 100),
        ]), $this->user);

        // productA: 10 - 3 = 7
        $this->assertSame(7, $productA->fresh()->quantity_on_hand);

        $this->service->update($invoice, $this->invoiceData(items: [
            $this->itemData(product: $productB, quantity: 5, unitPrice: 50),
        ]), $this->user);

        // productA reversed: 7 + 3 = 10 | productB decremented: 20 - 5 = 15
        $this->assertSame(10, $productA->fresh()->quantity_on_hand);
        $this->assertSame(15, $productB->fresh()->quantity_on_hand);
    }

    // -------------------------------------------------------------------------
    // Rollback on update with insufficient stock
    // -------------------------------------------------------------------------

    public function test_update_rolls_back_entirely_on_insufficient_stock(): void
    {
        $productA = Product::factory()->create(['quantity_on_hand' => 10]);
        $productB = Product::factory()->create(['quantity_on_hand' => 1]);

        $invoice = $this->service->create($this->invoiceData(items: [
            $this->itemData(product: $productA, quantity: 3, unitPrice: 100),
        ]), $this->user);

        $originalItems = $invoice->items->toArray();

        try {
            $this->service->update($invoice, $this->invoiceData(items: [
                $this->itemData(product: $productB, quantity: 5, unitPrice: 50),
            ]), $this->user);
        } catch (InsufficientStockException) {
        }

        // productA stock must be back to 7 (the reversal itself was rolled back)
        $this->assertSame(7, $productA->fresh()->quantity_on_hand);
        $this->assertSame(1, $productB->fresh()->quantity_on_hand);

        // Invoice still has its original items
        $this->assertCount(count($originalItems), $invoice->fresh()->items);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** @param array<string,mixed> $overrides */
    private function invoiceData(array $overrides = [], array $items = []): array
    {
        return array_merge([
            'type'        => 'invoice',
            'customer_id' => $this->customer->id,
            'issue_date'  => '2026-05-17',
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => $items,
        ], $overrides);
    }

    private function itemData(?Product $product = null, float $quantity = 1, float $unitPrice = 100): array
    {
        return [
            'product_id'    => $product?->id,
            'description'   => $product?->name ?? 'Main d\'œuvre',
            'quantity'      => $quantity,
            'unit_price_ht' => $unitPrice,
        ];
    }
}
