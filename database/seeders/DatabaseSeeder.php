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
        $directorEmail = env('SEED_DIRECTOR_EMAIL');
        $directorPassword = env('SEED_DIRECTOR_PASSWORD');
        $assistantEmail = env('SEED_ASST_EMAIL');
        $assistantPassword = env('SEED_ASST_PASSWORD');
        $memberEmail = env('SEED_MEMBER_EMAIL');
        $memberPassword = env('SEED_MEMBER_PASSWORD');

        if (!$directorEmail || !$directorPassword || !$assistantEmail || !$assistantPassword || !$memberEmail || !$memberPassword) {
            throw new \Exception("Seeding failed: Please configure SEED_DIRECTOR_EMAIL, SEED_DIRECTOR_PASSWORD, SEED_ASST_EMAIL, SEED_ASST_PASSWORD, SEED_MEMBER_EMAIL, and SEED_MEMBER_PASSWORD in your .env file.");
        }

        User::updateOrCreate(
            ['email' => $directorEmail],
            [
                'name' => 'Admin Director',
                'password' => bcrypt($directorPassword),
                'role' => 'director',
            ]
        );

        User::updateOrCreate(
            ['email' => $assistantEmail],
            [
                'name' => 'Admin Assistant',
                'password' => bcrypt($assistantPassword),
                'role' => 'assistant',
            ]
        );

        User::updateOrCreate(
            ['email' => $memberEmail],
            [
                'name' => 'Member User',
                'password' => bcrypt($memberPassword),
                'role' => 'member',
            ]
        );

        $this->call(ProjectTypeSeeder::class);
    }
}
