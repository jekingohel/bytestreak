<?php

namespace App\Services;

use App\Models\User;
use App\Models\XpEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public const WEEK = 'week';

    public const ALL_TIME = 'all';

    public function __construct(private StreakService $streaks) {}

    /**
     * Ranked rows for everyone who opted in. The weekly board resets every Monday and
     * only lists people who earned XP this week.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(string $period = self::WEEK): Collection
    {
        $query = User::query()->where('show_on_leaderboard', true);

        if ($period === self::WEEK) {
            $weekly = XpEntry::query()
                ->selectRaw('user_id, SUM(amount) as period_xp')
                ->where('created_at', '>=', now()->startOfWeek())
                ->groupBy('user_id');

            $query->joinSub($weekly, 'weekly', 'weekly.user_id', '=', 'users.id')
                ->select('users.*', DB::raw('weekly.period_xp as period_xp'))
                ->where('weekly.period_xp', '>', 0);
        } else {
            $query->select('users.*', DB::raw('users.xp as period_xp'))->where('users.xp', '>', 0);
        }

        $rank = 0;
        $position = 0;
        $lastXp = null;

        return $query->orderByDesc('period_xp')->orderBy('users.name')->get()
            ->map(function (User $user) use (&$rank, &$position, &$lastXp) {
                // Equal XP shares a rank (1, 2, 2, 4).
                $position++;
                if ($lastXp !== (int) $user->period_xp) {
                    $rank = $position;
                    $lastXp = (int) $user->period_xp;
                }

                return [
                    ...$user->toIdentity(),
                    'rank' => $rank,
                    'xp' => (int) $user->period_xp,
                    'total_xp' => $user->xp,
                    'level' => Level::forXp($user->xp),
                    'title' => Level::title(Level::forXp($user->xp)),
                    'streak' => $this->streaks->current($user),
                ];
            });
    }

    public function rankOf(User $user, string $period = self::WEEK): ?int
    {
        return $this->rows($period)->firstWhere('id', $user->id)['rank'] ?? null;
    }
}
