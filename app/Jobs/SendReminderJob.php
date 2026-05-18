<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderFailureNotification;
use App\Services\ReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendReminderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly Reminder $reminder)
    {
        $this->onQueue('reminders');
    }

    public function handle(ReminderService $service): void
    {
        $service->dispatch($this->reminder);
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(Throwable $e): void
    {
        $admins = User::role('admin')->get();
        Notification::send($admins, new ReminderFailureNotification($this->reminder));
    }
}
