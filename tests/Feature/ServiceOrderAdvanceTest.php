<?php

namespace Tests\Feature;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderAdvanceTest extends TestCase
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

    public function test_advance_moves_to_the_next_phase_and_stamps_it(): void
    {
        $order = ServiceOrder::factory()->create([
            'status'       => ServiceOrderStatus::Reception->value,
            'diagnosed_at' => null,
        ]);

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(ServiceOrderStatus::Diagnosis, $order->status);
        $this->assertNotNull($order->diagnosed_at);
    }

    public function test_advance_chains_through_each_phase(): void
    {
        $order = ServiceOrder::factory()->create(['status' => ServiceOrderStatus::Reception->value]);
        $employee = $this->employee();

        $expected = [
            ServiceOrderStatus::Diagnosis,
            ServiceOrderStatus::QuoteSent,
            ServiceOrderStatus::InProgress,
            ServiceOrderStatus::QualityControl,
            ServiceOrderStatus::Notified,
            ServiceOrderStatus::ReadyPickup,
            ServiceOrderStatus::Closed,
        ];

        foreach ($expected as $status) {
            $this->actingAs($employee)->patch(route('workshop.advance', $order));
            $this->assertSame($status, $order->refresh()->status);
        }
    }

    public function test_advance_on_a_closed_order_is_rejected(): void
    {
        $order = ServiceOrder::factory()->closed()->create();

        $this->actingAs($this->employee())
            ->patch(route('workshop.advance', $order))
            ->assertStatus(422);

        $this->assertSame(ServiceOrderStatus::Closed, $order->refresh()->status);
    }

    public function test_cancel_sets_status_and_timestamp(): void
    {
        $order = ServiceOrder::factory()->create(['status' => ServiceOrderStatus::InProgress->value]);

        $this->actingAs($this->employee())
            ->patch(route('workshop.cancel', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(ServiceOrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_update_saves_notes_and_mileage(): void
    {
        $order = ServiceOrder::factory()->create(['status' => ServiceOrderStatus::Diagnosis->value]);

        $this->actingAs($this->employee())
            ->put(route('workshop.update', $order), [
                'mileage_in'      => 91000,
                'diagnosis_notes' => 'Disques à remplacer.',
                'internal_notes'  => 'Client prévenu.',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(91000, $order->mileage_in);
        $this->assertSame('Disques à remplacer.', $order->diagnosis_notes);
        $this->assertSame('Client prévenu.', $order->internal_notes);
    }

    public function test_guest_cannot_advance(): void
    {
        $order = ServiceOrder::factory()->create();

        $this->patch(route('workshop.advance', $order))->assertRedirect(route('login'));
    }

    public function test_user_without_role_cannot_advance(): void
    {
        $order = ServiceOrder::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('workshop.advance', $order))
            ->assertForbidden();
    }
}
