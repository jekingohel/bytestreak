<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChallengeStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\ChallengeAttempt;
use App\Models\User;
use App\Services\DailyChallengeService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DailyChallengeService $daily): Response
    {
        $today = $daily->today();
        $teamSize = User::count();
        $answeredToday = $today ? $today->attempts()->count() : 0;
        $attempts = ChallengeAttempt::count();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'team_size' => $teamSize,
                'answered_today' => $answeredToday,
                'participation' => $teamSize > 0 && $today ? (int) round($answeredToday / $teamSize * 100) : null,
                'active_this_week' => ChallengeAttempt::where('submitted_at', '>=', now()->startOfWeek())->distinct()->count('user_id'),
                'accuracy' => $attempts > 0 ? (int) round(ChallengeAttempt::where('is_correct', true)->count() / $attempts * 100) : null,
                'attempts' => $attempts,
                'queued' => Challenge::queued()->count(),
                'drafts' => Challenge::where('status', ChallengeStatus::Draft)->count(),
            ],
            'today' => $today?->load('category')->toCard(),
            'schedule' => $this->schedule(),
            'participation' => $this->participation(),
            'hardest' => $this->hardest(),
            'categories' => $this->categoryAccuracy(),
        ]);
    }

    /**
     * What developers will get over the next ten days, including which queued
     * challenge the scheduler is going to pick for the days nobody planned.
     */
    private function schedule(): array
    {
        $start = today();
        $planned = Challenge::query()
            ->with('category')
            ->whereIn('status', [ChallengeStatus::Scheduled, ChallengeStatus::Published])
            ->whereDate('publish_date', '>=', $start)
            ->whereDate('publish_date', '<=', $start->copy()->addDays(9))
            ->get()
            ->keyBy(fn (Challenge $c) => $c->publish_date->toDateString());
        $queue = Challenge::queued()->with('category')->orderBy('id')->limit(10)->get();

        return collect(range(0, 9))->map(function (int $offset) use ($start, $planned, $queue) {
            $date = $start->copy()->addDays($offset);
            $challenge = $planned->get($date->toDateString());
            $source = $challenge ? 'planned' : null;

            if (! $challenge && ($date->isWeekday() || config('bytestreak.publish_on_weekends'))) {
                $challenge = $queue->shift();
                $source = $challenge ? 'queue' : 'gap';
            }

            return [
                'date' => $date->toDateString(),
                'source' => $source ?? 'rest',
                'challenge' => $challenge?->toCard(),
            ];
        })->all();
    }

    /** Daily answers per day for the last two weeks. */
    private function participation(): array
    {
        $start = today()->subDays(13);
        $counts = ChallengeAttempt::where('is_daily', true)
            ->where('submitted_at', '>=', $start)
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 13))->map(function (int $offset) use ($start, $counts) {
            $date = $start->copy()->addDays($offset);

            return ['date' => $date->toDateString(), 'count' => (int) ($counts[$date->toDateString()] ?? 0)];
        })->all();
    }

    /** Challenges the team gets wrong most often (needs a few answers to count). */
    private function hardest(): array
    {
        return Challenge::query()
            ->with('category')
            ->withCount(['attempts', 'attempts as correct_count' => fn ($q) => $q->where('is_correct', true)])
            ->has('attempts', '>=', 3)
            ->get()
            ->map(fn (Challenge $c) => [...$c->toCard(), 'attempts' => $c->attempts_count, 'accuracy' => (int) round($c->correct_count / $c->attempts_count * 100)])
            ->sortBy('accuracy')
            ->take(5)
            ->values()
            ->all();
    }

    /** Team accuracy per category: which topics the team finds difficult. */
    private function categoryAccuracy(): array
    {
        $stats = DB::table('challenge_attempts')
            ->join('challenges', 'challenges.id', '=', 'challenge_attempts.challenge_id')
            ->groupBy('challenges.category_id')
            ->selectRaw('challenges.category_id, COUNT(*) as attempts, SUM(challenge_attempts.is_correct) as correct')
            ->get()
            ->keyBy('category_id');

        return Category::ordered()->get()->map(function (Category $category) use ($stats) {
            $row = $stats->get($category->id);
            $attempts = (int) ($row->attempts ?? 0);

            return [
                ...$category->toChip(),
                'attempts' => $attempts,
                'accuracy' => $attempts > 0 ? (int) round(((int) $row->correct) / $attempts * 100) : null,
            ];
        })->all();
    }
}
