<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderStoreTest extends TestCase
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

    public function test_walk_in_creates_an_order_in_reception_and_redirects(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->employee())->post(route('workshop.store'), [
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'mileage_in'  => 84500,
        ]);

        $order = \App\Models\ServiceOrder::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('workshop.show', $order));

        $this->assertSame(ServiceOrderStatus::Reception, $order->status);
        $this->assertStringStartsWith('OS-', $order->number);
        $this->assertSame(84500, $order->mileage_in);
        $this->assertNotNull($order->received_at);
    }

    public function test_linked_appointment_is_marked_completed(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'status'      => AppointmentStatus::Confirmed->value,
        ]);

        $this->actingAs($this->employee())->post(route('workshop.store'), [
            'customer_id'    => $customer->id,
            'vehicle_id'     => $vehicle->id,
            'appointment_id' => $appointment->id,
        ])->assertRedirect();

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_a_non_confirmed_linked_appointment_is_not_completed(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
            'status'      => AppointmentStatus::Cancelled->value,
        ]);

        $this->actingAs($this->employee())->post(route('workshop.store'), [
            'customer_id'    => $customer->id,
            'vehicle_id'     => $vehicle->id,
            'appointment_id' => $appointment->id,
        ])->assertRedirect();

        // The cancelled appointment must stay cancelled.
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
    }

    public function test_vehicle_must_belong_to_the_customer(): void
    {
        $customer = Customer::factory()->create();
        $other    = Customer::factory()->create();
        $foreignVehicle = Vehicle::factory()->create(['customer_id' => $other->id]);

        $this->actingAs($this->employee())->post(route('workshop.store'), [
            'customer_id' => $customer->id,
            'vehicle_id'  => $foreignVehicle->id,
        ])->assertSessionHasErrors('vehicle_id');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_reception_condition_is_persisted_as_a_map(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())->post(route('workshop.store'), [
            'customer_id'         => $customer->id,
            'vehicle_id'          => $vehicle->id,
            'reception_condition' => [
                'FRONT_BONNET'         => '#FF1A1A',
                'LEFT_SIDE_DRIVER_DOOR' => '#E65100',
            ],
        ])->assertRedirect();

        $order = \App\Models\ServiceOrder::first();
        $this->assertSame('#FF1A1A', $order->reception_condition['FRONT_BONNET']);
        $this->assertSame('#E65100', $order->reception_condition['LEFT_SIDE_DRIVER_DOOR']);
    }

    public function test_invalid_condition_key_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())->post(route('workshop.store'), [
            'customer_id'         => $customer->id,
            'vehicle_id'          => $vehicle->id,
            'reception_condition' => ['not a valid part!' => '#FFFFFF'],
        ])->assertSessionHasErrors('reception_condition');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_invalid_condition_colour_is_rejected(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($this->employee())->post(route('workshop.store'), [
            'customer_id'         => $customer->id,
            'vehicle_id'          => $vehicle->id,
            'reception_condition' => ['FRONT_BONNET' => 'red'],
        ])->assertSessionHasErrors('reception_condition.FRONT_BONNET');
    }

    public function test_guest_cannot_access_reception_form(): void
    {
        $this->get(route('workshop.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_role_cannot_create(): void
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs(User::factory()->create())->post(route('workshop.store'), [
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
        ])->assertForbidden();
    }
}
