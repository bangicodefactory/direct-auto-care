<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

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
