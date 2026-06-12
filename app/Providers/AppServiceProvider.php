<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Personalizar el correo de verificación de email
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            // Construir la URL de verificación que apunta al frontend
            // El frontend redirigirá al endpoint de la API para verificar
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            
            // Extraer los parámetros de la URL original de Laravel
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'] ?? '';
            $query = $parsedUrl['query'] ?? '';
            
            // Construir URL del frontend que redirige a la verificación
            $verificationUrl = $frontendUrl . '/verify-email?url=' . urlencode($url);

            return (new MailMessage)
                ->subject('Verifica tu correo electrónico - ASPT')
                ->greeting('¡Hola ' . $notifiable->name . '!')
                ->line('Gracias por registrarte. Haz clic en el botón para verificar tu correo electrónico.')
                ->action('Verificar Correo Electrónico', $verificationUrl)
                ->line('Si no creaste una cuenta, ignora este correo.')
                ->salutation('Saludos, ASPT App');
        });
    }
}
