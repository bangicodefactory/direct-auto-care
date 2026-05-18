<?php

namespace Tests\Feature;

use App\Enums\ReminderStatus;
use App\Jobs\SendReminderJob;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchRemindersCommandTest extends TestCase
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
            'vehicle_id'  => $vehicle->id,
            'customer_id' => $customer->id,
        ], $overrides));
    }

    public function test_command_dispatches_job_for_each_due_reminder(): void
    {
        Queue::fake();

        $this->makeReminder(['scheduled_for' => today()->toDateString()]);
        $this->makeReminder(['scheduled_for' => today()->toDateString()]);

        $this->artisan('reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(SendReminderJob::class, 2);
    }

    public function test_command_dispatches_no_jobs_when_none_are_due(): void
    {
        Queue::fake();

        $this->makeReminder(['scheduled_for' => today()->addDay()->toDateString()]);

        $this->artisan('reminders:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_command_skips_already_sent_reminders(): void
    {
        Queue::fake();

        $this->makeReminder([
            'scheduled_for' => today()->toDateString(),
            'status'        => ReminderStatus::Sent,
        ]);

        $this->artisan('reminders:dispatch')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_command_pushes_jobs_to_reminders_queue(): void
    {
        Queue::fake();

        $this->makeReminder(['scheduled_for' => today()->toDateString()]);

        $this->artisan('reminders:dispatch')->assertSuccessful();

        Queue::assertPushedOn('reminders', SendReminderJob::class);
    }

    public function test_command_outputs_dispatched_count(): void
    {
        Queue::fake();

        $this->makeReminder(['scheduled_for' => today()->toDateString()]);
        $this->makeReminder(['scheduled_for' => today()->toDateString()]);

        $this->artisan('reminders:dispatch')
            ->expectsOutput('Dispatched 2 reminder(s).')
            ->assertSuccessful();
    }
}
