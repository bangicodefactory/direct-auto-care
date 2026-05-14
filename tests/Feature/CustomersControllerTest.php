<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomersControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');
    }

    // -------------------------------------------------------------------------
    // index — search
    // -------------------------------------------------------------------------

    public function test_index_returns_all_customers_without_search(): void
    {
        Customer::factory(3)->create();

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Customers/Index')
                ->has('customers.data', 3)
            );
    }

    public function test_index_filters_by_first_name(): void
    {
        Customer::factory()->create(['first_name' => 'Youssef', 'last_name' => 'Alaoui']);
        Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Bennani']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'Youssef']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.first_name', 'Youssef')
            );
    }

    public function test_index_filters_by_last_name(): void
    {
        Customer::factory()->create(['first_name' => 'Omar', 'last_name' => 'Bennani']);
        Customer::factory()->create(['first_name' => 'Sara', 'last_name' => 'Alaoui']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'Bennani']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.last_name', 'Bennani')
            );
    }

    public function test_index_filters_by_phone(): void
    {
        Customer::factory()->create(['phone' => '+212611111111']);
        Customer::factory()->create(['phone' => '+212622222222']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => '611111111']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.phone', '+212611111111')
            );
    }

    public function test_index_filters_by_email(): void
    {
        Customer::factory()->create(['email' => 'youssef@example.com']);
        Customer::factory()->create(['email' => 'sara@example.com']);

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'youssef']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.email', 'youssef@example.com')
            );
    }

    public function test_index_returns_empty_when_no_match(): void
    {
        Customer::factory(2)->create();

        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'zzznomatch']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 0)
            );
    }

    // -------------------------------------------------------------------------
    // index — sort
    // -------------------------------------------------------------------------

    public function test_index_sorts_by_last_name_ascending_by_default(): void
    {
        Customer::factory()->create(['last_name' => 'Zouiten']);
        Customer::factory()->create(['last_name' => 'Alaoui']);
        Customer::factory()->create(['last_name' => 'Bennani']);

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.last_name', 'Alaoui')
                ->where('customers.data.1.last_name', 'Bennani')
                ->where('customers.data.2.last_name', 'Zouiten')
            );
    }

    public function test_index_sort_direction_defaults_to_ascending(): void
    {
        // No direction param in request → filters.direction is absent (not sent)
        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->missing('filters.direction')
            );
    }

    public function test_index_rejects_unknown_sort_field(): void
    {
        Customer::factory()->create(['last_name' => 'Zouiten']);
        Customer::factory()->create(['last_name' => 'Alaoui']);

        // Unknown sort field falls back to last_name ASC
        $this->actingAs($this->employee)
            ->get(route('customers.index', ['sort' => 'injected_field']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('customers.data.0.last_name', 'Alaoui')
            );
    }

    // -------------------------------------------------------------------------
    // index — pagination
    // -------------------------------------------------------------------------

    public function test_index_paginates_20_per_page(): void
    {
        Customer::factory(25)->create();

        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('customers.data', 20)
                ->where('customers.total', 25)
            );
    }

    // -------------------------------------------------------------------------
    // index — can prop
    // -------------------------------------------------------------------------

    public function test_index_exposes_can_delete_false_for_employee(): void
    {
        $this->actingAs($this->employee)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('can.delete', false)
            );
    }

    public function test_index_exposes_can_delete_true_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('can.delete', true)
            );
    }

    // -------------------------------------------------------------------------
    // index — filters echoed back
    // -------------------------------------------------------------------------

    public function test_index_echoes_filters_back_to_frontend(): void
    {
        $this->actingAs($this->employee)
            ->get(route('customers.index', ['search' => 'Omar', 'sort' => 'email', 'direction' => 'desc']))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('filters.search', 'Omar')
                ->where('filters.sort', 'email')
                ->where('filters.direction', 'desc')
            );
    }

    // -------------------------------------------------------------------------
    // show — eager-loads relationships
    // -------------------------------------------------------------------------

    public function test_show_loads_customer_with_relationships(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->get(route('customers.show', $customer))
            ->assertInertia(
                fn (Assert $page) => $page
                ->component('Customers/Show')
                ->has('customer.vehicles')
                ->has('customer.invoices')
                ->has('customer.appointments')
            );
    }

    // -------------------------------------------------------------------------
    // destroy — authorization
    // -------------------------------------------------------------------------

    public function test_employee_cannot_delete_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($this->employee)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
