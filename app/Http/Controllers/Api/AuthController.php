<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Exchange email + password for a Bearer token. Same throttle as the login page:
     * five failures per email and IP, then a wait.
     */
    public function login(Request $request, ProgressService $progress): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => 'Those credentials do not match our records.']);
        }

        RateLimiter::clear($throttleKey);

        $days = (int) config('bytestreak.api_tokens.login_days');
        $token = $user->createToken($credentials['device_name'] ?? 'api', ['*'], $days > 0 ? now()->addDays($days) : null);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'data' => $progress->player($user),
        ], 201);
    }

    /** Revoke the token this request was made with. (A browser session signs out via POST /logout.) */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Signed out.']);
    }

    /** Revoke every token the user has, e.g. after losing a device. */
    public function logoutEverywhere(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Signed out on every device.']);
    }
}
