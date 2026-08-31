<?php

namespace App\Providers;

use App\Support\SiteSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Réglages du site édités en back-office : surchargent la config des fichiers.
        SiteSettings::apply();

        // E-mail de réinitialisation du mot de passe — en français, aux couleurs du PMS.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Réinitialisation de votre mot de passe — PMS '.config('hotel.short_name'))
                ->greeting('Bonjour '.$notifiable->name.',')
                ->line('Une réinitialisation du mot de passe de votre compte back-office a été demandée.')
                ->action('Choisir un nouveau mot de passe', $url)
                ->line("Ce lien expire dans {$minutes} minutes.")
                ->line("Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail.")
                ->salutation('— '.config('hotel.name'));
        });
    }
}
