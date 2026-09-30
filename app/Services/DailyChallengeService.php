<?php

namespace App\Services;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Decides which challenge is "today's". The scheduler calls publishDue() just after midnight,
 * but today() also calls it lazily so the site keeps working when cron isn't running.
 */
class DailyChallengeService
{
    /** @var array<string, Challenge|null> */
    private array $resolved = [];

    public function today(): ?Challenge
    {
        $key = today()->toDateString();

        if (! array_key_exists($key, $this->resolved)) {
            $this->resolved[$key] = $this->publishDue(today());
        }

        return $this->resolved[$key];
    }

    public function forget(): void
    {
        $this->resolved = [];
    }

    /** Publish whatever is due on $date and return that day's challenge, if any. */
    public function publishDue(Carbon $date): ?Challenge
    {
        $existing = Challenge::daily()->whereDate('publish_date', $date)->first();

        if ($existing && ! Challenge::where('status', ChallengeStatus::Scheduled)->whereDate('publish_date', '<=', $date)->exists()) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($date) {
                // Scheduled for a day that already passed without going live: release it as a bonus
                // challenge so nobody's streak is charged for a day they could not have played.
                Challenge::where('status', ChallengeStatus::Scheduled)
                    ->whereDate('publish_date', '<', $date)
                    ->update(['status' => ChallengeStatus::Published, 'publish_date' => null]);

                Challenge::where('status', ChallengeStatus::Scheduled)
                    ->whereDate('publish_date', $date)
                    ->update(['status' => ChallengeStatus::Published]);

                $todays = Challenge::daily()->whereDate('publish_date', $date)->first();

                if (! $todays && ($date->isWeekday() || config('bytestreak.publish_on_weekends'))) {
                    $todays = Challenge::queued()->orderBy('id')->lockForUpdate()->first();
                    $todays?->update(['status' => ChallengeStatus::Published, 'publish_date' => $date->toDateString()]);
                }

                return $todays;
            });
        } catch (QueryException) {
            // Two requests raced to fill the same date; the unique index kept one. Use the winner.
            return Challenge::daily()->whereDate('publish_date', $date)->first();
        }
    }
}
