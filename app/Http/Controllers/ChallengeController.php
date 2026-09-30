<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeOption;
use App\Services\AttemptService;
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

    public function show(Request $request, Challenge $challenge): Response
    {
        $user = $request->user();

        // Admins may preview anything; everyone else only sees what has gone live.
        abort_unless($challenge->isVisible() || $user->is_admin, 404);

        $challenge->load(['category', 'options']);
        $attempt = $user->attempts()->where('challenge_id', $challenge->id)->first();
        $isToday = $challenge->isDailyOn(today());

        // The answer, explanation and team split stay on the server until the user has answered.
        $picks = $attempt ? $challenge->attempts()->selectRaw('challenge_option_id, COUNT(*) as picks')
            ->groupBy('challenge_option_id')->pluck('picks', 'challenge_option_id') : collect();
        $totalPicks = (int) $picks->sum();

        return Inertia::render('challenges/Show', [
            'challenge' => [
                ...$challenge->toCard(),
                'question' => $challenge->question,
                'code_snippet' => $challenge->code_snippet,
                'code_language' => $challenge->code_language,
                'is_today' => $isToday,
                'is_preview' => ! $challenge->isVisible(),
                'reward' => $challenge->xpOn(today()),
                'explanation' => $attempt ? $challenge->explanation : null,
                'options' => $challenge->options->map(fn (ChallengeOption $option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    ...($attempt ? [
                        'is_correct' => $option->is_correct,
                        'percent' => $totalPicks > 0 ? (int) round(($picks[$option->id] ?? 0) / $totalPicks * 100) : 0,
                    ] : []),
                ]),
            ],
            'attempt' => $attempt ? [
                'option_id' => $attempt->challenge_option_id,
                'is_correct' => $attempt->is_correct,
                'is_daily' => $attempt->is_daily,
                'xp_awarded' => $attempt->xp_awarded,
                'submitted_at' => $attempt->submitted_at->toIso8601String(),
            ] : null,
            'team' => $attempt ? [
                'answers' => $totalPicks,
                'correct_percent' => $totalPicks > 0
                    ? (int) round($challenge->attempts()->where('is_correct', true)->count() / $totalPicks * 100)
                    : 0,
            ] : null,
            'nextUp' => Challenge::visible()
                ->whereKeyNot($challenge->id)
                ->whereDoesntHave('attempts', fn ($q) => $q->where('user_id', $user->id))
                ->orderByRaw('publish_date IS NULL')
                ->orderByDesc('publish_date')
                ->first()?->load('category')->toCard(),
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
