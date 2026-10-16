<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Categories', [
            'categories' => Category::ordered()->withCount('challenges')->get()->map->toAdminRow(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Category::create([...$data, 'slug' => Category::uniqueSlug($data['name'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category added.']);

        return back();
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category saved.']);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->challenges()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This category still has challenges. Hide it instead, or move them first.']);

            return back();
        }

        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return back();
    }
}
