<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Level;
use App\Services\StreakService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(StreakService $streaks): Response
    {
        return Inertia::render('admin/Users', [
            'users' => User::query()
                ->withCount(['attempts', 'attempts as correct_count' => fn ($q) => $q->where('is_correct', true)])
                ->orderByDesc('xp')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    ...$user->toIdentity(),
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'xp' => $user->xp,
                    'level' => Level::forXp($user->xp),
                    'streak' => $streaks->current($user),
                    'attempts' => $user->attempts_count,
                    'accuracy' => $user->attempts_count > 0 ? (int) round($user->correct_count / $user->attempts_count * 100) : null,
                    'last_completed_on' => $user->last_completed_on?->toDateString(),
                    'joined' => $user->created_at->toDateString(),
                ]),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['is_admin' => ['required', 'boolean']]);

        if ($user->is($request->user()) && ! $data['is_admin']) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'You cannot remove your own admin access.']);

            return back();
        }

        $user->forceFill(['is_admin' => $data['is_admin']])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => $data['is_admin']
            ? "{$user->name} is now an admin."
            : "{$user->name} is no longer an admin."]);

        return back();
    }
}
