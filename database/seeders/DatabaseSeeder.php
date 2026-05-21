<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Said',
            'email' => 'said@test.com',
            'password' => Hash::make('123456'),
        ]);

        User::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => Hash::make('123456'),
        ]);
    }
}
