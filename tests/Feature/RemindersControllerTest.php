<?php

namespace Tests\Feature;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemindersControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin    = User::factory()->create()->assignRole('admin');
        $this->employee = User::factory()->create()->assignRole('employee');
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

    public function test_admin_can_view_reminders_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reminders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Reminders/Index'));
    }

    public function test_employee_cannot_view_reminders_index(): void
    {
        $this->actingAs($this->employee)
            ->get(route('reminders.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_reminders_index(): void
    {
        $this->get(route('reminders.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_filters_by_status(): void
    {
        $this->makeReminder(['status' => ReminderStatus::Pending]);
        $this->makeReminder(['status' => ReminderStatus::Sent]);

        $this->actingAs($this->admin)
            ->get(route('reminders.index', ['status' => 'sent']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('reminders.data', 1));
    }

    public function test_index_filters_by_type(): void
    {
        $this->makeReminder(['type' => ReminderType::Vidange]);
        $this->makeReminder(['type' => ReminderType::VisiteTechnique]);

        $this->actingAs($this->admin)
            ->get(route('reminders.index', ['type' => 'visite_technique']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('reminders.data', 1));
    }

    public function test_index_filters_by_date_range(): void
    {
        $this->makeReminder(['scheduled_for' => '2026-01-15']);
        $this->makeReminder(['scheduled_for' => '2026-06-15']);

        $this->actingAs($this->admin)
            ->get(route('reminders.index', ['date_from' => '2026-01-01', 'date_to' => '2026-03-31']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('reminders.data', 1));
    }

    public function test_admin_can_cancel_pending_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Pending]);

        $this->actingAs($this->admin)
            ->post(route('reminders.cancel', $reminder))
            ->assertRedirect(route('reminders.index'));

        $this->assertSame(ReminderStatus::Cancelled, $reminder->fresh()->status);
    }

    public function test_cannot_cancel_sent_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Sent]);

        $this->actingAs($this->admin)
            ->post(route('reminders.cancel', $reminder))
            ->assertForbidden();
    }

    public function test_cannot_cancel_cancelled_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Cancelled]);

        $this->actingAs($this->admin)
            ->post(route('reminders.cancel', $reminder))
            ->assertForbidden();
    }

    public function test_employee_cannot_cancel_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Pending]);

        $this->actingAs($this->employee)
            ->post(route('reminders.cancel', $reminder))
            ->assertForbidden();
    }

    public function test_admin_can_trigger_pending_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Pending]);

        $this->actingAs($this->admin)
            ->post(route('reminders.trigger', $reminder))
            ->assertRedirect(route('reminders.index'));

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
    }

    public function test_admin_can_trigger_cancelled_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Cancelled]);

        $this->actingAs($this->admin)
            ->post(route('reminders.trigger', $reminder))
            ->assertRedirect(route('reminders.index'));

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
    }

    public function test_cannot_trigger_sent_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Sent]);

        $this->actingAs($this->admin)
            ->post(route('reminders.trigger', $reminder))
            ->assertForbidden();
    }

    public function test_employee_cannot_trigger_reminder(): void
    {
        $reminder = $this->makeReminder(['status' => ReminderStatus::Pending]);

        $this->actingAs($this->employee)
            ->post(route('reminders.trigger', $reminder))
            ->assertForbidden();
    }
}
