<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $said = User::create([
            'name' => 'Said',
            'email' => 'said@test.com',
            'password' => Hash::make('123456'),
        ]);

        $test = User::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => Hash::make('123456'),
        ]);

        // Notificaciones de prueba para Said
        Notification::create([
            'user_id' => $said->id,
            'type' => 'mensaje',
            'title' => 'Nuevo mensaje de Test',
            'description' => 'Te han enviado un mensaje en el chat general.',
        ]);

        Notification::create([
            'user_id' => $said->id,
            'type' => 'multa',
            'title' => 'Multa por retraso',
            'description' => 'Se te ha aplicado una multa de $500 por retraso en el pago de mantenimiento.',
        ]);

        Notification::create([
            'user_id' => $said->id,
            'type' => 'asamblea',
            'title' => 'Asamblea general programada',
            'description' => 'Se convoca asamblea general para el día 15 de junio a las 10:00 AM.',
        ]);

        Notification::create([
            'user_id' => $said->id,
            'type' => 'pago_atrasado',
            'title' => 'Pago atrasado',
            'description' => 'Tienes un pago pendiente del mes de mayo.',
        ]);

        // Notificaciones para Test
        Notification::create([
            'user_id' => $test->id,
            'type' => 'asamblea',
            'title' => 'Asamblea extraordinaria',
            'description' => 'Asamblea extraordinaria convocada para el 20 de junio.',
        ]);

        Notification::create([
            'user_id' => $test->id,
            'type' => 'pago_atrasado',
            'title' => 'Cuota vencida',
            'description' => 'La cuota de mantenimiento de abril sigue pendiente.',
        ]);
    }
}
