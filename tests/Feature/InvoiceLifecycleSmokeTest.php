<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IST-57 — Smoke test: full invoice lifecycle + numbering integrity
 *
 * Covers:
 *  - Quote → convert → invoice (number format, link, items copied, stock)
 *  - Sequential numbering within the same fiscal year
 *  - Quote and invoice sequences are independent counters
 */
class InvoiceLifecycleSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * End-to-end lifecycle:
     *   1. POST /invoices (quote) → DEV-YYYY-NNNNN, stock untouched
     *   2. POST /invoices/{quote}/convert → FAC-YYYY-NNNNN, link set, items copied, stock decremented
     */
    public function test_quote_to_invoice_full_lifecycle(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $year     = now()->year;

        // Step 1 — create quote
        $this->actingAs($admin)
            ->post(route('invoices.store'), [
                'type'        => 'quote',
                'customer_id' => $customer->id,
                'issue_date'  => now()->toDateString(),
                'discount'    => 0,
                'tva_rate'    => 20,
                'items'       => [
                    [
                        'product_id'    => $product->id,
                        'description'   => 'Filtre à huile',
                        'quantity'      => 2,
                        'unit_price_ht' => 75,
                    ],
                    [
                        'product_id'    => null,
                        'description'   => "Main d'œuvre",
                        'quantity'      => 1,
                        'unit_price_ht' => 200,
                    ],
                ],
            ])
            ->assertRedirect();

        $quote = Invoice::where('type', InvoiceType::Quote->value)->latest('id')->first();
        $this->assertNotNull($quote);

        // Quote gets a DEV- number
        $this->assertStringStartsWith("DEV-{$year}-", $quote->number);
        $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{5}$/', $quote->number);

        // Quotes must NOT decrement stock
        $this->assertEquals(10, $product->fresh()->quantity_on_hand);

        // Step 2 — convert quote to invoice
        $this->actingAs($admin)
            ->post(route('invoices.convert', $quote))
            ->assertRedirect();

        $invoice = Invoice::where('converted_from_quote_id', $quote->id)->first();
        $this->assertNotNull($invoice);

        // Invoice gets a FAC- number
        $this->assertStringStartsWith("FAC-{$year}-", $invoice->number);
        $this->assertMatchesRegularExpression('/^FAC-\d{4}-\d{5}$/', $invoice->number);

        // Conversion link is preserved
        $this->assertEquals($quote->id, $invoice->converted_from_quote_id);

        // All items are copied to the new invoice
        $this->assertCount(2, $invoice->items);
        $productItem = $invoice->items->firstWhere('description', 'Filtre à huile');
        $this->assertNotNull($productItem);
        $this->assertEquals($product->id, $productItem->product_id);
        $this->assertEquals(2, (float) $productItem->quantity);
        $this->assertEquals(75, (float) $productItem->unit_price_ht);

        // Converting an invoice with product items decrements stock
        $this->assertEquals(8, $product->fresh()->quantity_on_hand);

        // recomputeTotals() ran correctly: 2×75 + 1×200 = 350 HT, 20% TVA → 70, TTC = 420
        $invoice->refresh();
        $this->assertEquals(350, (float) $invoice->subtotal_ht);
        $this->assertEquals(70, (float) $invoice->tva_amount);
        $this->assertEquals(420, (float) $invoice->total_ttc);

        // Stock movement is linked to the new invoice, not the original quote
        $this->assertDatabaseHas('stock_movements', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'invoice_id' => $quote->id,
        ]);
    }

    /**
     * Invoices created in sequence must receive consecutive 5-digit numbers
     * with no gaps: 00001, 00002, 00003.
     */
    public function test_invoice_numbers_are_sequential(): void
    {
        $service  = app(InvoiceService::class);
        $user     = User::factory()->create();
        $customer = Customer::factory()->create();
        $year     = now()->year;

        $data = [
            'type'        => 'invoice',
            'customer_id' => $customer->id,
            'issue_date'  => now()->toDateString(),
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => [['description' => "Main d'œuvre", 'quantity' => 1, 'unit_price_ht' => 100]],
        ];

        $first  = $service->create($data, $user);
        $second = $service->create($data, $user);
        $third  = $service->create($data, $user);

        $this->assertEquals("FAC-{$year}-00001", $first->number);
        $this->assertEquals("FAC-{$year}-00002", $second->number);
        $this->assertEquals("FAC-{$year}-00003", $third->number);
    }

    /**
     * Quote (DEV-) and invoice (FAC-) sequences are maintained independently.
     * Creating quotes must not advance the invoice counter and vice versa.
     */
    public function test_quote_and_invoice_sequences_are_independent(): void
    {
        $service  = app(InvoiceService::class);
        $user     = User::factory()->create();
        $customer = Customer::factory()->create();
        $year     = now()->year;

        $base = [
            'customer_id' => $customer->id,
            'issue_date'  => now()->toDateString(),
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => [['description' => "Main d'œuvre", 'quantity' => 1, 'unit_price_ht' => 100]],
        ];

        $quote1   = $service->create(array_merge($base, ['type' => 'quote']), $user);
        $invoice1 = $service->create(array_merge($base, ['type' => 'invoice']), $user);
        $quote2   = $service->create(array_merge($base, ['type' => 'quote']), $user);
        $invoice2 = $service->create(array_merge($base, ['type' => 'invoice']), $user);

        $this->assertEquals("DEV-{$year}-00001", $quote1->number);
        $this->assertEquals("FAC-{$year}-00001", $invoice1->number);
        $this->assertEquals("DEV-{$year}-00002", $quote2->number);
        $this->assertEquals("FAC-{$year}-00002", $invoice2->number);
    }
}
