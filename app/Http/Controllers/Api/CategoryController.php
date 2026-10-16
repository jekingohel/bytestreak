<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /** Active categories; their slugs are the `category` filter of GET /challenges. */
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => Category::ordered()->where('is_active', true)->get()->map->toChip()]);
    }
}
