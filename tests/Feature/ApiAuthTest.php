<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelToDay(self::TODAY);
    }

    private function login(User $user, string $password = 'password'): string
    {
        return $this->postJson(route('api.auth.login'), [
            'email' => $user->email, 'password' => $password, 'device_name' => 'phpunit',
        ])->assertCreated()->json('token');
    }

    /** Each call below should authenticate from its own header, not a guard cached by an earlier call. */
    private function freshRequest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    public function test_login_returns_a_bearer_token_that_authenticates_api_calls(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);

        $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'password'])
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonStructure(['token', 'expires_at']);

        $token = $this->login($user);

        $this->freshRequest()->withToken($token)->getJson(route('api.me'))
            ->assertOk()
            ->assertJsonPath('data.initials', 'AL');
    }

    public function test_a_token_can_answer_a_challenge_without_a_csrf_token(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge(['xp' => 20]);
        $token = $this->login($user);

        $this->freshRequest()->withToken($token)
            ->postJson(route('api.challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)])
            ->assertCreated()
            ->assertJsonPath('data.result.xp_total', 20);

        $this->assertSame(20, $user->fresh()->xp);
    }

    public function test_wrong_credentials_are_rejected_and_throttled(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'nope'])
                ->assertJsonValidationErrors(['email' => 'Those credentials do not match']);
        }

        $this->postJson(route('api.auth.login'), ['email' => $user->email, 'password' => 'password'])
            ->assertJsonValidationErrors(['email' => 'Too many attempts']);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_missing_or_bad_tokens_get_401(): void
    {
        $this->getJson(route('api.me'))->assertUnauthorized();
        $this->withToken('1|not-a-real-token')->getJson(route('api.me'))->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();
        $phone = $this->login($user);
        $laptop = $this->login($user);

        $this->freshRequest()->withToken($phone)->postJson(route('api.auth.logout'))->assertOk();

        $this->freshRequest()->withToken($phone)->getJson(route('api.me'))->assertUnauthorized();
        $this->freshRequest()->withToken($laptop)->getJson(route('api.me'))->assertOk();

        $this->freshRequest()->withToken($laptop)->postJson(route('api.auth.logout-everywhere'))->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_tokens_expire(): void
    {
        $token = $this->login(User::factory()->create());

        $this->travelToDay('2026-11-01 10:00:00');

        $this->freshRequest()->withToken($token)->getJson(route('api.me'))->assertUnauthorized();
    }

    public function test_admin_endpoints_check_is_admin_for_token_users_too(): void
    {
        $dev = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $devToken = $this->login($dev);
        $adminToken = $this->login($admin);

        $this->freshRequest()->withToken($devToken)->getJson(route('api.admin.users.index'))->assertForbidden();
        $this->freshRequest()->withToken($adminToken)->getJson(route('api.admin.users.index'))->assertOk();
    }
}
