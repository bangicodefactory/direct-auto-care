<?php

namespace App\Providers;

use App\Contracts\WhatsAppChannelContract;
use App\Models\Customer;
use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\ReminderPolicy;
use App\Policies\ServiceRecordPolicy;
use App\Policies\UserPolicy;
use App\Services\Notifications\MetaWhatsAppChannel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            WhatsAppChannelContract::class,
            fn () => new MetaWhatsAppChannel(
                phoneNumberId: (string) config('services.meta_whatsapp.phone_number_id'),
                accessToken: (string) config('services.meta_whatsapp.access_token'),
                redirectTo: config('services.meta_whatsapp.redirect_to'),
            ),
        );

    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Reminder::class, ReminderPolicy::class);
        Gate::policy(ServiceRecord::class, ServiceRecordPolicy::class);

        ResetPassword::toMailUsing(function (mixed $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

            return (new MailMessage())
                ->subject('Réinitialisation du mot de passe')
                ->greeting('Bonjour,')
                ->line('Vous recevez cet e-mail car une demande de réinitialisation du mot de passe a été effectuée pour votre compte.')
                ->action('Réinitialiser le mot de passe', $url)
                ->line("Ce lien de réinitialisation expirera dans {$expire} minutes.")
                ->line("Si vous n'avez pas demandé de réinitialisation, aucune action n'est requise.")
                ->salutation('Cordialement, ' . config('app.name'));
        });
    }
}
