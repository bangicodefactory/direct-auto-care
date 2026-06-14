<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\ServiceOrderStatus;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;
use App\Services\ServiceOrderService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ServiceOrderCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function employee(): User
    {
        return User::factory()->create()->assignRole('employee');
    }

    /**
     * A ready-for-pickup order with two parts lines (260 HT total).
     */
    private function orderReadyForPickup(): ServiceOrder
    {
        $order = ServiceOrder::factory()->readyPickup()->create();

        ServiceOrderItem::factory()->create([
            'service_order_id' => $order->id,
            'description'      => 'Filtre à huile',
            'quantity'         => 2,
            'unit_price_ht'    => 80,
            'line_total_ht'    => 160,
        ]);
        ServiceOrderItem::factory()->create([
            'service_order_id' => $order->id,
            'description'      => "Main d'œuvre",
            'quantity'         => 1,
            'unit_price_ht'    => 100,
            'line_total_ht'    => 100,
        ]);

        return $order->load('items');
    }

    public function test_closing_generates_an_issued_invoice_and_marks_the_order_closed(): void
    {
        $order = $this->orderReadyForPickup();

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(ServiceOrderStatus::Closed, $order->status);
        $this->assertNotNull($order->closed_at);
        $this->assertNotNull($order->invoice_id);

        $invoice = Invoice::find($order->invoice_id);
        $this->assertNotNull($invoice);
        $this->assertSame(InvoiceType::Invoice, $invoice->type);
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame($order->customer_id, $invoice->customer_id);
        $this->assertSame($order->vehicle_id, $invoice->vehicle_id);
        $this->assertStringStartsWith('FAC-', $invoice->number);
    }

    public function test_invoice_totals_apply_20_percent_tva(): void
    {
        $order = $this->orderReadyForPickup();

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order));

        $invoice = Invoice::find($order->refresh()->invoice_id);

        // 160 + 100 = 260 HT ; TVA 20% = 52 ; TTC = 312.
        $this->assertEqualsWithDelta(260, (float) $invoice->subtotal_ht, 0.001);
        $this->assertEqualsWithDelta(52, (float) $invoice->tva_amount, 0.001);
        $this->assertEqualsWithDelta(312, (float) $invoice->total_ttc, 0.001);
    }

    public function test_invoice_items_mirror_the_service_order_items(): void
    {
        $order = $this->orderReadyForPickup();

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order));

        $invoice = Invoice::with('items')->find($order->refresh()->invoice_id);

        $this->assertCount(2, $invoice->items);
        $this->assertEqualsCanonicalizing(
            ['Filtre à huile', "Main d'œuvre"],
            $invoice->items->pluck('description')->all(),
        );
    }

    public function test_closing_does_not_decrement_stock_again(): void
    {
        $order   = ServiceOrder::factory()->readyPickup()->create();
        $product = Product::factory()->create(['quantity_on_hand' => 5]);

        // Stock was already decremented when the part was added in the workshop;
        // the line simply references the product. Closing must not move stock again.
        ServiceOrderItem::factory()->create([
            'service_order_id' => $order->id,
            'product_id'       => $product->id,
            'quantity'         => 3,
            'unit_price_ht'    => 50,
            'line_total_ht'    => 150,
        ]);

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order));

        $this->assertSame(5, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_an_order_not_ready_for_pickup_does_not_get_an_invoice_on_advance(): void
    {
        // Advancing from an earlier phase moves one step forward without invoicing.
        $order = ServiceOrder::factory()->inProgress()->create();

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(ServiceOrderStatus::QualityControl, $order->status);
        $this->assertNull($order->invoice_id);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_closing_creates_a_service_record_for_the_vehicle(): void
    {
        $order = $this->orderReadyForPickup();
        $order->update(['mileage_in' => 123456, 'diagnosis_notes' => 'Vidange + plaquettes']);

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order));

        $order->refresh();
        $this->assertDatabaseHas('service_records', [
            'vehicle_id'         => $order->vehicle_id,
            'invoice_id'         => $order->invoice_id,
            'mileage_at_service' => 123456,
            'notes'              => 'Vidange + plaquettes',
            'serviced_at'        => now()->toDateString(),
        ]);
        // Exactly one record — the close path is idempotent (firstOrCreate on invoice_id).
        $this->assertDatabaseCount('service_records', 1);
    }

    public function test_closing_an_order_without_items_is_rejected(): void
    {
        $order = ServiceOrder::factory()->readyPickup()->create();

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order))
            ->assertStatus(422);

        $order->refresh();
        $this->assertSame(ServiceOrderStatus::ReadyPickup, $order->status);
        $this->assertNull($order->invoice_id);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_close_rejects_an_order_not_ready_for_pickup(): void
    {
        // The controller never calls close() outside ReadyPickup, but the service
        // guards it anyway — exercise that contract directly.
        $order = ServiceOrder::factory()->inProgress()->create();
        ServiceOrderItem::factory()->create(['service_order_id' => $order->id]);

        try {
            app(ServiceOrderService::class)->close($order, $this->employee());
            $this->fail('Expected an HttpException for a non-ready order.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertDatabaseCount('invoices', 0);
        $this->assertNull($order->refresh()->invoice_id);
    }

    public function test_each_close_produces_a_distinct_invoice_number(): void
    {
        $employee = $this->employee();
        $first    = $this->orderReadyForPickup();
        $second   = $this->orderReadyForPickup();

        $this->actingAs($employee)->patch(route('workshop.advance', $first));
        $this->actingAs($employee)->patch(route('workshop.advance', $second));

        $numbers = Invoice::pluck('number');
        $this->assertCount(2, $numbers);
        $this->assertSame($numbers->unique()->count(), $numbers->count());
    }
}
