<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppChannelContract;
use App\Enums\ReminderStatus;
use App\Mail\ReminderMail;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use App\Services\ReminderService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeService(?WhatsAppChannelContract $whatsApp = null): ReminderService
    {
        $whatsApp ??= $this->createMock(WhatsAppChannelContract::class);

        return new ReminderService(whatsApp: $whatsApp);
    }

    private function makeReminder(array $overrides = []): Reminder
    {
        $customer = Customer::factory()->create([
            'notify_whatsapp' => $overrides['notify_whatsapp'] ?? false,
            'notify_email'    => $overrides['notify_email'] ?? false,
            'email'           => $overrides['email'] ?? null,
        ]);
        $vehicle = Vehicle::factory()->create(['customer_id' => $customer->id]);

        $reminderAttrs = array_diff_key($overrides, array_flip([
            'notify_whatsapp', 'notify_email', 'email',
        ]));

        return Reminder::factory()->create(array_merge([
            'vehicle_id'  => $vehicle->id,
            'customer_id' => $customer->id,
        ], $reminderAttrs));
    }

    // =========================================================================
    // findDueReminders
    // =========================================================================

    public function test_find_due_reminders_returns_pending_reminders_due_today(): void
    {
        $this->makeReminder(['scheduled_for' => today()->toDateString()]);

        $due = $this->makeService()->findDueReminders();

        $this->assertCount(1, $due);
    }

    public function test_find_due_reminders_returns_overdue_reminders(): void
    {
        $this->makeReminder(['scheduled_for' => today()->subDays(3)->toDateString()]);

        $due = $this->makeService()->findDueReminders();

        $this->assertCount(1, $due);
    }

    public function test_find_due_reminders_excludes_future_reminders(): void
    {
        $this->makeReminder(['scheduled_for' => today()->addDay()->toDateString()]);

        $due = $this->makeService()->findDueReminders();

        $this->assertCount(0, $due);
    }

    public function test_find_due_reminders_excludes_sent_reminders(): void
    {
        $this->makeReminder([
            'scheduled_for' => today()->toDateString(),
            'status'        => ReminderStatus::Sent,
        ]);

        $due = $this->makeService()->findDueReminders();

        $this->assertCount(0, $due);
    }

    public function test_find_due_reminders_excludes_cancelled_reminders(): void
    {
        $this->makeReminder([
            'scheduled_for' => today()->toDateString(),
            'status'        => ReminderStatus::Cancelled,
        ]);

        $due = $this->makeService()->findDueReminders();

        $this->assertCount(0, $due);
    }

    public function test_find_due_reminders_eager_loads_customer_and_vehicle(): void
    {
        $this->makeReminder(['scheduled_for' => today()->toDateString()]);

        $due = $this->makeService()->findDueReminders();

        $this->assertTrue($due->first()->relationLoaded('customer'));
        $this->assertTrue($due->first()->relationLoaded('vehicle'));
    }

    // =========================================================================
    // dispatch — WhatsApp
    // =========================================================================

    public function test_dispatch_sends_whatsapp_when_notify_whatsapp_is_true(): void
    {
        $whatsApp = $this->createMock(WhatsAppChannelContract::class);
        $whatsApp->expects($this->once())->method('send')->willReturn('wamid.123');

        $reminder = $this->makeReminder(['notify_whatsapp' => true]);
        $this->makeService($whatsApp)->dispatch($reminder);
    }

    public function test_dispatch_skips_whatsapp_when_notify_whatsapp_is_false(): void
    {
        $whatsApp = $this->createMock(WhatsAppChannelContract::class);
        $whatsApp->expects($this->never())->method('send');

        $reminder = $this->makeReminder(['notify_whatsapp' => false]);
        $this->makeService($whatsApp)->dispatch($reminder);
    }

    // =========================================================================
    // dispatch — Email
    // =========================================================================

    public function test_dispatch_sends_email_when_notify_email_is_true(): void
    {
        Mail::fake();

        $reminder = $this->makeReminder([
            'notify_email' => true,
            'email'        => 'client@example.com',
        ]);

        $this->makeService()->dispatch($reminder);

        Mail::assertSent(ReminderMail::class);
    }

    public function test_dispatch_skips_email_when_notify_email_is_false(): void
    {
        Mail::fake();

        $reminder = $this->makeReminder(['notify_email' => false]);
        $this->makeService()->dispatch($reminder);

        Mail::assertNotSent(ReminderMail::class);
    }

    public function test_dispatch_skips_email_when_customer_has_no_email_address(): void
    {
        Mail::fake();

        $reminder = $this->makeReminder(['notify_email' => true, 'email' => null]);
        $this->makeService()->dispatch($reminder);

        Mail::assertNotSent(ReminderMail::class);
    }

    // =========================================================================
    // dispatch — status updates
    // =========================================================================

    public function test_dispatch_marks_reminder_sent_after_successful_whatsapp(): void
    {
        $whatsApp = $this->createMock(WhatsAppChannelContract::class);
        $whatsApp->method('send')->willReturn('wamid.ok');

        $reminder = $this->makeReminder(['notify_whatsapp' => true]);
        $this->makeService($whatsApp)->dispatch($reminder);

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->sent_at);
    }

    public function test_dispatch_marks_reminder_sent_after_successful_email(): void
    {
        Mail::fake();

        $reminder = $this->makeReminder([
            'notify_email' => true,
            'email'        => 'client@example.com',
        ]);
        $this->makeService()->dispatch($reminder);

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->sent_at);
    }

    public function test_dispatch_does_not_mark_sent_when_whatsapp_fails(): void
    {
        $whatsApp = $this->createMock(WhatsAppChannelContract::class);
        $whatsApp->method('send')->willReturn(null);

        $reminder = $this->makeReminder(['notify_whatsapp' => true]);
        $this->makeService($whatsApp)->dispatch($reminder);

        $this->assertSame(ReminderStatus::Pending, $reminder->fresh()->status);
        $this->assertNull($reminder->fresh()->sent_at);
    }

    public function test_dispatch_marks_sent_when_no_channels_are_configured(): void
    {
        $reminder = $this->makeReminder(['notify_whatsapp' => false, 'notify_email' => false]);
        $this->makeService()->dispatch($reminder);

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->sent_at);
    }

    public function test_dispatch_clears_error_message_on_success(): void
    {
        $whatsApp = $this->createMock(WhatsAppChannelContract::class);
        $whatsApp->method('send')->willReturn('wamid.ok');

        $reminder = $this->makeReminder(['notify_whatsapp' => true, 'error_message' => 'previous error']);
        $this->makeService($whatsApp)->dispatch($reminder);

        $this->assertNull($reminder->fresh()->error_message);
    }

    public function test_dispatch_stores_error_message_and_stays_pending_when_email_throws(): void
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection failed'));

        $reminder = $this->makeReminder([
            'notify_email' => true,
            'email'        => 'client@example.com',
        ]);

        $this->makeService()->dispatch($reminder);

        $this->assertSame(ReminderStatus::Pending, $reminder->fresh()->status);
        $this->assertNull($reminder->fresh()->sent_at);
        $this->assertStringContainsString('email:', $reminder->fresh()->error_message);
    }

    public function test_dispatch_marks_sent_when_both_channels_succeed(): void
    {
        Mail::fake();

        $whatsApp = $this->createMock(WhatsAppChannelContract::class);
        $whatsApp->method('send')->willReturn('wamid.ok');

        $reminder = $this->makeReminder([
            'notify_whatsapp' => true,
            'notify_email'    => true,
            'email'           => 'client@example.com',
        ]);

        $this->makeService($whatsApp)->dispatch($reminder);

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->sent_at);
        $this->assertNull($reminder->fresh()->error_message);
        Mail::assertSent(ReminderMail::class);
    }

    public function test_same_reminder_is_not_dispatched_twice(): void
    {
        $whatsApp = $this->createMock(WhatsAppChannelContract::class);
        $whatsApp->expects($this->once())->method('send')->willReturn('wamid.ok');

        $reminder = $this->makeReminder([
            'scheduled_for'   => today()->toDateString(),
            'notify_whatsapp' => true,
        ]);

        $service = $this->makeService($whatsApp);
        $service->dispatch($reminder);

        $due = $service->findDueReminders();
        $this->assertCount(0, $due);
    }
}
