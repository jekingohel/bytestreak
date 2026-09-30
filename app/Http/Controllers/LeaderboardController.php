<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, LeaderboardService $leaderboard): Response
    {
        $period = $request->query('period') === LeaderboardService::ALL_TIME
            ? LeaderboardService::ALL_TIME
            : LeaderboardService::WEEK;

        return Inertia::render('Leaderboard', [
            'period' => $period,
            'rows' => $leaderboard->rows($period)->values(),
            'weekEndsAt' => now()->endOfWeek()->toIso8601String(),
        ]);
    }
}
