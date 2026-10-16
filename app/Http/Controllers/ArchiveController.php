<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Challenge;
use App\Services\ChallengeViewService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveController extends Controller
{
    /** Every challenge that has gone live: the user's history plus anything still unplayed. */
    public function __invoke(Request $request, ChallengeViewService $view): Response
    {
        $user = $request->user();
        $state = in_array($request->query('state'), ChallengeViewService::ARCHIVE_STATES, true) ? $request->query('state') : 'all';
        $category = Category::where('slug', $request->query('category'))->first();

        $challenges = $view->archive($user, $state, $category);

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
