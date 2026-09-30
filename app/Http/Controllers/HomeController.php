<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /** Public landing page. Signed-in developers go straight to their dashboard. */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Landing', [
            'categories' => Category::ordered()->where('is_active', true)->get()->map->toChip(),
            'stats' => [
                'players' => User::count(),
                'challenges' => Challenge::visible()->count(),
            ],
        ]);
    }
}
