<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invoice/devis lines that reference a catalog service (prestation): persistence,
 * stock neutrality, product/service mutual exclusion, and quote conversion.
 */
class InvoiceServiceLineTest extends TestCase
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

    /** @param array<int, array<string, mixed>> $items */
    private function payload(Customer $customer, array $items, string $type = 'invoice'): array
    {
        return [
            'type'        => $type,
            'customer_id' => $customer->id,
            'issue_date'  => now()->toDateString(),
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => $items,
        ];
    }

    public function test_product_line_snapshots_the_product_unit_and_service_line_has_none(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['unit' => 'litre', 'quantity_on_hand' => 10]);
        $service  = Service::factory()->create();

        $this->actingAs($this->employee())
            ->post(route('invoices.store'), $this->payload($customer, [
                [
                    'product_id'    => $product->id,
                    'description'   => 'Huile 5W30',
                    'quantity'      => 2,
                    'unit_price_ht' => 100,
                ],
                [
                    'service_id'    => $service->id,
                    'description'   => 'Vidange',
                    'quantity'      => 1,
                    'unit_price_ht' => 150,
                ],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoice_items', ['product_id' => $product->id, 'unit' => 'litre']);
        $this->assertDatabaseHas('invoice_items', ['service_id' => $service->id, 'unit' => null]);
    }

    public function test_invoice_service_line_persists_and_touches_no_stock(): void
    {
        $customer = Customer::factory()->create();
        $service  = Service::factory()->create(['name' => 'Vidange', 'default_price_ht' => 150]);

        $this->actingAs($this->employee())
            ->post(route('invoices.store'), $this->payload($customer, [[
                'service_id'    => $service->id,
                'description'   => 'Vidange huile 5W30',
                'quantity'      => 1,
                'unit_price_ht' => 200, // price edited from the 150 default — must be kept
            ]]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoice_items', [
            'service_id'    => $service->id,
            'product_id'    => null,
            'description'   => 'Vidange huile 5W30',
            'unit_price_ht' => 200,
            'line_total_ht' => 200,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_quote_service_line_persists_without_stock(): void
    {
        $customer = Customer::factory()->create();
        $service  = Service::factory()->create();

        $this->actingAs($this->employee())
            ->post(route('invoices.store'), $this->payload($customer, [[
                'service_id'    => $service->id,
                'description'   => 'Diagnostic',
                'quantity'      => 1,
                'unit_price_ht' => 100,
            ]], 'quote'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoice_items', ['service_id' => $service->id]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_line_cannot_reference_both_product_and_service(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $service  = Service::factory()->create();

        $this->actingAs($this->employee())
            ->post(route('invoices.store'), $this->payload($customer, [[
                'product_id'    => $product->id,
                'service_id'    => $service->id,
                'description'   => 'Ligne invalide',
                'quantity'      => 1,
                'unit_price_ht' => 100,
            ]]))
            ->assertSessionHasErrors('items.0.service_id');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_service_line_rejects_soft_deleted_service(): void
    {
        $customer = Customer::factory()->create();
        $service  = Service::factory()->create();
        $service->delete();

        $this->actingAs($this->employee())
            ->post(route('invoices.store'), $this->payload($customer, [[
                'service_id'    => $service->id,
                'description'   => 'Service supprimé',
                'quantity'      => 1,
                'unit_price_ht' => 100,
            ]]))
            ->assertSessionHasErrors('items.0.service_id');
    }

    public function test_mixed_invoice_decrements_stock_for_product_line_only(): void
    {
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $service  = Service::factory()->create();

        $this->actingAs($this->employee())
            ->post(route('invoices.store'), $this->payload($customer, [
                [
                    'product_id'    => $product->id,
                    'description'   => $product->name,
                    'quantity'      => 2,
                    'unit_price_ht' => 80,
                ],
                [
                    'service_id'    => $service->id,
                    'description'   => $service->name,
                    'quantity'      => 1,
                    'unit_price_ht' => 150,
                ],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(8, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_update_swapping_product_line_for_service_line_restores_stock(): void
    {
        $employee = $this->employee();
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);
        $service  = Service::factory()->create();

        $this->actingAs($employee)
            ->post(route('invoices.store'), $this->payload($customer, [[
                'product_id'    => $product->id,
                'description'   => $product->name,
                'quantity'      => 3,
                'unit_price_ht' => 80,
            ]]));

        $this->assertSame(7, $product->refresh()->quantity_on_hand);
        $invoice = Invoice::firstOrFail();

        $this->actingAs($employee)
            ->put(route('invoices.update', $invoice), [
                'customer_id' => $customer->id,
                'issue_date'  => now()->toDateString(),
                'discount'    => 0,
                'tva_rate'    => 20,
                'items'       => [[
                    'service_id'    => $service->id,
                    'description'   => $service->name,
                    'quantity'      => 1,
                    'unit_price_ht' => 150,
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // Product stock returned; the service line consumed none.
        $this->assertSame(10, $product->refresh()->quantity_on_hand);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'service_id' => $service->id,
            'product_id' => null,
        ]);
    }

    public function test_converting_quote_keeps_service_line_without_stock_movement(): void
    {
        $employee = $this->employee();
        $customer = Customer::factory()->create();
        $service  = Service::factory()->create();

        $this->actingAs($employee)
            ->post(route('invoices.store'), $this->payload($customer, [[
                'service_id'    => $service->id,
                'description'   => 'Recharge clim',
                'quantity'      => 1,
                'unit_price_ht' => 350,
            ]], 'quote'));

        $quote = Invoice::firstOrFail();

        $this->actingAs($employee)
            ->post(route('invoices.convert', $quote))
            ->assertRedirect();

        $facture = Invoice::where('converted_from_quote_id', $quote->id)->firstOrFail();

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $facture->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_invoice_referencing_soft_deleted_service_still_renders(): void
    {
        $employee = $this->employee();
        $customer = Customer::factory()->create();
        $service  = Service::factory()->create();

        $this->actingAs($employee)
            ->post(route('invoices.store'), $this->payload($customer, [[
                'service_id'    => $service->id,
                'description'   => 'Vidange',
                'quantity'      => 1,
                'unit_price_ht' => 150,
            ]]));

        $invoice = Invoice::firstOrFail();
        $service->delete();

        $this->actingAs($employee)
            ->get(route('invoices.show', $invoice))
            ->assertOk();

        // The FK survives a soft delete (nullOnDelete only fires on hard deletes).
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'service_id' => $service->id,
        ]);
    }
}
