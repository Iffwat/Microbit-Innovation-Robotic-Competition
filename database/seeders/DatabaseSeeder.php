<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@isobot.my'],
            [
                'name'     => 'Pentadbir Sistem',
                'email'    => 'admin@isobot.my',
                'password' => Hash::make('admin123'),
            ]
        );

        // Seed categories
        $this->call(CategorySeeder::class);
    }
}
