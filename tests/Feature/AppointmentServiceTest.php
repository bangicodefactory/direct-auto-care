<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentOverlapException;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\AppointmentCancellationNotification;
use App\Notifications\AppointmentConfirmationNotification;
use App\Services\AppointmentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * IST-60 — Smoke tests for AppointmentService: overlap validation + auto-confirmation.
 */
class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->service = app(AppointmentService::class);
    }

    // =========================================================================
    // validateNoOverlap
    // =========================================================================

    public function test_no_overlap_when_no_appointments_exist(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->service->validateNoOverlap(
            $vehicle->id,
            now()->setTime(9, 0),
            60,
        );

        $this->assertTrue(true); // No exception = pass.
    }

    public function test_throws_when_appointment_overlaps(): void
    {
        $vehicle = Vehicle::factory()->create();
        Appointment::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => now()->setTime(9, 0),
            'duration'     => 60,
            'status'       => AppointmentStatus::Confirmed,
        ]);

        $this->expectException(AppointmentOverlapException::class);

        // New slot 09:30–10:30 overlaps with 09:00–10:00.
        $this->service->validateNoOverlap(
            $vehicle->id,
            now()->setTime(9, 30),
            60,
        );
    }

    public function test_no_overlap_for_adjacent_slots(): void
    {
        $vehicle = Vehicle::factory()->create();
        Appointment::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => now()->setTime(9, 0),
            'duration'     => 60,
            'status'       => AppointmentStatus::Confirmed,
        ]);

        // 10:00–11:00 touches but does not overlap 09:00–10:00.
        $this->service->validateNoOverlap(
            $vehicle->id,
            now()->setTime(10, 0),
            60,
        );

        $this->assertTrue(true);
    }

    public function test_cancelled_appointments_do_not_block_the_slot(): void
    {
        $vehicle = Vehicle::factory()->create();
        Appointment::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => now()->setTime(9, 0),
            'duration'     => 60,
            'status'       => AppointmentStatus::Cancelled,
        ]);

        $this->service->validateNoOverlap(
            $vehicle->id,
            now()->setTime(9, 0),
            60,
        );

        $this->assertTrue(true);
    }

    public function test_exclude_id_skips_the_specified_appointment(): void
    {
        $vehicle     = Vehicle::factory()->create();
        $appointment = Appointment::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => now()->setTime(9, 0),
            'duration'     => 60,
            'status'       => AppointmentStatus::Confirmed,
        ]);

        // Updating the same appointment: its own slot must not count as a conflict.
        $this->service->validateNoOverlap(
            $vehicle->id,
            now()->setTime(9, 0),
            60,
            $appointment->id,
        );

        $this->assertTrue(true);
    }

    // =========================================================================
    // create
    // =========================================================================

    public function test_create_returns_confirmed_appointment(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $user     = User::factory()->create();

        $appointment = $this->service->create([
            'customer_id'  => $customer->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
        ], $user);

        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($user->id, $appointment->created_by);
    }

    public function test_create_attaches_service_types(): void
    {
        Notification::fake();

        $customer     = Customer::factory()->create();
        $user         = User::factory()->create();
        $serviceType1 = ServiceType::create(['name' => 'Vidange']);
        $serviceType2 = ServiceType::create(['name' => 'Freins']);

        $appointment = $this->service->create([
            'customer_id'      => $customer->id,
            'scheduled_at'     => '2026-07-01 10:00:00',
            'duration'         => 60,
            'service_type_ids' => [$serviceType1->id, $serviceType2->id],
        ], $user);

        $this->assertCount(2, $appointment->fresh()->serviceTypes);
    }

    public function test_create_sends_confirmation_notification_when_customer_has_email(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create([
            'email'           => 'client@example.com',
            'notify_email'    => true,
            'notify_whatsapp' => false,
        ]);
        $user = User::factory()->create();

        $this->service->create([
            'customer_id'  => $customer->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
        ], $user);

        Notification::assertSentOnDemand(
            AppointmentConfirmationNotification::class,
            function ($notification, $channels, $notifiable) use ($customer) {
                $mail = $notification->toMail($notifiable);

                return str_contains($mail->greeting, $customer->first_name)
                    && str_contains($mail->greeting, $customer->last_name)
                    && collect($mail->introLines)->contains(fn ($l) => str_contains($l, '01/07/2026'))
                    && collect($mail->introLines)->contains(fn ($l) => str_contains($l, '10:00'));
            }
        );
    }

    public function test_create_sends_vehicle_matricule_in_notification_when_vehicle_is_set(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create([
            'email'        => 'client@example.com',
            'notify_email' => true,
        ]);
        $vehicle = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $user    = User::factory()->create();

        $this->service->create([
            'customer_id'  => $customer->id,
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
        ], $user);

        Notification::assertSentOnDemand(
            AppointmentConfirmationNotification::class,
            function ($notification, $channels, $notifiable) use ($vehicle) {
                $mail = $notification->toMail($notifiable);

                return collect($mail->introLines)->contains(
                    fn ($l) => str_contains($l, $vehicle->license_plate)
                );
            }
        );
    }

    public function test_create_skips_notification_when_customer_has_notify_email_false(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create([
            'email'        => 'client@example.com',
            'notify_email' => false,
        ]);
        $user = User::factory()->create();

        $this->service->create([
            'customer_id'  => $customer->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
        ], $user);

        Notification::assertNothingSent();
    }

    public function test_create_skips_notification_when_customer_has_no_email(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create([
            'email'        => null,
            'notify_email' => true,
        ]);
        $user = User::factory()->create();

        $this->service->create([
            'customer_id'  => $customer->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
        ], $user);

        Notification::assertNothingSent();
    }

    public function test_create_throws_on_vehicle_overlap(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);
        $user     = User::factory()->create();

        Appointment::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
            'status'       => AppointmentStatus::Confirmed,
        ]);

        $this->expectException(AppointmentOverlapException::class);

        $this->service->create([
            'customer_id'  => $customer->id,
            'vehicle_id'   => $vehicle->id,
            'scheduled_at' => '2026-07-01 10:30:00',
            'duration'     => 60,
        ], $user);
    }

    public function test_create_skips_overlap_check_when_vehicle_id_is_null(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create();
        $user     = User::factory()->create();

        // Two appointments at the same time without a vehicle — must not throw.
        $this->service->create([
            'customer_id'  => $customer->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
        ], $user);

        $this->service->create([
            'customer_id'  => $customer->id,
            'scheduled_at' => '2026-07-01 10:00:00',
            'duration'     => 60,
        ], $user);

        $this->assertEquals(2, Appointment::count());
    }

    // =========================================================================
    // cancel
    // =========================================================================

    public function test_cancel_sets_status_to_cancelled(): void
    {
        Notification::fake();

        $appointment = Appointment::factory()->confirmed()->create();

        $result = $this->service->cancel($appointment, notify: false);

        $this->assertSame(AppointmentStatus::Cancelled, $result->status);
    }

    public function test_cancel_sends_cancellation_notification(): void
    {
        Notification::fake();

        $customer = Customer::factory()->create([
            'email'        => 'client@example.com',
            'notify_email' => true,
        ]);
        $appointment = Appointment::factory()->confirmed()->create([
            'customer_id' => $customer->id,
        ]);

        $this->service->cancel($appointment, notify: true);

        Notification::assertSentOnDemand(AppointmentCancellationNotification::class);
    }

    public function test_cancel_suppresses_notification_when_notify_is_false(): void
    {
        Notification::fake();

        $appointment = Appointment::factory()->confirmed()->create();

        $this->service->cancel($appointment, notify: false);

        Notification::assertNothingSent();
    }
}
