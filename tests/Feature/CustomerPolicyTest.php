<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Guest — redirected on all routes
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_customers_index(): void
    {
        $this->get(route('customers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_customers_show(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.show', $customer))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_customers_edit(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.edit', $customer))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_customers_destroy(): void
    {
        $customer = Customer::factory()->create();

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // User without role — forbidden on all routes
    // -------------------------------------------------------------------------

    public function test_user_without_role_cannot_access_customers_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertForbidden();
    }

    public function test_user_without_role_cannot_store_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '+212612345678',
            ])
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Employee — can view, create, update; cannot delete
    // -------------------------------------------------------------------------

    public function test_employee_can_view_customers_index(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('customers.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Customers/Index'));
    }

    public function test_employee_can_access_create_form(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('customers.create'))
            ->assertInertia(fn (Assert $page) => $page->component('Customers/Create'));
    }

    public function test_employee_can_store_customer(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $this->actingAs($employee)
            ->post(route('customers.store'), [
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
                'phone'      => '+212612345678',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['phone' => '+212612345678']);
    }

    public function test_employee_can_view_customer(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->get(route('customers.show', $customer))
            ->assertInertia(fn (Assert $page) => $page->component('Customers/Show'));
    }

    public function test_employee_can_access_edit_form(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->get(route('customers.edit', $customer))
            ->assertInertia(fn (Assert $page) => $page->component('Customers/Edit'));
    }

    public function test_employee_can_update_customer(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->put(route('customers.update', $customer), [
                'first_name' => 'Updated',
                'last_name'  => 'Name',
                'phone'      => $customer->phone,
            ])
            ->assertRedirect(route('customers.show', $customer));
    }

    public function test_employee_cannot_delete_customer(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Admin — all actions allowed
    // -------------------------------------------------------------------------

    public function test_admin_can_view_customers_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Customers/Index'));
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted($customer);
    }

    // -------------------------------------------------------------------------
    // A facture pins its client — nobody may delete them
    // -------------------------------------------------------------------------

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_cannot_delete_a_customer_holding_a_facture(): void
    {
        $customer = Customer::factory()->create();
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Invoice->value,
            'status'      => InvoiceStatus::Paid->value,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_a_draft_facture_still_blocks_deletion(): void
    {
        // The fiscal number is assigned when the row is created, so a facture
        // sitting in brouillon has already burned one.
        $customer = Customer::factory()->create();
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Invoice->value,
            'status'      => InvoiceStatus::Draft->value,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_a_soft_deleted_facture_still_blocks_deletion(): void
    {
        $customer = Customer::factory()->create();
        $facture  = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Invoice->value,
            'status'      => InvoiceStatus::Issued->value,
        ]);
        $facture->delete();

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_a_devis_does_not_block_deletion(): void
    {
        $customer = Customer::factory()->create();
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => InvoiceType::Quote->value,
            'status'      => InvoiceStatus::Issued->value,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_another_customers_facture_does_not_block_deletion(): void
    {
        $customer = Customer::factory()->create();
        Invoice::factory()->create(['type' => InvoiceType::Invoice->value]);

        $this->actingAs($this->admin())
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
