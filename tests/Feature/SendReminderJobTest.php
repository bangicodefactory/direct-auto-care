<?php

namespace Tests\Feature;

use App\Enums\ReminderStatus;
use App\Jobs\SendReminderJob;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use App\Services\ReminderService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReminderJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeReminder(): Reminder
    {
        $customer = Customer::factory()->create(['notify_whatsapp' => false, 'notify_email' => false]);
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        return Reminder::factory()->create([
            'vehicle_id'  => $vehicle->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_job_uses_reminders_queue(): void
    {
        $reminder = $this->makeReminder();
        $job      = new SendReminderJob($reminder);

        $this->assertEquals('reminders', $job->queue);
    }

    public function test_job_has_three_retries(): void
    {
        $reminder = $this->makeReminder();
        $job      = new SendReminderJob($reminder);

        $this->assertEquals(3, $job->tries);
    }

    public function test_job_has_exponential_backoff(): void
    {
        $reminder = $this->makeReminder();
        $job      = new SendReminderJob($reminder);
        $backoff  = $job->backoff();

        $this->assertCount(3, $backoff);
        $this->assertGreaterThan($backoff[0], $backoff[1]);
        $this->assertGreaterThan($backoff[1], $backoff[2]);
    }

    public function test_job_calls_reminder_service_dispatch(): void
    {
        $reminder = $this->makeReminder();
        $service  = $this->createMock(ReminderService::class);
        $service->expects($this->once())->method('dispatch')->with($reminder);

        $this->app->instance(ReminderService::class, $service);

        (new SendReminderJob($reminder))->handle($service);
    }

    public function test_job_marks_reminder_sent_synchronously_when_no_channels_configured(): void
    {
        $reminder = $this->makeReminder();

        dispatch_sync(new SendReminderJob($reminder));

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
    }
}
