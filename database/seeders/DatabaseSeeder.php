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

        // Notificaciones de ejemplo para Said
        Notification::create([
            'user_id' => $said->id,
            'type' => 'mensaje',
            'title' => 'Nuevo mensaje recibido',
            'description' => 'Tienes un nuevo mensaje de Test en el chat general.',
        ]);

        Notification::create([
            'user_id' => $said->id,
            'type' => 'multa',
            'title' => 'Multa registrada',
            'description' => 'Se ha registrado una multa de $500 por ruido excesivo el día 20 de mayo.',
        ]);

        Notification::create([
            'user_id' => $said->id,
            'type' => 'asamblea',
            'title' => 'Asamblea extraordinaria',
            'description' => 'Se convoca a asamblea extraordinaria el viernes 30 de mayo a las 18:00 en el salón de usos múltiples.',
        ]);

        // Notificaciones de ejemplo para Test
        Notification::create([
            'user_id' => $test->id,
            'type' => 'pago_atrasado',
            'title' => 'Pago atrasado',
            'description' => 'Tu cuota de mantenimiento del mes de mayo ($1,200) está pendiente. Favor de regularizar.',
        ]);

        Notification::create([
            'user_id' => $test->id,
            'type' => 'asamblea',
            'title' => 'Asamblea ordinaria',
            'description' => 'Recordatorio: asamblea ordinaria programada para el 1 de junio a las 10:00.',
        ]);
    }
}
