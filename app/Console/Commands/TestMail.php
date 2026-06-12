<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Enviar correo de prueba';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Enviando correo de prueba a: $email ...");

        try {
            Mail::raw('Este es un correo de prueba desde ASPT. Si lo recibes, el SMTP funciona correctamente.', function ($msg) use ($email) {
                $msg->to($email)->subject('Prueba ASPT - Correo funciona');
            });
            $this->info('✓ Correo enviado exitosamente!');
        } catch (\Exception $e) {
            $this->error('✗ Error al enviar: ' . $e->getMessage());
        }
    }
}
