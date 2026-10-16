<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => User::query()
            ->withCount(['attempts', 'attempts as correct_count' => fn ($q) => $q->where('is_correct', true)])
            ->orderByDesc('xp')
            ->orderBy('name')
            ->get()
            ->map->toAdminRow()]);
    }

    /** Only admin access can be changed here; game state is never edited directly. */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate(['is_admin' => ['required', 'boolean']]);

        if ($user->is($request->user()) && ! $data['is_admin']) {
            throw ValidationException::withMessages(['is_admin' => 'You cannot remove your own admin access.']);
        }

        $user->forceFill(['is_admin' => $data['is_admin']])->save();

        return response()->json(['data' => $user->loadCount([
            'attempts', 'attempts as correct_count' => fn ($q) => $q->where('is_correct', true),
        ])->toAdminRow()]);
    }
}
