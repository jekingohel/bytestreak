<?php

namespace App\Console\Commands;

use App\Services\DailyChallengeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('challenges:publish-daily {--date= : Publish for this date (Y-m-d) instead of today}')]
#[Description("Publish today's challenge: the one scheduled for today, or the next one in the queue")]
class PublishDailyChallenge extends Command
{
    public function handle(DailyChallengeService $daily): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : today();
        $challenge = $daily->publishDue($date);

        if (! $challenge) {
            $this->warn("No challenge for {$date->toDateString()} — nothing scheduled and the queue is empty (or it is a rest day).");

            return self::SUCCESS;
        }

        $this->info("{$date->toDateString()}: #{$challenge->id} {$challenge->title}");

        return self::SUCCESS;
    }
}
