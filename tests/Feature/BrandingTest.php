<?php

namespace Tests\Feature;

use App\Enums\ReminderType;
use App\Mail\ReminderMail;
use App\Models\Appointment;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\AppointmentCancellationNotification;
use App\Notifications\AppointmentConfirmationNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * COMPANY_BRAND is the per-deployment product name: "Garagio" on the demo,
 * the "Direct Auto Care" default in production. These tests pin both sides.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_brand_defaults_to_direct_auto_care(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('appSettings.brand_name', 'Direct Auto Care'));
    }

    public function test_brand_follows_company_brand_config(): void
    {
        config(['company.brand' => 'Garagio']);

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('appSettings.brand_name', 'Garagio'));
    }

    public function test_brand_is_independent_of_the_legal_company_name(): void
    {
        config(['company.brand' => 'Garagio', 'company.name' => 'DIRECT AUTO CARE SARL']);

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('appSettings.brand_name', 'Garagio')
                ->where('appSettings.company_name', 'DIRECT AUTO CARE SARL'));
    }

    public function test_landing_page_receives_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('auth.user.id', $user->id));
    }

    public function test_reminder_mail_subject_uses_the_brand(): void
    {
        config(['company.brand' => 'Garagio']);
        $reminder = Reminder::factory()->create(['type' => ReminderType::Vidange]);

        $mail = new ReminderMail($reminder->load(['customer', 'vehicle']));

        $this->assertSame('Vidange — Garagio', $mail->envelope()->subject);
    }

    public function test_appointment_mails_use_the_brand(): void
    {
        config(['company.brand' => 'Garagio']);
        $appointment = Appointment::factory()->create();
        $customer    = $appointment->customer;

        $confirmation = (new AppointmentConfirmationNotification($appointment))->toMail($customer);
        $cancellation = (new AppointmentCancellationNotification($appointment))->toMail($customer);

        $this->assertSame('Confirmation de votre rendez-vous — Garagio', $confirmation->subject);
        $this->assertSame("L'équipe Garagio", $confirmation->salutation);
        $this->assertSame('Annulation de votre rendez-vous — Garagio', $cancellation->subject);
        $this->assertSame("L'équipe Garagio", $cancellation->salutation);
    }

    public function test_blank_company_brand_falls_back_to_the_default(): void
    {
        $previous = getenv('COMPANY_BRAND');
        putenv('COMPANY_BRAND=');
        $_ENV['COMPANY_BRAND']    = '';
        $_SERVER['COMPANY_BRAND'] = '';

        try {
            $company = require config_path('company.php');

            $this->assertSame('Direct Auto Care', $company['brand']);
        } finally {
            putenv($previous === false ? 'COMPANY_BRAND' : "COMPANY_BRAND={$previous}");
            unset($_ENV['COMPANY_BRAND'], $_SERVER['COMPANY_BRAND']);
        }
    }

    public function test_server_rendered_title_uses_the_brand(): void
    {
        config(['company.brand' => 'Garagio']);

        $this->get('/')->assertSee('<title inertia>Garagio</title>', false);
    }

    public function test_reminder_mail_signature_uses_the_brand(): void
    {
        config(['company.brand' => 'Garagio']);
        $reminder = Reminder::factory()->create(['type' => ReminderType::Vidange]);

        (new ReminderMail($reminder->load(['customer', 'vehicle'])))
            ->assertSeeInHtml('Garagio');
    }

    public function test_password_reset_mail_signature_uses_the_brand(): void
    {
        config(['company.brand' => 'Garagio']);
        $user = User::factory()->create();

        $mail = (new ResetPassword('token'))->toMail($user);

        $this->assertSame('Cordialement, Garagio', $mail->salutation);
    }

    public function test_appointment_mails_keep_the_default_brand(): void
    {
        $appointment = Appointment::factory()->create();

        $mail = (new AppointmentConfirmationNotification($appointment))->toMail($appointment->customer);

        $this->assertSame('Confirmation de votre rendez-vous — Direct Auto Care', $mail->subject);
        $this->assertSame("L'équipe Direct Auto Care", $mail->salutation);
    }
}
