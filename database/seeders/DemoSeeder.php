<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\DailyChallengeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Optional demo data: a team of made-up developers who have been playing for three weeks,
 * so the leaderboard, team stats and admin analytics have something to show.
 *
 * Every answer goes through the real AttemptService with the clock moved to that day,
 * so XP, streaks, freezes and badges are exactly what the app itself would have produced.
 *
 *   php artisan db:seed --class=DemoSeeder
 *   php artisan migrate:fresh --seed        ← start clean again, without the demo team
 */
class DemoSeeder extends Seeder
{
    /** [name, how often they show up, how often they are right] */
    private const TEAM = [
        ['Priya Nair', 0.98, 0.90],
        ['Aarav Mehta', 0.93, 0.84],
        ['Sneha Kapoor', 0.97, 0.72],
        ['Rohan Desai', 0.86, 0.80],
        ['Ananya Iyer', 0.90, 0.66],
        ['Kabir Shah', 0.78, 0.88],
        ['Meera Joshi', 0.82, 0.70],
        ['Dev Patel', 0.70, 0.76],
        ['Isha Verma', 0.64, 0.82],
        ['Arjun Rao', 0.58, 0.62],
        ['Tara Menon', 0.48, 0.78],
        ['Nikhil Bose', 0.36, 0.60],
    ];

    public function run(AttemptService $attempts, DailyChallengeService $daily): void
    {
        if (User::where('email', 'like', '%@demo.bytestreak.test')->exists()) {
            $this->command?->warn('Demo team already exists — skipping.');

            return;
        }

        mt_srand(20260930);
        $realNow = now();
        $firstDay = Challenge::daily()->min('publish_date');

        $team = collect(self::TEAM)->map(function (array $member) use ($firstDay) {
            [$name, $shows, $accuracy] = $member;

            $user = User::create([
                'name' => $name,
                'email' => str($name)->before(' ')->lower().'@demo.bytestreak.test',
                'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            ]);
            $joined = Carbon::parse($firstDay ?? today())->subDays(2);
            $user->forceFill(['email_verified_at' => $joined, 'created_at' => $joined])->save();

            return ['user' => $user, 'shows' => $shows, 'accuracy' => $accuracy];
        });

        $dailies = Challenge::daily()
            ->whereDate('publish_date', '<=', today())
            ->with('options')
            ->orderBy('publish_date')
            ->get();

        try {
            foreach ($dailies as $challenge) {
                $isToday = $challenge->publish_date->isSameDay($realNow);

                foreach ($team as $member) {
                    // Today is still in progress: fewer people have played, and nobody answers in the future.
                    $shows = $isToday ? $member['shows'] * 0.7 : $member['shows'];
                    if ($this->chance($shows) === false) {
                        continue;
                    }

                    $at = $challenge->publish_date->copy()->setTime(mt_rand(9, 17), mt_rand(0, 59), mt_rand(0, 59));
                    if ($at->gt($realNow)) {
                        $at = $realNow->copy()->subMinutes(mt_rand(1, 30));
                    }
                    Carbon::setTestNow($at);
                    $daily->forget();

                    $correct = $challenge->options->firstWhere('is_correct', true);
                    $wrong = $challenge->options->where('is_correct', false)->values();
                    $pick = $this->chance($member['accuracy']) ? $correct : $wrong[mt_rand(0, $wrong->count() - 1)];

                    $attempts->submit($member['user'], $challenge, $pick);
                }
            }
        } finally {
            Carbon::setTestNow();
            $daily->forget();
        }

        $this->command?->info("Demo team of {$team->count()} created.");
    }

    private function chance(float $probability): bool
    {
        return mt_rand(1, 1000) <= $probability * 1000;
    }
}
