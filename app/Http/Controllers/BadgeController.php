<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BadgeController extends Controller
{
    public function __invoke(Request $request, BadgeService $badges): Response
    {
        $user = $request->user();
        $metrics = $badges->metrics($user);
        $earned = $user->badges()->pluck('user_badges.earned_at', 'badges.id');

        return Inertia::render('Badges', [
            'badges' => Badge::active()->ordered()->get()->map(fn (Badge $badge) => [
                ...$badge->toTile(),
                'earned_at' => $earned->has($badge->id) ? Carbon::parse($earned[$badge->id])->toIso8601String() : null,
                'progress' => $badges->progress($badge, $metrics),
            ]),
        ]);
    }
}
