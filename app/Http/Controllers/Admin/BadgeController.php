<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BadgeCriteria;
use App\Http\Controllers\Controller;
use App\Http\Requests\BadgeRequest;
use App\Models\Badge;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BadgeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Badges', [
            'badges' => Badge::ordered()->withCount('users')->get()->map->toAdminRow(),
            'criteria' => BadgeCriteria::options(),
            'categories' => Category::ordered()->get()->map->toChip(),
        ]);
    }

    public function store(BadgeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Badge::create([...$data, 'slug' => Badge::uniqueSlug($data['name'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Badge added.']);

        return back();
    }

    public function update(BadgeRequest $request, Badge $badge): RedirectResponse
    {
        $badge->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Badge saved.']);

        return back();
    }

    public function destroy(Badge $badge): RedirectResponse
    {
        if ($badge->users()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'People have earned this badge. Turn it off instead of deleting it.']);

            return back();
        }

        $badge->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Badge deleted.']);

        return back();
    }
}
