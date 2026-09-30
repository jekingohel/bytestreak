<?php

namespace App\Services;

use App\Enums\ChallengeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only views of one developer's progress: the numbers behind the dashboard and profile.
 */
class ProgressService
{
    /** @return array{completed: int, correct: int, accuracy: int|null, badges: int, longest_streak: int} */
    public function summary(User $user): array
    {
        $totals = $user->attempts()
            ->selectRaw('COUNT(*) as completed, COALESCE(SUM(is_correct), 0) as correct')
            ->toBase()
            ->first();

        $completed = (int) $totals->completed;
        $correct = (int) $totals->correct;

        return [
            'completed' => $completed,
            'correct' => $correct,
            'accuracy' => $completed > 0 ? (int) round($correct / $completed * 100) : null,
            'badges' => $user->badges()->count(),
            'longest_streak' => $user->longest_streak,
        ];
    }

    /**
     * Monday → Sunday of the current week, one state per day:
     * correct | wrong | missed | today | upcoming | rest
     *
     * @return array<int, array{date: string, label: string, state: string}>
     */
    public function week(User $user): array
    {
        $today = today();
        $start = $today->copy()->startOfWeek();
        $end = $start->copy()->addDays(6);

        $challenges = Challenge::query()
            ->whereIn('status', [ChallengeStatus::Published, ChallengeStatus::Scheduled])
            ->whereDate('publish_date', '>=', $start)
            ->whereDate('publish_date', '<=', $end)
            ->get(['id', 'publish_date', 'status'])
            ->keyBy(fn (Challenge $c) => $c->publish_date->toDateString());

        $attempts = $user->attempts()
            ->where('is_daily', true)
            ->whereIn('challenge_id', $challenges->pluck('id'))
            ->pluck('is_correct', 'challenge_id');

        $queueHasItems = Challenge::queued()->exists();
        $joined = $user->created_at->copy()->startOfDay();

        return collect(range(0, 6))->map(function (int $offset) use ($start, $today, $challenges, $attempts, $queueHasItems, $joined) {
            $date = $start->copy()->addDays($offset);
            $challenge = $challenges->get($date->toDateString());

            $state = match (true) {
                $challenge && $attempts->has($challenge->id) => $attempts[$challenge->id] ? 'correct' : 'wrong',
                $date->isSameDay($today) => $challenge ? 'today' : 'rest',
                // A day only counts as missed if the user had already joined.
                $date->lt($today) => $challenge && $date->gte($joined) ? 'missed' : 'rest',
                default => $challenge || ($queueHasItems && $this->publishesOn($date)) ? 'upcoming' : 'rest',
            };

            return ['date' => $date->toDateString(), 'label' => $date->format('D'), 'state' => $state];
        })->all();
    }

    /**
     * GitHub-style activity grid: columns are weeks (Mon → Sun), oldest first.
     *
     * @return array{weeks: array<int, array<int, array{date: string, count: int, correct: int, future: bool}>>, total: int, active_days: int}
     */
    public function heatmap(User $user, int $weeks = 26): array
    {
        $today = today();
        $start = $today->copy()->startOfWeek()->subWeeks($weeks - 1);

        $byDay = $user->attempts()
            ->where('submitted_at', '>=', $start)
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as total, SUM(is_correct) as correct')
            ->groupBy('day')
            ->toBase()
            ->get()
            ->keyBy('day');

        $grid = [];
        for ($w = 0; $w < $weeks; $w++) {
            $column = [];
            for ($d = 0; $d < 7; $d++) {
                $date = $start->copy()->addDays($w * 7 + $d);
                $row = $byDay->get($date->toDateString());
                $column[] = [
                    'date' => $date->toDateString(),
                    'count' => (int) ($row->total ?? 0),
                    'correct' => (int) ($row->correct ?? 0),
                    'future' => $date->gt($today),
                ];
            }
            $grid[] = $column;
        }

        return [
            'weeks' => $grid,
            'total' => (int) $byDay->sum('total'),
            'active_days' => $byDay->count(),
        ];
    }

    /**
     * Accuracy per category, including categories the user has not touched yet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function categoryMastery(User $user): array
    {
        $stats = DB::table('challenge_attempts')
            ->join('challenges', 'challenges.id', '=', 'challenge_attempts.challenge_id')
            ->where('challenge_attempts.user_id', $user->id)
            ->groupBy('challenges.category_id')
            ->selectRaw('challenges.category_id, COUNT(*) as attempts, SUM(challenge_attempts.is_correct) as correct')
            ->get()
            ->keyBy('category_id');

        return Category::ordered()->where('is_active', true)->get()
            ->map(function (Category $category) use ($stats) {
                $row = $stats->get($category->id);
                $attempts = (int) ($row->attempts ?? 0);
                $correct = (int) ($row->correct ?? 0);

                return [
                    ...$category->toChip(),
                    'attempts' => $attempts,
                    'correct' => $correct,
                    'accuracy' => $attempts > 0 ? (int) round($correct / $attempts * 100) : null,
                ];
            })
            ->all();
    }

    private function publishesOn(Carbon $date): bool
    {
        return $date->isWeekday() || config('bytestreak.publish_on_weekends');
    }
}
