<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BadgeCriteria;
use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BadgeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Badges', [
            'badges' => Badge::ordered()->withCount('users')->get()->map(fn (Badge $badge) => [
                ...$badge->toTile(),
                'criteria_type' => $badge->criteria_type->value,
                'criteria_label' => $badge->criteria_type->label(),
                'criteria_value' => $badge->criteria_value,
                'category_id' => $badge->category_id,
                'sort_order' => $badge->sort_order,
                'is_active' => $badge->is_active,
                'earned_by' => $badge->users_count,
            ]),
            'criteria' => BadgeCriteria::options(),
            'categories' => Category::ordered()->get()->map->toChip(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Badge::create([...$data, 'slug' => $this->uniqueSlug($data['name'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Badge added.']);

        return back();
    }

    public function update(Request $request, Badge $badge): RedirectResponse
    {
        $badge->update($this->validated($request, $badge));

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

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Badge $badge = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('badges', 'name')->ignore($badge)],
            'description' => ['required', 'string', 'max:255'],
            'emoji' => ['required', 'string', 'max:16'],
            'criteria_type' => ['required', Rule::enum(BadgeCriteria::class)],
            'criteria_value' => ['required', 'integer', 'min:1', 'max:100000'],
            'category_id' => [
                Rule::requiredIf($request->input('criteria_type') === BadgeCriteria::CategoryCorrect->value),
                'nullable', 'exists:categories,id',
            ],
            'xp_bonus' => ['required', 'integer', 'min:0', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($data['criteria_type'] !== BadgeCriteria::CategoryCorrect->value) {
            $data['category_id'] = null;
        }

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'badge';
        $slug = $base;
        for ($i = 2; Badge::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
