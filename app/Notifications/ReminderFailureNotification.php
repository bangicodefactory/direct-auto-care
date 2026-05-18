<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Notifications\Notification;

class ReminderFailureNotification extends Notification
{
    public function __construct(private readonly Reminder $reminder)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $customer = $this->reminder->appointment->customer;

        return [
            'type'    => 'reminder_failure',
            'message' => "Échec du rappel pour {$customer->first_name} {$customer->last_name}",
            'url'     => route('reminders.index'),
        ];
    }
}
