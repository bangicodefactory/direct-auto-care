<?php

namespace App\Services\Notifications;

use App\Contracts\WhatsAppChannelContract;
use App\Enums\ReminderType;
use App\Models\Reminder;
use Illuminate\Support\Facades\Http;

class MetaWhatsAppChannel implements WhatsAppChannelContract
{
    /**
     * @param  string|null  $redirectTo  When set (e.g. on staging), every message is
     *                                    delivered to this number instead of the
     *                                    customer's real phone. Leave null in production.
     */
    public function __construct(
        private readonly string $phoneNumberId,
        private readonly string $accessToken,
        private readonly ?string $redirectTo = null,
    ) {
    }

    public function send(Reminder $reminder): ?string
    {
        $reminder->loadMissing('customer', 'vehicle');

        $http = Http::withToken($this->accessToken);

        // Windows local dev: cURL cannot verify the Meta CA chain without a configured bundle.
        if (app()->isLocal()) {
            $http = $http->withoutVerifying();
        }

        $recipient = $this->redirectTo ?: $reminder->customer->phone;

        $response = $http->post("https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to'                => ltrim($recipient, '+'),
            'type'              => 'template',
            'template'          => [
                'name'       => $this->templateName($reminder->type),
                'language'   => ['code' => 'fr'],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $reminder->customer->first_name],
                            ['type' => 'text', 'text' => $reminder->vehicle->license_plate],
                        ],
                    ],
                ],
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
