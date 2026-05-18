<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderJob;
use App\Services\ReminderService;
use Illuminate\Console\Command;

class DispatchRemindersCommand extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Dispatch due reminder notifications via the queue';

    public function handle(ReminderService $service): int
    {
        $due = $service->findDueReminders();

        foreach ($due as $reminder) {
            SendReminderJob::dispatch($reminder);
        }

        $this->info("Dispatched {$due->count()} reminder(s).");

        return self::SUCCESS;
    }
}
