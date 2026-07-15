<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;

class NewAppointmentNotification extends Notification
{
    public function __construct(private readonly Appointment $appointment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $customer = $this->appointment->customer;

        return [
            'type'    => 'new_appointment',
            'message' => "Nouveau rendez-vous\u{00A0}: {$customer->display_name} le {$this->appointment->scheduled_at->format('d/m/Y à H:i')}",
            'url'     => route('appointments.show', $this->appointment),
        ];
    }
}
