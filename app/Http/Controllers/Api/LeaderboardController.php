<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaderboardController extends Controller
{
    /** ?period=week (default, resets every Monday) or ?period=all. */
    public function __invoke(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        $period = $request->validate([
            'period' => ['nullable', Rule::in([LeaderboardService::WEEK, LeaderboardService::ALL_TIME])],
        ])['period'] ?? LeaderboardService::WEEK;

        $rows = $leaderboard->rows($period)->values();

        return response()->json([
            'data' => $rows,
            'period' => $period,
            'me' => $rows->firstWhere('id', $request->user()->id),
            'week_ends_at' => now()->endOfWeek()->toIso8601String(),
        ]);
    }
}
