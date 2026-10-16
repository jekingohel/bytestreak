<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Challenge;
use App\Models\ChallengeOption;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * What a developer sees of challenges, shared by the pages and the API.
 */
class ChallengeViewService
{
    public const ARCHIVE_STATES = ['all', 'todo', 'correct', 'wrong'];

    /**
     * One challenge as $user may see it. The answer, explanation and team split stay
     * on the server until the user has answered.
     *
     * @return array{challenge: array<string, mixed>, attempt: array<string, mixed>|null, team: array<string, int>|null}
     */
    public function detail(Challenge $challenge, User $user): array
    {
        $challenge->load(['category', 'options']);
        $attempt = $user->attempts()->where('challenge_id', $challenge->id)->first();

        $picks = $attempt ? $challenge->attempts()->selectRaw('challenge_option_id, COUNT(*) as picks')
            ->groupBy('challenge_option_id')->pluck('picks', 'challenge_option_id') : collect();
        $totalPicks = (int) $picks->sum();

        return [
            'challenge' => [
                ...$challenge->toCard(),
                'question' => $challenge->question,
                'code_snippet' => $challenge->code_snippet,
                'code_language' => $challenge->code_language,
                'is_today' => $challenge->isDailyOn(today()),
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
        ];
    }

    /** The newest live challenge $user has not played yet, other than $current. */
    public function nextUp(User $user, ?Challenge $current = null): ?array
    {
        return Challenge::visible()
            ->when($current, fn ($q) => $q->whereKeyNot($current->id))
            ->whereDoesntHave('attempts', fn ($q) => $q->where('user_id', $user->id))
            ->orderByRaw('publish_date IS NULL')
            ->orderByDesc('publish_date')
            ->first()?->load('category')->toCard();
    }

    /** Every challenge that has gone live: the user's history plus anything still unplayed. */
    public function archive(User $user, string $state = 'all', ?Category $category = null, int $perPage = 12): LengthAwarePaginator
    {
        $mine = fn ($q) => $q->where('user_id', $user->id);

        return Challenge::visible()
            ->with(['category', 'attempts' => $mine])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($state === 'todo', fn ($q) => $q->whereDoesntHave('attempts', $mine))
            ->when($state === 'correct', fn ($q) => $q->whereHas('attempts', fn ($a) => $a->where('user_id', $user->id)->where('is_correct', true)))
            ->when($state === 'wrong', fn ($q) => $q->whereHas('attempts', fn ($a) => $a->where('user_id', $user->id)->where('is_correct', false)))
            ->orderByRaw('publish_date IS NULL')
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Challenge $challenge) {
                $attempt = $challenge->attempts->first();

                return [
                    ...$challenge->toCard(),
                    'is_today' => $challenge->isDailyOn(today()),
                    'reward' => $challenge->xpOn(today()),
                    'attempt' => $attempt ? ['is_correct' => $attempt->is_correct, 'xp_awarded' => $attempt->xp_awarded] : null,
                ];
            });
    }
}
