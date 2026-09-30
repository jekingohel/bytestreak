<?php

namespace App\Services;

use App\Enums\BadgeCriteria;
use App\Models\Badge;
use App\Models\User;
use App\Models\XpEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BadgeService
{
    public function __construct(private StreakService $streaks) {}

    /**
     * Everything badge criteria can be measured against.
     *
     * @return array{attempts_total: int, correct_total: int, streak: int, correct_in_a_row: int, level: int, category_correct: array<int, int>}
     */
    public function metrics(User $user): array
    {
        $attempts = $user->attempts()
            ->join('challenges', 'challenges.id', '=', 'challenge_attempts.challenge_id')
            ->orderByDesc('challenge_attempts.submitted_at')
            ->orderByDesc('challenge_attempts.id')
            ->get(['challenge_attempts.is_correct', 'challenges.category_id']);

        $run = 0;
        foreach ($attempts as $attempt) {
            if (! $attempt->is_correct) {
                break;
            }
            $run++;
        }

        return [
            'attempts_total' => $attempts->count(),
            'correct_total' => $attempts->where('is_correct', true)->count(),
            'streak' => max($this->streaks->current($user), 0),
            'correct_in_a_row' => $run,
            'level' => Level::forXp($user->xp),
            'category_correct' => $attempts->where('is_correct', true)->countBy('category_id')->all(),
        ];
    }

    /** @return array{current: int, target: int, remaining: int, percent: int} */
    public function progress(Badge $badge, array $metrics): array
    {
        $current = match ($badge->criteria_type) {
            BadgeCriteria::CategoryCorrect => $metrics['category_correct'][$badge->category_id] ?? 0,
            default => $metrics[$badge->criteria_type->value] ?? 0,
        };
        $target = max(1, $badge->criteria_value);

        return [
            'current' => min($current, $target),
            'target' => $target,
            'remaining' => $target - min($current, $target),
            'percent' => (int) floor(min($current, $target) / $target * 100),
        ];
    }

    /**
     * Award every badge the user now qualifies for. Mutates (but does not save) $user->xp.
     *
     * @return Collection<int, Badge>
     */
    public function awardNew(User $user, ?Carbon $at = null): Collection
    {
        $at ??= now();
        $awarded = collect();

        // Bonus XP can push a level badge over the line, so go round until nothing new unlocks.
        for ($pass = 0; $pass < 3; $pass++) {
            $metrics = $this->metrics($user);
            $earnedIds = $user->badges()->pluck('badges.id');

            $new = Badge::active()->ordered()->whereNotIn('id', $earnedIds)->get()
                ->filter(fn (Badge $badge) => $this->progress($badge, $metrics)['percent'] >= 100);

            if ($new->isEmpty()) {
                break;
            }

            foreach ($new as $badge) {
                $user->badges()->attach($badge->id, ['earned_at' => $at]);

                if ($badge->xp_bonus > 0) {
                    XpEntry::create([
                        'user_id' => $user->id,
                        'amount' => $badge->xp_bonus,
                        'reason' => XpEntry::REASON_BADGE,
                        'description' => "Badge unlocked: {$badge->name}",
                        'badge_id' => $badge->id,
                        'created_at' => $at,
                    ]);
                    $user->xp += $badge->xp_bonus;
                }

                $awarded->push($badge);
            }
        }

        return $awarded;
    }
}
