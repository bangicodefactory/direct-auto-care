<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * IST-61 — Smoke tests for AppointmentPolicy + AppointmentsController.
 */
class AppointmentsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    // =========================================================================
    // Guests
    // =========================================================================

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('appointments.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('appointments.create'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_show(): void
    {
        $appointment = Appointment::factory()->create();
        $this->get(route('appointments.show', $appointment))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_today(): void
    {
        $this->get(route('appointments.today'))->assertRedirect(route('login'));
    }

    // =========================================================================
    // Employee — full access except delete
    // =========================================================================

    public function test_employee_can_access_index(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Appointments/Index'));
    }

    public function test_employee_can_access_create(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        $this->actingAs($employee)
            ->get(route('appointments.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Appointments/Create'));
    }

    public function test_employee_can_access_show(): void
    {
        $employee    = User::factory()->create()->assignRole('employee');
        $appointment = Appointment::factory()->create();

        $this->actingAs($employee)
            ->get(route('appointments.show', $appointment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointments/Show')
                ->where('appointment.id', $appointment->id));
    }

    public function test_employee_can_access_edit(): void
    {
        $employee    = User::factory()->create()->assignRole('employee');
        $appointment = Appointment::factory()->create();

        $this->actingAs($employee)
            ->get(route('appointments.edit', $appointment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Appointments/Edit'));
    }

    public function test_employee_can_store_appointment(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();

        $this->actingAs($employee)
            ->post(route('appointments.store'), [
                'customer_id'  => $customer->id,
                'scheduled_at' => '2026-08-01 09:00:00',
                'duration'     => 60,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'status'      => AppointmentStatus::Confirmed->value,
        ]);
    }

    public function test_store_returns_overlap_error_when_vehicle_has_conflict(): void
    {
        $employee = User::factory()->create()->assignRole('employee');
        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        Appointment::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => '2026-08-01 09:00:00',
            'duration'     => 60,
            'status'       => AppointmentStatus::Confirmed,
        ]);

        $this->actingAs($employee)
            ->post(route('appointments.store'), [
                'customer_id'  => $customer->id,
                'vehicle_id'   => $vehicle->id,
                'scheduled_at' => '2026-08-01 09:30:00',
                'duration'     => 60,
            ])
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_employee_can_update_appointment(): void
    {
        $employee    = User::factory()->create()->assignRole('employee');
        $customer    = Customer::factory()->create();
        $appointment = Appointment::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($employee)
            ->put(route('appointments.update', $appointment), [
                'customer_id'  => $customer->id,
                'scheduled_at' => '2026-09-01 14:00:00',
                'duration'     => 90,
            ])
            ->assertRedirect(route('appointments.show', $appointment));

        $this->assertEquals(90, $appointment->fresh()->duration);
    }

    public function test_update_returns_overlap_error_when_vehicle_has_conflict(): void
    {
        $employee    = User::factory()->create()->assignRole('employee');
        $customer    = Customer::factory()->create();
        $vehicle     = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $appointment = Appointment::factory()->create([
            'customer_id'  => $customer->id,
            'vehicle_id'   => null,
            'scheduled_at' => '2026-09-01 14:00:00',
            'duration'     => 60,
        ]);

        Appointment::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => '2026-09-01 14:00:00',
            'duration'     => 60,
            'status'       => AppointmentStatus::Confirmed,
        ]);

        $this->actingAs($employee)
            ->put(route('appointments.update', $appointment), [
                'customer_id'  => $customer->id,
                'vehicle_id'   => $vehicle->id,
                'scheduled_at' => '2026-09-01 14:30:00',
                'duration'     => 60,
            ])
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_employee_can_cancel_appointment(): void
    {
        $employee    = User::factory()->create()->assignRole('employee');
        $appointment = Appointment::factory()->confirmed()->create();

        $this->actingAs($employee)
            ->post(route('appointments.cancel', $appointment))
            ->assertRedirect(route('appointments.show', $appointment));

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
    }

    public function test_employee_cannot_delete_appointment(): void
    {
        $employee    = User::factory()->create()->assignRole('employee');
        $appointment = Appointment::factory()->create();

        $this->actingAs($employee)
            ->delete(route('appointments.destroy', $appointment))
            ->assertForbidden();
    }

    // =========================================================================
    // Admin — full access including delete
    // =========================================================================

    public function test_admin_can_delete_appointment(): void
    {
        $admin       = User::factory()->create()->assignRole('admin');
        $appointment = Appointment::factory()->create();

        $this->actingAs($admin)
            ->delete(route('appointments.destroy', $appointment))
            ->assertRedirect(route('appointments.index'));

        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    }

    // =========================================================================
    // today endpoint
    // =========================================================================

    public function test_today_returns_only_todays_appointments(): void
    {
        $employee = User::factory()->create()->assignRole('employee');

        Appointment::factory()->create(['scheduled_at' => today()->setTime(10, 0)]);
        Appointment::factory()->create(['scheduled_at' => today()->setTime(14, 0)]);
        Appointment::factory()->create(['scheduled_at' => today()->addDay()->setTime(10, 0)]);

        $response = $this->actingAs($employee)
            ->getJson(route('appointments.today'));

        $response->assertOk()->assertJsonCount(2);
    }

    public function test_today_response_includes_required_calendar_fields(): void
    {
        $employee    = User::factory()->create()->assignRole('employee');
        $appointment = Appointment::factory()->create([
            'scheduled_at' => today()->setTime(9, 0),
            'duration'     => 60,
        ]);

        $response = $this->actingAs($employee)
            ->getJson(route('appointments.today'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $appointment->id])
            ->assertJsonStructure([['id', 'start', 'end', 'duration', 'status', 'status_label', 'customer', 'vehicle', 'services']]);
    }
}
