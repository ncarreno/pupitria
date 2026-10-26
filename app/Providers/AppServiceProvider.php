<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(fn (object $notifiable, string $url) => (new MailMessage)
            ->subject('Confirma tu email')
            ->greeting('Hola:')
            ->line('Pulsa el botón para confirmar tu email y empezar a usar '.config('app.name').'.')
            ->action('Confirmar mi email', $url)
            ->line('Si no has creado ninguna cuenta, no tienes que hacer nada.')
            ->salutation('Un saludo'));
    }
}
