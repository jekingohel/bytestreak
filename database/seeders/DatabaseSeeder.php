<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: categories, badges, the starter question bank
     * and one admin account (SEED_ADMIN_* in .env).
     *
     * For a lively demo with teammates on the leaderboard, also run:
     *   php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            BadgeSeeder::class,
            ChallengeSeeder::class,
        ]);

        $admin = User::firstOrNew(['email' => env('SEED_ADMIN_EMAIL', 'admin@bytestreak.test')]);

        if (! $admin->exists) {
            $admin->fill([
                'name' => env('SEED_ADMIN_NAME', 'Admin'),
                'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            ]);
            $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();
        }
    }
}
