<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ReminderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');
    }

    // -------------------------------------------------------------------------
    // Access
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
    }

    public function test_employee_can_access_dashboard(): void
    {
        $this->actingAs($this->employee)
            ->get(route('dashboard'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // Base KPIs (both roles)
    // -------------------------------------------------------------------------

    public function test_dashboard_exposes_base_kpi_keys(): void
    {
        $this->actingAs($this->employee)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('kpis.customers_total')
                ->has('kpis.customers_this_month')
                ->has('kpis.customers_delta')
                ->has('kpis.active_vehicles')
                ->has('kpis.pending_reminders')
                ->has('kpis.appointments_today')
                ->has('kpis.low_stock')
            );
    }

    public function test_customers_total_counts_non_deleted_customers(): void
    {
        Customer::factory(3)->create();

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('kpis.customers_total', 3)
            );
    }

    public function test_customers_delta_reflects_new_customers_this_month(): void
    {
        Customer::factory(2)->create(['created_at' => now()]);
        Customer::factory()->create(['created_at' => now()->subMonths(2)]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('kpis.customers_this_month', 2)
            );
    }

    public function test_active_vehicles_count(): void
    {
        $customer = Customer::factory()->create();
        Vehicle::factory(4)->create(['customer_id' => $customer->id]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('kpis.active_vehicles', 4)
            );
    }

    public function test_pending_reminders_count(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        Reminder::factory(2)->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'status'      => ReminderStatus::Pending,
        ]);
        Reminder::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'status'      => ReminderStatus::Sent,
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('kpis.pending_reminders', 2)
            );
    }

    public function test_low_stock_returns_products_below_threshold(): void
    {
        Product::factory()->create(['quantity_on_hand' => 2, 'low_stock_threshold' => 5]);
        Product::factory()->create(['quantity_on_hand' => 10, 'low_stock_threshold' => 5]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('kpis.low_stock', 1)
            );
    }

    public function test_appointments_today_excludes_cancelled(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        Appointment::factory()->create([
            'customer_id'  => $customer->id,
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => today()->setTime(10, 0),
            'status'       => AppointmentStatus::Confirmed,
        ]);
        Appointment::factory()->create([
            'customer_id'  => $customer->id,
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => today()->setTime(11, 0),
            'status'       => AppointmentStatus::Cancelled,
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('kpis.appointments_today', 1)
            );
    }

    // -------------------------------------------------------------------------
    // Admin-only KPIs
    // -------------------------------------------------------------------------

    public function test_admin_gets_revenue_and_chart_keys(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('isAdmin', true)
                ->has('kpis.revenue_month')
                ->has('kpis.revenue_year')
                ->has('kpis.revenue_last_year')
                ->has('kpis.top_services')
                ->has('kpis.monthly_stats')
            );
    }

    public function test_employee_does_not_get_revenue_keys(): void
    {
        $this->actingAs($this->employee)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('isAdmin', false)
                ->missing('kpis.revenue_month')
                ->missing('kpis.revenue_year')
                ->missing('kpis.revenue_last_year')
                ->missing('kpis.top_services')
                ->missing('kpis.monthly_stats')
            );
    }

    public function test_revenue_excludes_cancelled_invoices(): void
    {
        $customer = Customer::factory()->create();

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => 'invoice',
            'status'      => InvoiceStatus::Paid,
            'total_ttc'   => 1000,
            'issue_date'  => now(),
        ]);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => 'invoice',
            'status'      => InvoiceStatus::Cancelled,
            'total_ttc'   => 500,
            'issue_date'  => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('kpis.revenue_month', 1000)
            );
    }

    public function test_revenue_excludes_draft_invoices(): void
    {
        $customer = Customer::factory()->create();

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => 'invoice',
            'status'      => InvoiceStatus::Issued,
            'total_ttc'   => 800,
            'issue_date'  => now(),
        ]);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => 'invoice',
            'status'      => InvoiceStatus::Draft,
            'total_ttc'   => 200,
            'issue_date'  => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('kpis.revenue_month', 800)
            );
    }

    public function test_revenue_excludes_quotes(): void
    {
        $customer = Customer::factory()->create();

        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => 'invoice',
            'status'      => InvoiceStatus::Paid,
            'total_ttc'   => 600,
            'issue_date'  => now(),
        ]);
        Invoice::factory()->create([
            'customer_id' => $customer->id,
            'type'        => 'quote',
            'status'      => InvoiceStatus::Issued,
            'total_ttc'   => 400,
            'issue_date'  => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->where('kpis.revenue_month', 600)
            );
    }

    public function test_monthly_stats_returns_12_entries(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn (Assert $page) => $page
                ->has('kpis.monthly_stats', 12)
            );
    }

    // -------------------------------------------------------------------------
    // Caching — role-scoped keys
    // -------------------------------------------------------------------------

    public function test_admin_result_is_cached_under_admin_key(): void
    {
        Cache::spy();

        $this->actingAs($this->admin)->get(route('dashboard'));

        Cache::shouldHaveReceived('remember')
            ->once()
            ->withArgs(fn ($key) => $key === 'dashboard.admin');
    }

    public function test_employee_result_is_cached_under_employee_key(): void
    {
        Cache::spy();

        $this->actingAs($this->employee)->get(route('dashboard'));

        Cache::shouldHaveReceived('remember')
            ->once()
            ->withArgs(fn ($key) => $key === 'dashboard.employee');
    }
}
