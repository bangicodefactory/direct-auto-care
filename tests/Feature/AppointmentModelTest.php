<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IST-58 — Smoke tests for the Appointment model, factory, and pivot.
 */
class AppointmentModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_factory_creates_appointment_with_all_required_fields(): void
    {
        $appointment = Appointment::factory()->create();

        $this->assertNotNull($appointment->customer_id);
        $this->assertNotNull($appointment->created_by);
        $this->assertNotNull($appointment->scheduled_at);
        $this->assertNotNull($appointment->duration);
        $this->assertInstanceOf(AppointmentStatus::class, $appointment->status);
    }

    public function test_scheduled_at_is_cast_to_carbon(): void
    {
        $appointment = Appointment::factory()->create([
            'scheduled_at' => '2026-06-15 09:00:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $appointment->fresh()->scheduled_at);
        $this->assertEquals('2026-06-15 09:00:00', $appointment->fresh()->scheduled_at->toDateTimeString());
    }

    public function test_status_is_cast_to_appointment_status_enum(): void
    {
        $appointment = Appointment::factory()->pending()->create();

        $this->assertSame(AppointmentStatus::Pending, $appointment->fresh()->status);
    }

    public function test_factory_status_states_produce_correct_values(): void
    {
        $this->assertSame(AppointmentStatus::Pending, Appointment::factory()->pending()->make()->status);
        $this->assertSame(AppointmentStatus::Confirmed, Appointment::factory()->confirmed()->make()->status);
        $this->assertSame(AppointmentStatus::Completed, Appointment::factory()->completed()->make()->status);
        $this->assertSame(AppointmentStatus::Cancelled, Appointment::factory()->cancelled()->make()->status);
    }

    public function test_factory_today_state_schedules_within_business_hours(): void
    {
        $appointment = Appointment::factory()->today()->make();

        $hour = (int) $appointment->scheduled_at->format('H');
        $this->assertGreaterThanOrEqual(8, $hour);
        $this->assertLessThanOrEqual(16, $hour);
        $this->assertEquals(today()->toDateString(), $appointment->scheduled_at->toDateString());
    }

    public function test_service_types_relationship_attaches_and_retrieves(): void
    {
        $appointment  = Appointment::factory()->create();
        $serviceType1 = ServiceType::create(['name' => 'Vidange']);
        $serviceType2 = ServiceType::create(['name' => 'Freins']);

        $appointment->serviceTypes()->attach([$serviceType1->id, $serviceType2->id]);

        $loaded = $appointment->fresh()->serviceTypes;
        $this->assertCount(2, $loaded);
        $this->assertTrue($loaded->contains('name', 'Vidange'));
        $this->assertTrue($loaded->contains('name', 'Freins'));
    }

    public function test_customer_relationship_resolves(): void
    {
        $customer    = Customer::factory()->create();
        $appointment = Appointment::factory()->create(['customer_id' => $customer->id]);

        $this->assertEquals($customer->id, $appointment->customer->id);
    }

    public function test_vehicle_relationship_resolves_when_set(): void
    {
        $customer    = Customer::factory()->create();
        $vehicle     = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
        ]);

        $this->assertEquals($vehicle->id, $appointment->vehicle->id);
    }

    public function test_vehicle_is_nullable(): void
    {
        $appointment = Appointment::factory()->create(['vehicle_id' => null]);

        $this->assertNull($appointment->fresh()->vehicle_id);
        $this->assertNull($appointment->vehicle);
    }

    public function test_created_by_relationship_resolves(): void
    {
        $user        = User::factory()->create();
        $appointment = Appointment::factory()->create(['created_by' => $user->id]);

        $this->assertEquals($user->id, $appointment->createdBy->id);
    }

    public function test_appointment_is_soft_deleted(): void
    {
        $appointment = Appointment::factory()->create();
        $appointment->delete();

        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
        $this->assertNull(Appointment::find($appointment->id));
        $this->assertNotNull(Appointment::withTrashed()->find($appointment->id));
    }
}
