<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Challenge;
use App\Models\ChallengeAttempt;
use App\Models\ChallengeOption;
use App\Models\User;
use App\Models\XpEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The one place an answer turns into XP, streak and badges.
 */
class AttemptService
{
    public function __construct(
        private StreakService $streaks,
        private BadgeService $badges,
    ) {}

    /**
     * @return array{
     *     is_correct: bool, is_daily: bool, xp_total: int,
     *     xp_lines: array<int, array{label: string, amount: int}>,
     *     streak: array<string, mixed>|null,
     *     badges: array<int, array<string, mixed>>,
     *     level_up: array{from: int, to: int, title: string}|null,
     * }
     */
    public function submit(User $user, Challenge $challenge, ChallengeOption $option): array
    {
        return DB::transaction(function () use ($user, $challenge, $option) {
            /** @var User $user */
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if (ChallengeAttempt::where('user_id', $user->id)->where('challenge_id', $challenge->id)->exists()) {
                throw ValidationException::withMessages(['option_id' => 'You have already answered this challenge.']);
            }

            $now = now();
            $today = today();
            $isDaily = $challenge->isDailyOn($today);
            $isCorrect = $option->is_correct;
            $levelBefore = Level::forXp($user->xp);

            $streak = $isDaily ? $this->streaks->registerCompletion($user, $today) : null;

            $lines = [];
            if ($isCorrect) {
                $lines[] = [
                    'reason' => XpEntry::REASON_CORRECT,
                    'label' => $isDaily ? 'Correct answer' : 'Correct answer (practice)',
                    'amount' => $challenge->xpOn($today),
                ];

                $bonus = $isDaily
                    ? min(($streak['current'] - 1) * (int) config('bytestreak.xp.streak_bonus_per_day'), (int) config('bytestreak.xp.streak_bonus_cap'))
                    : 0;
                if ($bonus > 0) {
                    $lines[] = [
                        'reason' => XpEntry::REASON_STREAK_BONUS,
                        'label' => "{$streak['current']}-day streak bonus",
                        'amount' => $bonus,
                    ];
                }
            } elseif ($isDaily && (int) config('bytestreak.xp.participation') > 0) {
                $lines[] = [
                    'reason' => XpEntry::REASON_PARTICIPATION,
                    'label' => 'Showed up today',
                    'amount' => (int) config('bytestreak.xp.participation'),
                ];
            }

            $answerXp = array_sum(array_column($lines, 'amount'));

            $attempt = ChallengeAttempt::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'challenge_option_id' => $option->id,
                'is_correct' => $isCorrect,
                'is_daily' => $isDaily,
                'xp_awarded' => $answerXp,
                'submitted_at' => $now,
            ]);

            foreach ($lines as $line) {
                XpEntry::create([
                    'user_id' => $user->id,
                    'amount' => $line['amount'],
                    'reason' => $line['reason'],
                    'description' => "{$line['label']} · {$challenge->title}",
                    'challenge_attempt_id' => $attempt->id,
                    'created_at' => $now,
                ]);
            }
            $user->xp += $answerXp;

            $newBadges = $this->badges->awardNew($user, $now);
            foreach ($newBadges as $badge) {
                if ($badge->xp_bonus > 0) {
                    $lines[] = ['reason' => XpEntry::REASON_BADGE, 'label' => "Badge: {$badge->name}", 'amount' => $badge->xp_bonus];
                }
            }

            $user->save();

            $levelAfter = Level::forXp($user->xp);

            return [
                'is_correct' => $isCorrect,
                'is_daily' => $isDaily,
                'xp_total' => array_sum(array_column($lines, 'amount')),
                'xp_lines' => array_map(fn (array $l) => ['label' => $l['label'], 'amount' => $l['amount']], $lines),
                'streak' => $streak,
                'badges' => $newBadges->map(fn (Badge $b) => $b->toTile())->values()->all(),
                'level_up' => $levelAfter > $levelBefore
                    ? ['from' => $levelBefore, 'to' => $levelAfter, 'title' => Level::title($levelAfter)]
                    : null,
            ];
        });
    }
}
