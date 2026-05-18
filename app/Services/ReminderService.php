<?php

namespace App\Services;

use App\Contracts\WhatsAppChannelContract;
use App\Enums\ReminderStatus;
use App\Mail\ReminderMail;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class ReminderService
{
    public function __construct(
        private readonly WhatsAppChannelContract $whatsApp,
    ) {
    }

    /**
     * @return Collection<int, Reminder>
     */
    public function findDueReminders(): Collection
    {
        return Reminder::with(['customer', 'vehicle'])
            ->where('status', ReminderStatus::Pending)
            ->whereDate('scheduled_for', '<=', today())
            ->get();
    }

    public function dispatch(Reminder $reminder): void
    {
        $reminder->loadMissing(['customer', 'vehicle']);

        $customer = $reminder->customer;
        $sent     = false;

        if ($customer->notify_whatsapp) {
            $messageId = $this->whatsApp->send($reminder);

            if ($messageId !== null) {
                $sent = true;
            }
        }

        if ($customer->notify_email && $customer->email) {
            try {
                Mail::to($customer->email)->send(new ReminderMail($reminder));
                $sent = true;
            } catch (\Throwable $e) {
                $reminder->update(['error_message' => 'email: ' . $e->getMessage()]);
            }
        }

        $noChannels = ! $customer->notify_whatsapp && ! $customer->notify_email;

        if ($sent || $noChannels) {
            $reminder->update([
                'status'        => ReminderStatus::Sent,
                'sent_at'       => now(),
                'error_message' => null,
            ]);
        }
    }
}
