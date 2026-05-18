<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppChannelContract;
use App\Enums\ReminderType;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use App\Services\Notifications\MetaWhatsAppChannel;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWhatsAppChannelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeChannel(): MetaWhatsAppChannel
    {
        return new MetaWhatsAppChannel(
            phoneNumberId: 'test-phone-id',
            accessToken: 'test-token',
        );
    }

    private function makeReminder(array $overrides = []): Reminder
    {
        $customer = Customer::factory()->create(['phone' => '+212600000001']);
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        return Reminder::factory()->create(array_merge([
            'vehicle_id'  => $vehicle->id,
            'customer_id' => $customer->id,
        ], $overrides));
    }

    public function test_successful_send_returns_meta_message_id(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.abc123']],
            ], 200),
        ]);

        $reminder  = $this->makeReminder(['type' => ReminderType::Vidange]);
        $messageId = $this->makeChannel()->send($reminder);

        $this->assertEquals('wamid.abc123', $messageId);
    }

    public function test_send_posts_to_meta_graph_api_with_correct_payload(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.xyz']],
            ], 200),
        ]);

        $reminder = $this->makeReminder(['type' => ReminderType::Vidange]);
        $this->makeChannel()->send($reminder);

        Http::assertSent(function ($request) use ($reminder) {
            return str_contains($request->url(), 'test-phone-id/messages')
                && $request->data()['messaging_product'] === 'whatsapp'
                && $request->data()['to'] === $reminder->customer->phone
                && $request->data()['template']['name'] === 'vidange_reminder_fr';
        });
    }

    public function test_vidange_type_uses_vidange_template(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $reminder = $this->makeReminder(['type' => ReminderType::Vidange]);
        $this->makeChannel()->send($reminder);

        Http::assertSent(fn ($r) => $r->data()['template']['name'] === 'vidange_reminder_fr');
    }

    public function test_visite_technique_type_uses_visite_technique_template(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $reminder = $this->makeReminder(['type' => ReminderType::VisiteTechnique]);
        $this->makeChannel()->send($reminder);

        Http::assertSent(fn ($r) => $r->data()['template']['name'] === 'visite_technique_reminder_fr');
    }

    public function test_entretien_periodique_type_uses_entretien_periodique_template(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $reminder = $this->makeReminder(['type' => ReminderType::EntretienPeriodique]);
        $this->makeChannel()->send($reminder);

        Http::assertSent(fn ($r) => $r->data()['template']['name'] === 'entretien_periodique_reminder_fr');
    }

    public function test_failed_send_stores_meta_error_code_on_reminder(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['code' => 131047, 'message' => 'Recipient not in allowed list'],
            ], 400),
        ]);

        $reminder = $this->makeReminder();
        $result   = $this->makeChannel()->send($reminder);

        $this->assertNull($result);
        $this->assertEquals('131047', $reminder->fresh()->error_message);
    }

    public function test_failed_send_stores_http_status_when_no_error_code(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([], 503),
        ]);

        $reminder = $this->makeReminder();
        $this->makeChannel()->send($reminder);

        $this->assertEquals('503', $reminder->fresh()->error_message);
    }

    public function test_channel_is_bound_in_container(): void
    {
        $channel = app(WhatsAppChannelContract::class);

        $this->assertInstanceOf(MetaWhatsAppChannel::class, $channel);
    }
}
