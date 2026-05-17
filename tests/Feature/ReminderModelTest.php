<?php

namespace Tests\Feature;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReminderModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_factory_creates_reminder_with_all_required_fields(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertNotNull($reminder->vehicle_id);
        $this->assertNotNull($reminder->customer_id);
        $this->assertNotNull($reminder->scheduled_for);
        $this->assertInstanceOf(ReminderType::class, $reminder->type);
        $this->assertInstanceOf(ReminderStatus::class, $reminder->status);
    }

    public function test_factory_links_customer_to_vehicle_owner(): void
    {
        $reminder = Reminder::factory()->create();
        $vehicle  = Vehicle::find($reminder->vehicle_id);

        $this->assertEquals($vehicle->customer_id, $reminder->customer_id);
    }

    public function test_type_cast_returns_reminder_type_enum(): void
    {
        $reminder = Reminder::factory()->create(['type' => ReminderType::Vidange]);

        $this->assertSame(ReminderType::Vidange, $reminder->fresh()->type);
    }

    public function test_status_cast_returns_reminder_status_enum(): void
    {
        $reminder = Reminder::factory()->create(['status' => ReminderStatus::Pending]);

        $this->assertSame(ReminderStatus::Pending, $reminder->fresh()->status);
    }

    public function test_scheduled_for_is_cast_to_carbon(): void
    {
        $reminder = Reminder::factory()->create(['scheduled_for' => '2026-06-01']);

        $this->assertInstanceOf(Carbon::class, $reminder->fresh()->scheduled_for);
        $this->assertEquals('2026-06-01', $reminder->fresh()->scheduled_for->toDateString());
    }

    public function test_sent_at_is_cast_to_carbon_when_set(): void
    {
        $reminder = Reminder::factory()->sent()->create();

        $this->assertInstanceOf(Carbon::class, $reminder->fresh()->sent_at);
    }

    public function test_factory_sent_state_sets_status_and_sent_at(): void
    {
        $reminder = Reminder::factory()->sent()->create();

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->sent_at);
    }

    public function test_factory_vidange_state_sets_type_and_trigger_km(): void
    {
        $reminder = Reminder::factory()->vidange()->create();

        $this->assertSame(ReminderType::Vidange, $reminder->fresh()->type);
        $this->assertNotNull($reminder->fresh()->trigger_km);
    }

    public function test_factory_visite_technique_state_clears_trigger_km(): void
    {
        $reminder = Reminder::factory()->visiteTechnique()->create();

        $this->assertSame(ReminderType::VisiteTechnique, $reminder->fresh()->type);
        $this->assertNull($reminder->fresh()->trigger_km);
    }

    public function test_factory_entretien_periodique_state_clears_trigger_km(): void
    {
        $reminder = Reminder::factory()->entretienPeriodique()->create();

        $this->assertSame(ReminderType::EntretienPeriodique, $reminder->fresh()->type);
        $this->assertNull($reminder->fresh()->trigger_km);
    }

    public function test_vehicle_relationship_resolves(): void
    {
        $vehicle  = Vehicle::factory()->create();
        $reminder = Reminder::factory()->create([
            'vehicle_id'  => $vehicle->id,
            'customer_id' => $vehicle->customer_id,
        ]);

        $this->assertEquals($vehicle->id, $reminder->vehicle->id);
    }

    public function test_customer_relationship_resolves(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $reminder = Reminder::factory()->create([
            'vehicle_id'  => $vehicle->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertEquals($customer->id, $reminder->customer->id);
    }

    public function test_trigger_km_is_null_for_non_vidange_types(): void
    {
        $reminder = Reminder::factory()->visiteTechnique()->create();

        $this->assertNull($reminder->fresh()->trigger_km);
    }

    public function test_unique_constraint_prevents_duplicate_vehicle_type_date(): void
    {
        $vehicle = Vehicle::factory()->create();

        Reminder::factory()->create([
            'vehicle_id'    => $vehicle->id,
            'customer_id'   => $vehicle->customer_id,
            'type'          => ReminderType::Vidange,
            'scheduled_for' => '2026-07-01',
        ]);

        $this->expectException(QueryException::class);

        Reminder::factory()->create([
            'vehicle_id'    => $vehicle->id,
            'customer_id'   => $vehicle->customer_id,
            'type'          => ReminderType::Vidange,
            'scheduled_for' => '2026-07-01',
        ]);
    }
}
