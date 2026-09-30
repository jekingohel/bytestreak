<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\DailyChallengeService;
use App\Services\StreakService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'is_local' => app()->isLocal(),
            ],
            'auth' => [
                'user' => fn () => $request->user() ? $this->player($request->user()) : null,
            ],
        ];
    }

    /** The always-visible game state: who you are, your level, and whether today's challenge is waiting. */
    private function player(User $user): array
    {
        $today = app(DailyChallengeService::class)->today();

        return [
            ...$user->toIdentity(),
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'show_on_leaderboard' => $user->show_on_leaderboard,
            'xp' => $user->xp,
            'level' => $user->levelInfo(),
            'streak' => app(StreakService::class)->current($user),
            'longest_streak' => $user->longest_streak,
            'freezes' => $user->streak_freezes,
            'has_today' => $today !== null,
            'done_today' => $today !== null && $user->attempts()->where('challenge_id', $today->id)->exists(),
        ];
    }
}
