<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeOption;
use App\Services\AttemptService;
use App\Services\ChallengeViewService;
use App\Services\DailyChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeController extends Controller
{
    public function today(DailyChallengeService $daily): RedirectResponse
    {
        $challenge = $daily->today();

        if (! $challenge) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'No challenge today — enjoy the break, or pick one from the archive.']);

            return redirect()->route('archive');
        }

        return redirect()->route('challenges.show', $challenge);
    }

    public function show(Request $request, Challenge $challenge, ChallengeViewService $view): Response
    {
        $user = $request->user();

        // Admins may preview anything; everyone else only sees what has gone live.
        abort_unless($challenge->isVisible() || $user->is_admin, 404);

        return Inertia::render('challenges/Show', [
            ...$view->detail($challenge, $user),
            'nextUp' => $view->nextUp($user, $challenge),
            'nextChallengeAt' => now()->addDay()->startOfDay()->toIso8601String(),
        ]);
    }

    public function attempt(Request $request, Challenge $challenge, AttemptService $attempts): RedirectResponse
    {
        abort_unless($challenge->isVisible(), 404);

        $data = $request->validate([
            'option_id' => ['required', 'integer', Rule::exists('challenge_options', 'id')->where('challenge_id', $challenge->id)],
        ]);

        $result = $attempts->submit($request->user(), $challenge, ChallengeOption::findOrFail($data['option_id']));

        // One-time celebration payload: XP breakdown, streak change, new badges, level-up.
        Inertia::flash('result', $result);

        return redirect()->route('challenges.show', $challenge);
    }
}
