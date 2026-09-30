<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Login', [
            // Local-only shortcut: one-click sign-in as any seeded account while developing.
            'devUsers' => self::devLoginEnabled()
                ? User::orderByDesc('is_admin')->orderByDesc('xp')->limit(6)->get()
                    ->map(fn (User $u) => [...$u->toIdentity(), 'is_admin' => $u->is_admin, 'xp' => $u->xp])
                : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => 'Those credentials do not match our records.']);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public static function devLoginEnabled(): bool
    {
        return app()->isLocal() && config('bytestreak.dev_login');
    }

    /** Local environment only — the route is not registered anywhere else. */
    public function devLogin(Request $request, User $user): RedirectResponse
    {
        abort_unless(self::devLoginEnabled(), 404);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
