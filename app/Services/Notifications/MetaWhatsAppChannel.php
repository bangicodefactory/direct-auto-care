<?php

namespace App\Services\Notifications;

use App\Contracts\WhatsAppChannelContract;
use App\Enums\ReminderType;
use App\Models\Reminder;
use Illuminate\Support\Facades\Http;

class MetaWhatsAppChannel implements WhatsAppChannelContract
{
    public function __construct(
        private readonly string $phoneNumberId,
        private readonly string $accessToken,
    ) {
    }

    public function send(Reminder $reminder): ?string
    {
        $reminder->loadMissing('customer');

        $response = Http::withToken($this->accessToken)
            ->post("https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => ltrim($reminder->customer->phone, '+'),
                'type'              => 'template',
                'template'          => [
                    'name'     => $this->templateName($reminder->type),
                    'language' => ['code' => 'fr'],
                ],
            ]);

        if ($response->failed()) {
            $reminder->update([
                'error_message' => (string) $response->json('error.code', $response->status()),
            ]);

            return null;
        }

        return $response->json('messages.0.id');
    }

    private function templateName(ReminderType $type): string
    {
        return match ($type) {
            ReminderType::Vidange             => 'vidange_reminder_fr',
            ReminderType::VisiteTechnique     => 'visite_technique_reminder_fr',
            ReminderType::EntretienPeriodique => 'entretien_periodique_reminder_fr',
        };
    }
}
