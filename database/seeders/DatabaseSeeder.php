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
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);

        $said = User::create([
            'name' => 'Said',
            'email' => 'said@test.com',
            'password' => Hash::make('123456'),
            'role' => 'user',
        ]);

        $karol = User::create([
            'name' => 'Karol',
            'email' => 'karol@test.com',
            'password' => Hash::make('123456'),
            'role' => 'user',
        ]);

        // Notificaciones de prueba para Said
        Notification::create([
            'user_id' => $said->id,
            'type' => 'mensaje',
            'title' => 'Nuevo mensaje de Karol',
            'description' => 'Karol te ha enviado un mensaje en el chat.',
        ]);

        Notification::create([
            'user_id' => $said->id,
            'type' => 'multa',
            'title' => 'Multa por retraso',
            'description' => 'El administrador te ha aplicado una multa de $500 por retraso en el pago de mantenimiento.',
        ]);

        // Notificaciones de prueba para Karol
        Notification::create([
            'user_id' => $karol->id,
            'type' => 'mensaje',
            'title' => 'Nuevo mensaje de Said',
            'description' => 'Said te ha enviado un mensaje en el chat.',
        ]);

        Notification::create([
            'user_id' => $karol->id,
            'type' => 'asamblea',
            'title' => 'Asamblea general programada',
            'description' => 'Se convoca asamblea general para el día 15 de junio a las 10:00 AM.',
        ]);

        Notification::create([
            'user_id' => $karol->id,
            'type' => 'pago_atrasado',
            'title' => 'Pago atrasado',
            'description' => 'Tienes un pago pendiente del mes de mayo.',
        ]);
    }
}
