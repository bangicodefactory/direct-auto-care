<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentOverlapException;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentCancellationNotification;
use App\Notifications\AppointmentConfirmationNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class AppointmentService
{
    /**
     * Assert that no non-cancelled appointment for the same vehicle overlaps the given slot.
     *
     * Overlap condition (half-open intervals, touches are not overlaps):
     *   existing.start < new.end  AND  existing.end > new.start
     *
     * Pass $excludeId when validating an update so the appointment being edited
     * is not counted as its own conflict.
     *
     * @throws AppointmentOverlapException
     */
    public function validateNoOverlap(
        int $vehicleId,
        Carbon $scheduledAt,
        int $duration,
        ?int $excludeId = null,
    ): void {
        $endsAt = $scheduledAt->copy()->addMinutes($duration);

        $overlaps = Appointment::query()
            ->where('vehicle_id', $vehicleId)
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('scheduled_at', '<', $endsAt)
            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration MINUTE) > ?', [$scheduledAt])
            ->exists();

        if ($overlaps) {
            throw new AppointmentOverlapException();
        }
    }

    /**
     * Create a confirmed appointment and send a confirmation notification to the customer.
     *
     * Passes $data keys: customer_id, scheduled_at, duration, [vehicle_id, service_type_ids, notes]
     * The appointment is always set to Confirmed (auto-confirmation).
     */
    public function create(array $data, User $user): Appointment
    {
        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $vehicleId   = isset($data['vehicle_id']) ? (int) $data['vehicle_id'] : null;

        if ($vehicleId) {
            $this->validateNoOverlap($vehicleId, $scheduledAt, (int) $data['duration']);
        }

        $appointment = Appointment::create([
            'customer_id'  => $data['customer_id'],
            'vehicle_id'   => $vehicleId,
            'created_by'   => $user->id,
            'scheduled_at' => $scheduledAt,
            'duration'     => (int) $data['duration'],
            'status'       => AppointmentStatus::Confirmed,
            'notes'        => $data['notes'] ?? null,
        ]);

        if (! empty($data['service_type_ids'])) {
            $appointment->serviceTypes()->attach($data['service_type_ids']);
        }

        $appointment->load('customer', 'vehicle');
        $this->notifyCustomer($appointment, new AppointmentConfirmationNotification($appointment));

        return $appointment;
    }

    /**
     * Cancel an appointment and optionally notify the customer.
     *
     * Pass $notify = false to suppress the customer notification (e.g. bulk operations).
     */
    public function cancel(Appointment $appointment, bool $notify = true): Appointment
    {
        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        if ($notify) {
            $appointment->loadMissing('customer', 'vehicle');
            $this->notifyCustomer($appointment, new AppointmentCancellationNotification($appointment));
        }

        return $appointment->fresh();
    }

    private function notifyCustomer(Appointment $appointment, object $notification): void
    {
        $customer = $appointment->customer;

        if ($customer->notify_email && $customer->email) {
            Notification::route('mail', [
                $customer->email => "{$customer->first_name} {$customer->last_name}",
            ])->notify($notification);
        }

        // WhatsApp channel — pending ADR-001 (Twilio).
    }
}
