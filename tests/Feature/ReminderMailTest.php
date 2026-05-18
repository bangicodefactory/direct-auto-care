<?php

namespace Tests\Feature;

use App\Enums\ReminderType;
use App\Mail\ReminderMail;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\Vehicle;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeReminder(array $overrides = []): Reminder
    {
        $customer = Customer::factory()->create(['email' => 'client@example.com']);
        $vehicle  = Vehicle::factory()->create(['customer_id' => $customer->id]);

        return Reminder::factory()->create(array_merge([
            'vehicle_id'  => $vehicle->id,
            'customer_id' => $customer->id,
        ], $overrides));
    }

    public function test_mailable_has_correct_subject_for_vidange(): void
    {
        $reminder = $this->makeReminder(['type' => ReminderType::Vidange]);
        $mail     = new ReminderMail($reminder->load(['customer', 'vehicle']));

        $this->assertEquals('Vidange — Direct Auto Care', $mail->envelope()->subject);
    }

    public function test_mailable_has_correct_subject_for_visite_technique(): void
    {
        $reminder = $this->makeReminder(['type' => ReminderType::VisiteTechnique]);
        $mail     = new ReminderMail($reminder->load(['customer', 'vehicle']));

        $this->assertEquals('Visite technique — Direct Auto Care', $mail->envelope()->subject);
    }

    public function test_mailable_has_correct_subject_for_entretien_periodique(): void
    {
        $reminder = $this->makeReminder(['type' => ReminderType::EntretienPeriodique]);
        $mail     = new ReminderMail($reminder->load(['customer', 'vehicle']));

        $this->assertEquals('Entretien périodique — Direct Auto Care', $mail->envelope()->subject);
    }

    public function test_mailable_uses_reminder_markdown_view(): void
    {
        $reminder = $this->makeReminder();
        $mail     = new ReminderMail($reminder->load(['customer', 'vehicle']));

        $this->assertEquals('mail.reminder', $mail->content()->markdown);
    }

    public function test_mailable_can_be_dispatched(): void
    {
        Mail::fake();

        $reminder = $this->makeReminder(['type' => ReminderType::Vidange]);

        Mail::to('client@example.com')->send(new ReminderMail($reminder->load(['customer', 'vehicle'])));

        Mail::assertSent(ReminderMail::class);
    }
}
