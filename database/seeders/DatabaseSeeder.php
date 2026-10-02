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

        $seed = config('bytestreak.seed_admin');
        $admin = User::firstOrNew(['email' => $seed['email']]);

        if (! $admin->exists) {
            // Never fall back to a guessable password outside local development.
            $password = $seed['password'] ?: (app()->isProduction() ? null : 'password');

            if (! $password) {
                $this->command?->error('SEED_ADMIN_PASSWORD is not set, so no admin account was created. Set it and run db:seed again.');

                return;
            }

            $admin->fill([
                'name' => $seed['name'],
                'password' => $password,
            ]);
            $admin->forceFill(['is_admin' => true, 'email_verified_at' => now()])->save();
        }
    }
}
