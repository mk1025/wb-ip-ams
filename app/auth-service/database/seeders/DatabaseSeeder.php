<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'password' => Hash::make('password'),
                'role' => 'super-admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'alice@example.com'],
            [
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );
    }
}
