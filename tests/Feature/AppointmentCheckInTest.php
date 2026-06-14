<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCheckInTest extends TestCase
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

    private function confirmedAppointment(): Appointment
    {
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        return Appointment::factory()->confirmed()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
        ]);
    }

    public function test_check_in_creates_a_service_order_and_completes_the_appointment(): void
    {
        $appointment = $this->confirmedAppointment();

        $this->actingAs($this->employee())
            ->post(route('appointments.check-in', $appointment))
            ->assertRedirect();

        $order = ServiceOrder::where('appointment_id', $appointment->id)->first();
        $this->assertNotNull($order);
        $this->assertSame($appointment->customer_id, $order->customer_id);
        $this->assertSame($appointment->vehicle_id, $order->vehicle_id);
        $this->assertSame(ServiceOrderStatus::Reception, $order->status);
        $this->assertStringStartsWith('OS-', $order->number);

        $this->assertSame(AppointmentStatus::Completed, $appointment->refresh()->status);
    }

    public function test_check_in_redirects_to_the_new_service_order(): void
    {
        $appointment = $this->confirmedAppointment();

        $response = $this->actingAs($this->employee())
            ->post(route('appointments.check-in', $appointment));

        $order = ServiceOrder::where('appointment_id', $appointment->id)->firstOrFail();
        $response->assertRedirect(route('workshop.show', $order));
    }

    public function test_a_non_confirmed_appointment_cannot_be_checked_in(): void
    {
        $customer    = Customer::factory()->create();
        $vehicle     = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $appointment = Appointment::factory()->pending()->create([
            'customer_id' => $customer->id,
            'vehicle_id'  => $vehicle->id,
        ]);

        $this->actingAs($this->employee())
            ->post(route('appointments.check-in', $appointment))
            ->assertStatus(422);

        $this->assertDatabaseMissing('service_orders', ['appointment_id' => $appointment->id]);
    }

    public function test_an_appointment_without_a_vehicle_cannot_be_checked_in(): void
    {
        $appointment = Appointment::factory()->confirmed()->create(['vehicle_id' => null]);

        $this->actingAs($this->employee())
            ->post(route('appointments.check-in', $appointment))
            ->assertStatus(422);

        $this->assertDatabaseMissing('service_orders', ['appointment_id' => $appointment->id]);
    }

    public function test_an_appointment_cannot_be_checked_in_twice(): void
    {
        $appointment = $this->confirmedAppointment();
        $employee    = $this->employee();

        $this->actingAs($employee)
            ->post(route('appointments.check-in', $appointment))
            ->assertRedirect();

        // The appointment is now Completed, so the second attempt is rejected.
        $this->actingAs($employee)
            ->post(route('appointments.check-in', $appointment))
            ->assertStatus(422);

        $this->assertSame(1, ServiceOrder::where('appointment_id', $appointment->id)->count());
    }

    public function test_guest_cannot_check_in(): void
    {
        $appointment = $this->confirmedAppointment();

        $this->post(route('appointments.check-in', $appointment))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_role_cannot_check_in(): void
    {
        $appointment = $this->confirmedAppointment();

        $this->actingAs(User::factory()->create())
            ->post(route('appointments.check-in', $appointment))
            ->assertForbidden();
    }
}
