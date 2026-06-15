<?php

namespace Tests\Feature;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\ServiceOrderNumberingService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Model + factory
    // -------------------------------------------------------------------------

    public function test_factory_creates_an_order_in_reception(): void
    {
        $order = ServiceOrder::factory()->create();

        $this->assertSame(ServiceOrderStatus::Reception, $order->status);
        $this->assertNotNull($order->received_at);
        $this->assertDatabaseHas('service_orders', ['id' => $order->id]);
    }

    public function test_relations_resolve(): void
    {
        $order = ServiceOrder::factory()->create();

        $this->assertNotNull($order->customer);
        $this->assertNotNull($order->vehicle);
        $this->assertNotNull($order->createdBy);
        $this->assertTrue($order->items()->exists() === false);
    }

    public function test_active_scope_excludes_closed_and_cancelled(): void
    {
        ServiceOrder::factory()->create(); // reception → active
        ServiceOrder::factory()->closed()->create();
        ServiceOrder::factory()->create(['status' => ServiceOrderStatus::Cancelled->value]);

        $active = ServiceOrder::query()->active()->get();

        $this->assertCount(1, $active);
        $this->assertSame(ServiceOrderStatus::Reception, $active->first()->status);
    }

    // -------------------------------------------------------------------------
    // Numbering
    // -------------------------------------------------------------------------

    public function test_numbering_is_sequential_within_a_fiscal_year(): void
    {
        $service = app(ServiceOrderNumberingService::class);

        $this->assertSame('OS-2026-00001', $service->next(2026));
        $this->assertSame('OS-2026-00002', $service->next(2026));
        $this->assertSame('OS-2026-00003', $service->next(2026));
    }

    public function test_numbering_resets_per_fiscal_year(): void
    {
        $service = app(ServiceOrderNumberingService::class);

        $this->assertSame('OS-2026-00001', $service->next(2026));
        $this->assertSame('OS-2027-00001', $service->next(2027));
        $this->assertSame('OS-2026-00002', $service->next(2026));
    }

    // -------------------------------------------------------------------------
    // Policy
    // -------------------------------------------------------------------------

    public function test_employee_can_view_but_not_delete(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $order = ServiceOrder::factory()->create();

        $this->assertTrue($employee->can('viewAny', ServiceOrder::class));
        $this->assertTrue($employee->can('view', $order));
        $this->assertTrue($employee->can('create', ServiceOrder::class));
        $this->assertTrue($employee->can('update', $order));
        $this->assertFalse($employee->can('delete', $order));
    }

    public function test_admin_can_delete(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $order = ServiceOrder::factory()->create();

        $this->assertTrue($admin->can('delete', $order));
        $this->assertTrue($admin->can('restore', $order));
        $this->assertTrue($admin->can('forceDelete', $order));
    }

    public function test_user_without_role_cannot_view(): void
    {
        $user  = User::factory()->create();
        $order = ServiceOrder::factory()->create();

        $this->assertFalse($user->can('view', $order));
    }
}
