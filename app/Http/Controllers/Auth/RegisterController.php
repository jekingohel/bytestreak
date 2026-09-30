<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/Register', [
            'allowedDomain' => config('bytestreak.allowed_email_domain'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $domain = config('bytestreak.allowed_email_domain');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email',
                function (string $attribute, mixed $value, Closure $fail) use ($domain) {
                    if ($domain && ! Str::endsWith(Str::lower((string) $value), '@'.Str::lower($domain))) {
                        $fail("Sign-up is limited to @{$domain} email addresses.");
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
