<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_invoice(): void
    {
        $invoice = Invoice::factory()->create();
        $item    = InvoiceItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->assertTrue($item->invoice->is($invoice));
    }

    public function test_product_is_nullable(): void
    {
        $item = InvoiceItem::factory()->create(['product_id' => null]);

        $this->assertNull($item->product);
    }

    public function test_belongs_to_product_when_set(): void
    {
        $product = Product::factory()->create();
        $item    = InvoiceItem::factory()->create(['product_id' => $product->id]);

        $this->assertTrue($item->product->is($product));
    }

    public function test_decimal_fields_are_cast(): void
    {
        $item = InvoiceItem::factory()->create([
            'quantity'      => 2,
            'unit_price_ht' => 150,
            'line_total_ht' => 300,
        ]);

        $this->assertEquals('2.00', $item->quantity);
        $this->assertEquals('150.00', $item->unit_price_ht);
        $this->assertEquals('300.00', $item->line_total_ht);
    }
}
