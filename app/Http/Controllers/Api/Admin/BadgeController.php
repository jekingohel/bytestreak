<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BadgeRequest;
use App\Models\Badge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BadgeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Badge::ordered()->withCount('users')->get()->map->toAdminRow()]);
    }

    public function store(BadgeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $badge = Badge::create([...$data, 'slug' => Badge::uniqueSlug($data['name'])]);

        return response()->json(['data' => $badge->loadCount('users')->toAdminRow()], 201);
    }

    public function update(BadgeRequest $request, Badge $badge): JsonResponse
    {
        $badge->update($request->validated());

        return response()->json(['data' => $badge->loadCount('users')->toAdminRow()]);
    }

    public function destroy(Badge $badge): Response
    {
        abort_if($badge->users()->exists(), 409, 'People have earned this badge. Turn it off instead of deleting it.');

        $badge->delete();

        return response()->noContent();
    }
}
