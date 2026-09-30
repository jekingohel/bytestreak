<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveController extends Controller
{
    private const STATES = ['all', 'todo', 'correct', 'wrong'];

    /** Every challenge that has gone live: the user's history plus anything still unplayed. */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $state = in_array($request->query('state'), self::STATES, true) ? $request->query('state') : 'all';
        $category = Category::where('slug', $request->query('category'))->first();
        $mine = fn ($q) => $q->where('user_id', $user->id);

        $challenges = Challenge::visible()
            ->with(['category', 'attempts' => $mine])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->when($state === 'todo', fn ($q) => $q->whereDoesntHave('attempts', $mine))
            ->when($state === 'correct', fn ($q) => $q->whereHas('attempts', fn ($a) => $a->where('user_id', $user->id)->where('is_correct', true)))
            ->when($state === 'wrong', fn ($q) => $q->whereHas('attempts', fn ($a) => $a->where('user_id', $user->id)->where('is_correct', false)))
            ->orderByRaw('publish_date IS NULL')
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->paginate(12)
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

        $played = $user->attempts()->count();

        return Inertia::render('Archive', [
            'challenges' => $challenges,
            'categories' => Category::ordered()->where('is_active', true)->get()->map->toChip(),
            'filters' => ['state' => $state, 'category' => $category?->slug],
            'totals' => [
                'available' => Challenge::visible()->count(),
                'played' => $played,
            ],
        ]);
    }
}
