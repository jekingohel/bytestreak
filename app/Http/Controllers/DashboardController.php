<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Challenge;
use App\Models\ChallengeAttempt;
use App\Services\BadgeService;
use App\Services\DailyChallengeService;
use App\Services\LeaderboardService;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DailyChallengeService $daily,
        ProgressService $progress,
        BadgeService $badges,
        LeaderboardService $leaderboard,
    ): Response {
        $user = $request->user();
        $today = $daily->today()?->load('category');
        $attempt = $today ? $user->attempts()->where('challenge_id', $today->id)->first() : null;

        $metrics = $badges->metrics($user);
        $earned = $user->badges()->pluck('badges.id');

        $board = $leaderboard->rows(LeaderboardService::WEEK);

        return Inertia::render('Dashboard', [
            'today' => $today ? [
                ...$today->toCard(),
                'players' => $today->attempts()->count(),
                'attempt' => $attempt ? [
                    'is_correct' => $attempt->is_correct,
                    'xp_awarded' => $attempt->xp_awarded,
                ] : null,
            ] : null,
            'nextChallengeAt' => now()->addDay()->startOfDay()->toIso8601String(),
            'summary' => $progress->summary($user),
            'week' => $progress->week($user),
            'mastery' => $progress->categoryMastery($user),
            'nextBadges' => Badge::active()->ordered()->whereNotIn('id', $earned)->get()
                ->map(fn (Badge $badge) => [...$badge->toTile(), 'progress' => $badges->progress($badge, $metrics)])
                ->sortBy([['progress.remaining', 'asc'], ['progress.percent', 'desc']])
                ->take(3)
                ->values(),
            'leaderboard' => [
                'top' => $board->take(5)->values(),
                'me' => $board->firstWhere('id', $user->id),
                'size' => $board->count(),
            ],
            'unplayed' => Challenge::visible()
                ->when($today, fn ($q) => $q->whereKeyNot($today->id))
                ->whereDoesntHave('attempts', fn ($q) => $q->where('user_id', $user->id))
                ->count(),
            'recent' => $user->attempts()->with('challenge.category')->latest('submitted_at')->limit(4)->get()
                ->map(fn (ChallengeAttempt $a) => [
                    ...$a->challenge->toCard(),
                    'is_correct' => $a->is_correct,
                    'xp_awarded' => $a->xp_awarded,
                    'submitted_at' => $a->submitted_at->toIso8601String(),
                ]),
        ]);
    }
}
