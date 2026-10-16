<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\LeaderboardService;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /** Who you are, your level and streak, and whether today's challenge is done. */
    public function show(Request $request, ProgressService $progress): JsonResponse
    {
        return response()->json(['data' => $progress->player($request->user())]);
    }

    public function update(UpdateProfileRequest $request, ProgressService $progress): JsonResponse
    {
        $request->user()->update($request->validated());

        return response()->json(['data' => $progress->player($request->user())]);
    }

    public function password(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return response()->json(['message' => 'Password updated.']);
    }

    /** The numbers behind the dashboard and profile pages. */
    public function stats(Request $request, ProgressService $progress, LeaderboardService $leaderboard): JsonResponse
    {
        $weeks = (int) ($request->validate(['weeks' => ['nullable', 'integer', 'min:1', 'max:53']])['weeks'] ?? 26);
        $user = $request->user();

        return response()->json(['data' => [
            'summary' => $progress->summary($user),
            'week' => $progress->week($user),
            'heatmap' => $progress->heatmap($user, $weeks),
            'mastery' => $progress->categoryMastery($user),
            'rank' => [
                'week' => $leaderboard->rankOf($user, LeaderboardService::WEEK),
                'all' => $leaderboard->rankOf($user, LeaderboardService::ALL_TIME),
            ],
            'xp_history' => $progress->xpHistory($user, 25),
            'joined' => $user->created_at->toIso8601String(),
        ]]);
    }
}
