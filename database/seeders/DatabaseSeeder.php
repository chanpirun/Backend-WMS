<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'assistant@gmail.com'],
            [
                'name' => 'Admin Assistant',
                'password' => bcrypt('assistant123'),
                'role' => 'assistant',
            ]
        );

        User::updateOrCreate(
            ['email' => 'director@gmail.com'],
            [
                'name' => 'Admin Director',
                'password' => bcrypt('director123'),
                'role' => 'director',
            ]
        );

        User::updateOrCreate(
            ['email' => 'member01@email.com'],
            [
                'name' => 'Member User',
                'password' => bcrypt('password123'),
                'role' => 'member',
            ]
        );
    }
}
