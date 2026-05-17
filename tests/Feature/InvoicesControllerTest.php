<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoicesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // =========================================================================
    // Guests
    // =========================================================================

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('invoices.create'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_show(): void
    {
        $invoice = Invoice::factory()->create();
        $this->get(route('invoices.show', $invoice))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_edit(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft->value]);
        $this->get(route('invoices.edit', $invoice))->assertRedirect(route('login'));
    }

    // =========================================================================
    // Employee — view + create, no delete
    // =========================================================================

    public function test_employee_can_access_index(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Index'));
    }

    public function test_employee_can_access_show(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $invoice  = Invoice::factory()->create();

        $this->actingAs($employee)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Show')
                ->where('invoice.id', $invoice->id));
    }

    public function test_employee_can_access_create(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Create'));
    }

    public function test_employee_can_store_invoice(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->post(route('invoices.store'), $this->storePayload($customer))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'type'        => InvoiceType::Invoice->value,
            'customer_id' => $customer->id,
            'status'      => InvoiceStatus::Draft->value,
        ]);
    }

    public function test_employee_can_access_edit_on_draft(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Draft->value]);

        $this->actingAs($employee)
            ->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Edit'));
    }

    public function test_employee_cannot_delete(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $invoice  = Invoice::factory()->create();

        $this->actingAs($employee)
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();
    }

    public function test_employee_can_update_draft_invoice(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create([
            'type'        => InvoiceType::Invoice->value,
            'status'      => InvoiceStatus::Draft->value,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($employee)
            ->put(route('invoices.update', $invoice), $this->updatePayload($customer, ['notes' => 'Test employé']))
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'notes' => 'Test employé']);
    }

    // =========================================================================
    // Policy — edit blocked for non-draft invoices
    // =========================================================================

    public function test_employee_cannot_edit_issued_invoice(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Issued->value]);

        $this->actingAs($employee)
            ->get(route('invoices.edit', $invoice))
            ->assertForbidden();
    }

    public function test_employee_cannot_update_issued_invoice(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Issued->value]);

        $this->actingAs($employee)
            ->put(route('invoices.update', $invoice), $this->updatePayload($customer))
            ->assertForbidden();
    }

    public function test_employee_cannot_update_paid_invoice(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Paid->value]);

        $this->actingAs($employee)
            ->put(route('invoices.update', $invoice), $this->updatePayload($customer))
            ->assertForbidden();
    }

    public function test_employee_cannot_update_cancelled_invoice(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Cancelled->value]);

        $this->actingAs($employee)
            ->put(route('invoices.update', $invoice), $this->updatePayload($customer))
            ->assertForbidden();
    }

    public function test_admin_cannot_edit_non_draft_invoice(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Issued->value]);

        $this->actingAs($admin)
            ->get(route('invoices.edit', $invoice))
            ->assertForbidden();
    }

    public function test_admin_cannot_update_non_draft_invoice(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Issued->value]);

        $this->actingAs($admin)
            ->put(route('invoices.update', $invoice), $this->updatePayload($customer))
            ->assertForbidden();
    }

    // =========================================================================
    // Admin — full access
    // =========================================================================

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Index')
                ->where('can.delete', true));
    }

    public function test_admin_can_store_quote(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->post(route('invoices.store'), $this->storePayload($customer, ['type' => 'quote']))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'type'   => InvoiceType::Quote->value,
            'status' => InvoiceStatus::Draft->value,
        ]);
    }

    public function test_admin_can_update_draft_invoice(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create([
            'type'        => InvoiceType::Invoice->value,
            'status'      => InvoiceStatus::Draft->value,
            'created_by'  => $admin->id,
            'customer_id' => $customer->id,
        ]);

        $this->actingAs($admin)
            ->put(route('invoices.update', $invoice), $this->updatePayload($customer, ['notes' => 'Mise à jour test']))
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'notes' => 'Mise à jour test']);
    }

    public function test_admin_can_delete_invoice(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $invoice = Invoice::factory()->create();

        $this->actingAs($admin)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    // =========================================================================
    // Store validation
    // =========================================================================

    public function test_store_requires_type(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $payload  = $this->storePayload($customer);
        unset($payload['type']);

        $this->actingAs($admin)
            ->post(route('invoices.store'), $payload)
            ->assertSessionHasErrors('type');
    }

    public function test_store_requires_at_least_one_item(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->post(route('invoices.store'), $this->storePayload($customer, ['items' => []]))
            ->assertSessionHasErrors('items');
    }

    public function test_store_requires_item_description(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $payload  = $this->storePayload($customer);
        unset($payload['items'][0]['description']);

        $this->actingAs($admin)
            ->post(route('invoices.store'), $payload)
            ->assertSessionHasErrors('items.0.description');
    }

    public function test_store_requires_positive_item_quantity(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $payload  = $this->storePayload($customer);
        $payload['items'][0]['quantity'] = 0;

        $this->actingAs($admin)
            ->post(route('invoices.store'), $payload)
            ->assertSessionHasErrors('items.0.quantity');
    }

    public function test_store_rejects_invalid_type(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->post(route('invoices.store'), $this->storePayload($customer, ['type' => 'invalid']))
            ->assertSessionHasErrors('type');
    }

    public function test_store_rejects_soft_deleted_customer(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $customer->delete();

        $this->actingAs($admin)
            ->post(route('invoices.store'), $this->storePayload($customer))
            ->assertSessionHasErrors('customer_id');
    }

    public function test_store_rejects_vehicle_belonging_to_different_customer(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $other    = Customer::factory()->create();
        $vehicle  = \App\Models\Vehicle::factory()->create(['customer_id' => $other->id]);

        $this->actingAs($admin)
            ->post(route('invoices.store'), $this->storePayload($customer, ['vehicle_id' => $vehicle->id]))
            ->assertSessionHasErrors('vehicle_id');
    }

    public function test_store_with_product_decrements_stock(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['quantity_on_hand' => 10]);

        $payload          = $this->storePayload($customer);
        $payload['items'] = [[
            'product_id'    => $product->id,
            'description'   => $product->name,
            'quantity'      => 3,
            'unit_price_ht' => 50,
        ]];

        $this->actingAs($admin)
            ->post(route('invoices.store'), $payload)
            ->assertRedirect();

        $this->assertSame(7, $product->fresh()->quantity_on_hand);
    }

    // =========================================================================
    // Index — filters
    // =========================================================================

    public function test_index_filters_by_type(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Invoice::factory()->create(['type' => InvoiceType::Invoice->value]);
        Invoice::factory()->create(['type' => InvoiceType::Quote->value]);

        $this->actingAs($admin)
            ->get(route('invoices.index', ['type' => 'invoice']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('invoices.total', 1)
                ->where('invoices.data.0.type', 'invoice'));
    }

    public function test_index_filters_by_status(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Invoice::factory()->create(['status' => InvoiceStatus::Draft->value]);
        Invoice::factory()->create(['status' => InvoiceStatus::Paid->value]);

        $this->actingAs($admin)
            ->get(route('invoices.index', ['status' => 'draft']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('invoices.total', 1)
                ->where('invoices.data.0.status', 'draft'));
    }

    public function test_index_filters_by_number_search(): void
    {
        $admin   = User::factory()->create()->assignRole('admin');
        $target  = Invoice::factory()->create(['number' => 'FAC-2026-00001']);
        Invoice::factory()->create(['number' => 'DEV-2026-00001']);

        $this->actingAs($admin)
            ->get(route('invoices.index', ['search' => 'FAC-2026']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('invoices.total', 1)
                ->where('invoices.data.0.number', $target->number));
    }

    public function test_index_filters_by_date_from(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Invoice::factory()->create(['issue_date' => '2026-01-10']);
        $recent = Invoice::factory()->create(['issue_date' => '2026-06-01']);

        $this->actingAs($admin)
            ->get(route('invoices.index', ['date_from' => '2026-06-01']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('invoices.total', 1)
                ->where('invoices.data.0.id', $recent->id));
    }

    public function test_index_filters_by_date_to(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $old = Invoice::factory()->create(['issue_date' => '2026-01-10']);
        Invoice::factory()->create(['issue_date' => '2026-06-01']);

        $this->actingAs($admin)
            ->get(route('invoices.index', ['date_to' => '2026-01-31']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('invoices.total', 1)
                ->where('invoices.data.0.id', $old->id));
    }

    public function test_index_filters_by_date_range(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        Invoice::factory()->create(['issue_date' => '2026-01-10']);
        $target = Invoice::factory()->create(['issue_date' => '2026-03-15']);
        Invoice::factory()->create(['issue_date' => '2026-06-01']);

        $this->actingAs($admin)
            ->get(route('invoices.index', ['date_from' => '2026-03-01', 'date_to' => '2026-03-31']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('invoices.total', 1)
                ->where('invoices.data.0.id', $target->id));
    }

    // =========================================================================
    // InsufficientStock — surface as validation error, not 500
    // =========================================================================

    public function test_store_returns_error_on_insufficient_stock(): void
    {
        $admin    = User::factory()->create()->assignRole('admin');
        $customer = Customer::factory()->create();
        $product  = Product::factory()->create(['quantity_on_hand' => 1]);

        $payload           = $this->storePayload($customer);
        $payload['items']  = [[
            'product_id'    => $product->id,
            'description'   => $product->name,
            'quantity'      => 99,
            'unit_price_ht' => 100,
        ]];

        $this->actingAs($admin)
            ->post(route('invoices.store'), $payload)
            ->assertSessionHasErrors('items');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** @param array<string,mixed> $overrides */
    private function storePayload(Customer $customer, array $overrides = []): array
    {
        return array_merge([
            'type'        => 'invoice',
            'customer_id' => $customer->id,
            'issue_date'  => now()->toDateString(),
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => [
                [
                    'product_id'    => null,
                    'description'   => "Main d'œuvre",
                    'quantity'      => 1,
                    'unit_price_ht' => 200,
                ],
            ],
        ], $overrides);
    }

    /** @param array<string,mixed> $overrides */
    private function updatePayload(Customer $customer, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'issue_date'  => now()->toDateString(),
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => [
                [
                    'product_id'    => null,
                    'description'   => "Main d'œuvre",
                    'quantity'      => 1,
                    'unit_price_ht' => 200,
                ],
            ],
        ], $overrides);
    }
}
