<?php

namespace Tests\Feature;

use App\Enums\ReminderStatus;
use App\Jobs\SendReminderJob;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RemindersSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeReminder(array $overrides = []): Reminder
    {
        $customer = Customer::factory()->create(['notify_whatsapp' => false, 'notify_email' => false]);
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        return Reminder::factory()->create(array_merge([
            'vehicle_id'   => $vehicle->id,
            'customer_id'  => $customer->id,
            'scheduled_for' => today()->toDateString(),
        ], $overrides));
    }

    private function makeWhatsAppReminder(): Reminder
    {
        $customer = Customer::factory()->create(['notify_whatsapp' => true, 'notify_email' => false]);
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        return Reminder::factory()->create([
            'vehicle_id'   => $vehicle->id,
            'customer_id'  => $customer->id,
            'scheduled_for' => today()->toDateString(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Smoke 1: dedup — running the command twice does not re-send an already-sent reminder
    // -------------------------------------------------------------------------

    public function test_dispatch_twice_does_not_duplicate_sent_reminder(): void
    {
        $reminder = $this->makeReminder();

        // First run: execute job synchronously to mark reminder as sent
        dispatch_sync(new SendReminderJob($reminder));
        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);

        // Second command run: reminder is no longer pending, nothing should be queued
        Queue::fake();
        $this->artisan('reminders:dispatch')->assertSuccessful();
        Queue::assertNothingPushed();
    }

    // -------------------------------------------------------------------------
    // Smoke 2: opt-out — WhatsApp channel is skipped when notify_whatsapp=false
    // -------------------------------------------------------------------------

    public function test_whatsapp_channel_skipped_when_customer_opted_out(): void
    {
        Http::fake();

        $reminder = $this->makeReminder(['scheduled_for' => today()->toDateString()]);

        dispatch_sync(new SendReminderJob($reminder));

        Http::assertNothingSent();
        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
    }

    public function test_email_channel_skipped_when_customer_opted_out(): void
    {
        Mail::fake();

        $reminder = $this->makeReminder(['scheduled_for' => today()->toDateString()]);

        dispatch_sync(new SendReminderJob($reminder));

        Mail::assertNothingSent();
        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // Smoke 3: channel failure — WhatsApp API error → error_message saved, status stays pending
    // -------------------------------------------------------------------------

    public function test_whatsapp_api_failure_stores_error_message_and_keeps_reminder_pending(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 190, 'message' => 'Invalid token']], 401)]);

        $reminder = $this->makeWhatsAppReminder();

        dispatch_sync(new SendReminderJob($reminder));

        $fresh = $reminder->fresh();
        $this->assertNotNull($fresh->error_message);
        $this->assertSame(ReminderStatus::Pending, $fresh->status);
    }

    // -------------------------------------------------------------------------
    // Smoke 4: retry — a pending reminder with a stored error is re-queued on the next command run
    // -------------------------------------------------------------------------

    public function test_command_re_queues_reminder_that_previously_failed(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 190]], 401)]);

        $reminder = $this->makeWhatsAppReminder();

        // Simulate a failed first attempt: channel fails, error stored, status stays pending
        dispatch_sync(new SendReminderJob($reminder));
        $this->assertSame(ReminderStatus::Pending, $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->error_message);

        // Next command run should re-queue the still-pending reminder
        Queue::fake();
        $this->artisan('reminders:dispatch')->assertSuccessful();
        Queue::assertPushed(SendReminderJob::class, 1);
    }
}
