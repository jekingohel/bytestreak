<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\LeaderboardService;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    public function show(Request $request, ProgressService $progress, LeaderboardService $leaderboard): Response
    {
        $user = $request->user();

        return Inertia::render('Profile', [
            'summary' => $progress->summary($user),
            'heatmap' => $progress->heatmap($user),
            'mastery' => $progress->categoryMastery($user),
            'rank' => $leaderboard->rankOf($user, LeaderboardService::ALL_TIME),
            'joined' => $user->created_at->toIso8601String(),
            'xpHistory' => $progress->xpHistory($user),
            'apiTokens' => $user->is_admin ? [
                'base_url' => url('/api/v1'),
                'choices_days' => config('bytestreak.api_tokens.choices_days'),
                'tokens' => $user->tokens()->latest()->get()->map(fn (PersonalAccessToken $token) => [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at->toIso8601String(),
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'expired' => (bool) $token->expires_at?->isPast(),
                ]),
            ] : null,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile saved.']);

        return back();
    }

    public function password(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Password updated.']);

        return back();
    }
}
