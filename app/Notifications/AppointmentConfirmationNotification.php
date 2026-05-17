<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentConfirmationNotification extends Notification
{
    public function __construct(private readonly Appointment $appointment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appointment = $this->appointment;
        $customer    = $appointment->customer;
        $vehicle     = $appointment->vehicle;

        $mail = (new MailMessage())
            ->subject('Confirmation de votre rendez-vous — Direct Auto Care')
            ->greeting("Bonjour {$customer->first_name} {$customer->last_name},")
            ->line('Votre rendez-vous a été confirmé.')
            ->line('**Date :** ' . $appointment->scheduled_at->format('d/m/Y'))
            ->line('**Heure :** ' . $appointment->scheduled_at->format('H:i'));

        if ($vehicle) {
            $mail->line('**Véhicule :** ' . $vehicle->license_plate);
        }

        return $mail->salutation("L'équipe Direct Auto Care");
    }
}
