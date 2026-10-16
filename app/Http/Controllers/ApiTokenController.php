<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Personal API tokens on the profile page. The plain-text token is flashed exactly once;
 * only its hash is stored.
 */
class ApiTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'expires_in_days' => ['required', 'integer', Rule::in(config('bytestreak.api_tokens.choices_days'))],
        ]);

        $days = (int) $data['expires_in_days'];
        $token = $request->user()->createToken($data['name'], ['*'], $days > 0 ? now()->addDays($days) : null);

        Inertia::flash('token', ['name' => $data['name'], 'plain_text' => $token->plainTextToken]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Token created. Copy it now — it will not be shown again.']);

        return back();
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        abort_unless($request->user()->tokens()->whereKey($token)->delete(), 404);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Token revoked. Anything using it has lost access.']);

        return back();
    }
}
