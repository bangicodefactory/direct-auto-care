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

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function validPayload(Customer $customer): array
    {
        return [
            'type'        => InvoiceType::Invoice->value,
            'customer_id' => $customer->id,
            'issue_date'  => '2026-05-17',
            'items'       => [
                [
                    'product_id'    => null,
                    'description'   => 'Vidange moteur',
                    'quantity'      => 1,
                    'unit_price_ht' => 150.00,
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Guest — redirected on all routes
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_invoices_index(): void
    {
        $this->get(route('invoices.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_invoices_show(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get(route('invoices.show', $invoice))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_invoices_edit(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft->value]);

        $this->get(route('invoices.edit', $invoice))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_invoices_destroy(): void
    {
        $invoice = Invoice::factory()->create();

        $this->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // User without role — forbidden on all routes
    // -------------------------------------------------------------------------

    public function test_user_without_role_cannot_access_invoices_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertForbidden();
    }

    public function test_user_without_role_cannot_store_invoice(): void
    {
        $user     = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('invoices.store'), $this->validPayload($customer))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Employee — can view, create, update Draft; cannot delete, cannot update non-Draft
    // -------------------------------------------------------------------------

    public function test_employee_can_view_invoices_index(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('invoices.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Index'));
    }

    public function test_employee_can_access_create_form(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('invoices.create'))
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Create'));
    }

    public function test_employee_can_store_invoice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->post(route('invoices.store'), $this->validPayload($customer))
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id]);
    }

    public function test_employee_can_view_invoice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice  = Invoice::factory()->create();

        $this->actingAs($employee)
            ->get(route('invoices.show', $invoice))
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Show'));
    }

    public function test_employee_can_access_edit_form_for_draft_invoice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Draft->value]);

        $this->actingAs($employee)
            ->get(route('invoices.edit', $invoice))
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Edit'));
    }

    public function test_employee_cannot_access_edit_form_for_issued_invoice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice  = Invoice::factory()->create(['status' => InvoiceStatus::Issued->value]);

        $this->actingAs($employee)
            ->get(route('invoices.edit', $invoice))
            ->assertForbidden();
    }

    public function test_employee_can_update_draft_invoice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create([
            'status'      => InvoiceStatus::Draft->value,
            'customer_id' => $customer->id,
        ]);

        $payload = $this->validPayload($customer);
        unset($payload['type']);

        $this->actingAs($employee)
            ->put(route('invoices.update', $invoice), $payload)
            ->assertRedirect(route('invoices.show', $invoice));
    }

    public function test_employee_cannot_update_issued_invoice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create([
            'status'      => InvoiceStatus::Issued->value,
            'customer_id' => $customer->id,
        ]);

        $payload = $this->validPayload($customer);
        unset($payload['type']);

        $this->actingAs($employee)
            ->put(route('invoices.update', $invoice), $payload)
            ->assertForbidden();
    }

    public function test_employee_cannot_delete_invoice(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice  = Invoice::factory()->create();

        $this->actingAs($employee)
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Admin — all actions allowed
    // -------------------------------------------------------------------------

    public function test_admin_can_view_invoices_index(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('invoices.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Invoices/Index'));
    }

    public function test_admin_can_delete_draft_invoice(): void
    {
        $admin   = User::factory()->create();
        $admin->assignRole('admin');
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft->value]);

        $this->actingAs($admin)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertSoftDeleted($invoice);
    }

    public function test_admin_cannot_delete_issued_invoice(): void
    {
        // An issued facture must be cancelled first (stock restored, audit
        // trail kept), then deleted. Direct deletion is denied.
        $admin   = User::factory()->create();
        $admin->assignRole('admin');
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Issued->value]);

        $this->actingAs($admin)
            ->delete(route('invoices.destroy', $invoice))
            ->assertForbidden();

        $this->assertNotSoftDeleted($invoice);
    }

    public function test_admin_can_delete_cancelled_invoice(): void
    {
        $admin   = User::factory()->create();
        $admin->assignRole('admin');
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Cancelled->value]);

        $this->actingAs($admin)
            ->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertSoftDeleted($invoice);
    }

    public function test_force_delete_is_always_denied(): void
    {
        $admin   = User::factory()->create();
        $admin->assignRole('admin');
        $invoice = Invoice::factory()->create();

        $this->assertFalse($admin->can('forceDelete', $invoice));
    }
}
