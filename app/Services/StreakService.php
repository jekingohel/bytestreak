<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * A streak counts consecutive *published daily challenges* a user completed — right or wrong.
 * Days without a challenge never break it, and streak freezes absorb missed days.
 */
class StreakService
{
    /** Daily challenges that went live after the user's last completion and before $today. */
    public function missedSince(User $user, Carbon $today): int
    {
        if ($user->last_completed_on === null) {
            return 0;
        }

        return Challenge::daily()
            ->whereDate('publish_date', '>', $user->last_completed_on)
            ->whereDate('publish_date', '<', $today)
            ->count();
    }

    /** The streak as it stands right now (0 once missed days outnumber the freezes). */
    public function current(User $user, ?Carbon $today = null): int
    {
        $today ??= today();

        if ($user->current_streak === 0 || $user->last_completed_on === null) {
            return 0;
        }

        if ($user->last_completed_on->isSameDay($today)) {
            return $user->current_streak;
        }

        return $this->missedSince($user, $today) > $user->streak_freezes ? 0 : $user->current_streak;
    }

    /**
     * Record that the user completed today's daily challenge. Mutates (but does not save) the user.
     *
     * @return array{current: int, previous: int, extended: bool, was_reset: bool, freezes_used: int, freeze_earned: bool, freezes: int}
     */
    public function registerCompletion(User $user, Carbon $today): array
    {
        $previous = $user->current_streak;

        if ($user->last_completed_on?->isSameDay($today)) {
            return [
                'current' => $previous, 'previous' => $previous, 'extended' => false, 'was_reset' => false,
                'freezes_used' => 0, 'freeze_earned' => false, 'freezes' => $user->streak_freezes,
            ];
        }

        $missed = $this->missedSince($user, $today);
        $freezesUsed = 0;
        $wasReset = false;

        if ($previous > 0 && $missed <= $user->streak_freezes) {
            $freezesUsed = $missed;
            $user->streak_freezes -= $missed;
            $user->current_streak = $previous + 1;
        } else {
            $wasReset = $previous > 0;
            $user->current_streak = 1;
        }

        $freezeEarned = false;
        $every = (int) config('bytestreak.streak.freeze_every');
        if ($every > 0
            && $user->current_streak % $every === 0
            && $user->streak_freezes < (int) config('bytestreak.streak.max_freezes')) {
            $user->streak_freezes++;
            $freezeEarned = true;
        }

        $user->longest_streak = max($user->longest_streak, $user->current_streak);
        $user->last_completed_on = $today->copy()->startOfDay();

        return [
            'current' => $user->current_streak,
            'previous' => $previous,
            'extended' => true,
            'was_reset' => $wasReset,
            'freezes_used' => $freezesUsed,
            'freeze_earned' => $freezeEarned,
            'freezes' => $user->streak_freezes,
        ];
    }
}
