<?php

namespace Tests\Feature;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ServiceOrderIndexTest extends TestCase
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

    public function test_board_lists_active_orders_and_excludes_closed_and_cancelled(): void
    {
        ServiceOrder::factory()->create(); // reception → active
        ServiceOrder::factory()->inProgress()->create(); // active
        ServiceOrder::factory()->closed()->create(); // excluded
        ServiceOrder::factory()->create(['status' => ServiceOrderStatus::Cancelled->value]); // excluded

        $this->actingAs($this->employee())
            ->get(route('workshop.index'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('Workshop/Index')
                    ->has('orders.data', 2)
                    ->where('total', 2)
                    ->has('statuses')
            );
    }

    public function test_status_filter_narrows_the_list(): void
    {
        ServiceOrder::factory()->create(); // reception
        ServiceOrder::factory()->inProgress()->create();

        $this->actingAs($this->employee())
            ->get(route('workshop.index', ['status' => ServiceOrderStatus::InProgress->value]))
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('orders.data', 1)
                    ->where('orders.data.0.status', ServiceOrderStatus::InProgress->value)
                    ->where('total', 2) // chip total is unfiltered
            );
    }

    public function test_search_matches_the_order_number(): void
    {
        $target = ServiceOrder::factory()->create(['number' => 'OS-2026-09999']);
        ServiceOrder::factory()->create(['number' => 'OS-2026-11111']);

        $this->actingAs($this->employee())
            ->get(route('workshop.index', ['q' => '09999']))
            ->assertInertia(
                fn (Assert $page) => $page
                    ->has('orders.data', 1)
                    ->where('orders.data.0.id', $target->id)
            );
    }

    public function test_status_chips_carry_active_phase_counts(): void
    {
        ServiceOrder::factory()->count(2)->create(); // 2 in Reception
        ServiceOrder::factory()->inProgress()->create(); // 1 in progress

        $this->actingAs($this->employee())
            ->get(route('workshop.index'))
            ->assertInertia(function (Assert $page) {
                $statuses = collect($page->toArray()['props']['statuses']);

                $this->assertSame(2, $statuses->firstWhere('value', ServiceOrderStatus::Reception->value)['count']);
                $this->assertSame(1, $statuses->firstWhere('value', ServiceOrderStatus::InProgress->value)['count']);
                // Closed/Cancelled are not offered as board filters.
                $this->assertNull($statuses->firstWhere('value', ServiceOrderStatus::Closed->value));
            });
    }

    public function test_employee_can_view_the_board(): void
    {
        $this->actingAs($this->employee())
            ->get(route('workshop.index'))
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('workshop.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_role_cannot_view_the_board(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('workshop.index'))
            ->assertForbidden();
    }
}
