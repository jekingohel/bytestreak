<?php

namespace App\Http\Controllers;

use App\Models\XpEntry;
use App\Services\LeaderboardService;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

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
            'xpHistory' => $user->xpEntries()->latest('created_at')->latest('id')->limit(12)->get()
                ->map(fn (XpEntry $entry) => [
                    'id' => $entry->id,
                    'amount' => $entry->amount,
                    'reason' => $entry->reason,
                    'description' => $entry->description,
                    'created_at' => $entry->created_at->toIso8601String(),
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'show_on_leaderboard' => ['required', 'boolean'],
        ]);

        $request->user()->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile saved.']);

        return back();
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Password updated.']);

        return back();
    }
}
