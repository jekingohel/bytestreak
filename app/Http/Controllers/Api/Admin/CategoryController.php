<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Category::ordered()->withCount('challenges')->get()->map->toAdminRow()]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $category = Category::create([...$data, 'slug' => Category::uniqueSlug($data['name'])]);

        return response()->json(['data' => $category->loadCount('challenges')->toAdminRow()], 201);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json(['data' => $category->loadCount('challenges')->toAdminRow()]);
    }

    public function destroy(Category $category): Response
    {
        abort_if($category->challenges()->exists(), 409, 'This category still has challenges. Hide it instead, or move them first.');

        $category->delete();

        return response()->noContent();
    }
}
