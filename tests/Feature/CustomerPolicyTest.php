<?php

namespace Tests\Feature;

use App\Models\Customer;
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
}
