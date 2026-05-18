<?php

namespace App\Jobs;

use App\Models\Reminder;
use App\Services\ReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
}
