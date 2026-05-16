<?php

namespace Tests\Unit\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Enum casts
    // -------------------------------------------------------------------------

    public function test_type_is_cast_to_invoice_type_enum(): void
    {
        $invoice = Invoice::factory()->create(['type' => InvoiceType::Invoice->value]);

        $this->assertInstanceOf(InvoiceType::class, $invoice->type);
        $this->assertEquals(InvoiceType::Invoice, $invoice->type);
    }

    public function test_status_is_cast_to_invoice_status_enum(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paid->value]);

        $this->assertInstanceOf(InvoiceStatus::class, $invoice->status);
        $this->assertEquals(InvoiceStatus::Paid, $invoice->status);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function test_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($invoice->customer->is($customer));
    }

    public function test_vehicle_is_nullable(): void
    {
        $invoice = Invoice::factory()->create(['vehicle_id' => null]);

        $this->assertNull($invoice->vehicle);
    }

    public function test_belongs_to_vehicle_when_set(): void
    {
        $vehicle = Vehicle::factory()->create();
        $invoice = Invoice::factory()->create(['vehicle_id' => $vehicle->id]);

        $this->assertTrue($invoice->vehicle->is($vehicle));
    }

    public function test_creator_belongs_to_user_via_created_by(): void
    {
        $user    = User::factory()->create();
        $invoice = Invoice::factory()->create(['created_by' => $user->id]);

        $this->assertTrue($invoice->creator->is($user));
    }

    public function test_converted_from_quote_is_nullable(): void
    {
        $invoice = Invoice::factory()->create(['converted_from_quote_id' => null]);

        $this->assertNull($invoice->convertedFromQuote);
    }

    public function test_converted_from_quote_belongs_to_self(): void
    {
        $quote   = Invoice::factory()->create(['type' => InvoiceType::Quote->value]);
        $invoice = Invoice::factory()->create([
            'type'                   => InvoiceType::Invoice->value,
            'converted_from_quote_id' => $quote->id,
        ]);

        $this->assertTrue($invoice->convertedFromQuote->is($quote));
    }

    public function test_has_many_items(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        // Items belonging to another invoice must not bleed in
        InvoiceItem::factory()->count(2)->create();

        $this->assertCount(3, $invoice->items);
    }

    // -------------------------------------------------------------------------
    // recomputeTotals()
    // -------------------------------------------------------------------------

    public function test_recompute_totals_sums_item_line_totals_into_subtotal(): void
    {
        $invoice = Invoice::factory()->create(['discount' => 0, 'tva_rate' => 20]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'line_total_ht' => 100.00]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'line_total_ht' => 250.00]);

        $invoice->recomputeTotals();

        $this->assertEquals(350.00, (float) $invoice->subtotal_ht);
    }

    public function test_recompute_totals_calculates_tva_amount(): void
    {
        $invoice = Invoice::factory()->create(['discount' => 0, 'tva_rate' => 20]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'line_total_ht' => 500.00]);

        $invoice->recomputeTotals();

        $this->assertEquals(100.00, (float) $invoice->tva_amount);
    }

    public function test_recompute_totals_calculates_total_ttc(): void
    {
        $invoice = Invoice::factory()->create(['discount' => 0, 'tva_rate' => 20]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'line_total_ht' => 500.00]);

        $invoice->recomputeTotals();

        $this->assertEquals(600.00, (float) $invoice->total_ttc);
    }

    public function test_recompute_totals_applies_discount_before_tva(): void
    {
        $invoice = Invoice::factory()->create(['discount' => 50.00, 'tva_rate' => 20]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'line_total_ht' => 500.00]);

        $invoice->recomputeTotals();

        // subtotal = 500, discount = 50, taxable = 450
        // tva = 450 * 20% = 90, total = 450 + 90 = 540
        $this->assertEquals(500.00, (float) $invoice->subtotal_ht);
        $this->assertEquals(90.00, (float) $invoice->tva_amount);
        $this->assertEquals(540.00, (float) $invoice->total_ttc);
    }

    public function test_recompute_totals_with_no_items_zeros_amounts(): void
    {
        $invoice = Invoice::factory()->create(['discount' => 0, 'tva_rate' => 20]);
        $invoice->recomputeTotals();

        $this->assertEquals(0.00, (float) $invoice->subtotal_ht);
        $this->assertEquals(0.00, (float) $invoice->tva_amount);
        $this->assertEquals(0.00, (float) $invoice->total_ttc);
    }

    public function test_recompute_totals_is_fluent(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertSame($invoice, $invoice->recomputeTotals());
    }

    public function test_recompute_totals_persists_when_saved(): void
    {
        $invoice = Invoice::factory()->create(['discount' => 0, 'tva_rate' => 20]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'line_total_ht' => 200.00]);

        $invoice->recomputeTotals()->save();

        $fresh = $invoice->fresh();
        $this->assertEquals(200.00, (float) $fresh->subtotal_ht);
        $this->assertEquals(40.00, (float) $fresh->tva_amount);
        $this->assertEquals(240.00, (float) $fresh->total_ttc);
    }
}
