<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelToDay(self::TODAY);
    }

    public function test_guests_get_a_json_401_instead_of_a_redirect(): void
    {
        $this->getJson(route('api.me'))->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->get('/api/v1/today')->assertUnauthorized();
    }

    public function test_me_returns_the_player_state(): void
    {
        $user = User::factory()->create(['name' => 'Ada Lovelace']);
        $this->makeChallenge();

        $this->actingAs($user)->getJson(route('api.me'))
            ->assertOk()
            ->assertJsonPath('data.initials', 'AL')
            ->assertJsonPath('data.has_today', true)
            ->assertJsonPath('data.done_today', false)
            ->assertJsonPath('data.level.level', 1);
    }

    public function test_today_hides_the_answer_until_the_user_has_answered(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge();

        $this->actingAs($user)->getJson(route('api.today'))
            ->assertOk()
            ->assertJsonPath('data.challenge.id', $challenge->id)
            ->assertJsonPath('data.challenge.explanation', null)
            ->assertJsonPath('data.attempt', null)
            ->assertJsonMissingPath('data.challenge.options.0.is_correct')
            ->assertJsonMissingPath('data.challenge.options.0.percent');
    }

    public function test_today_is_null_on_a_rest_day(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('api.today'))
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_answering_scores_through_the_attempt_service_and_reveals_the_answer(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge(['xp' => 20]);

        $this->actingAs($user)
            ->postJson(route('api.challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)])
            ->assertCreated()
            ->assertJsonPath('data.result.is_correct', true)
            ->assertJsonPath('data.result.is_daily', true)
            ->assertJsonPath('data.result.xp_total', 20)
            ->assertJsonPath('data.result.streak.current', 1)
            ->assertJsonPath('data.challenge.explanation', $challenge->explanation)
            ->assertJsonPath('data.challenge.options.0.is_correct', true)
            ->assertJsonPath('data.attempt.is_correct', true);

        $user->refresh();
        $this->assertSame(20, $user->xp);
        $this->assertSame(20, (int) $user->xpEntries()->sum('amount'));
    }

    public function test_a_second_answer_is_rejected_with_422(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge();

        $this->actingAs($user)->postJson(route('api.challenges.attempt', $challenge), ['option_id' => $this->wrongOption($challenge)])->assertCreated();
        $this->actingAs($user)->postJson(route('api.challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('option_id');

        $this->assertSame(1, $user->attempts()->count());
    }

    public function test_an_option_from_another_challenge_is_rejected(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge();
        $other = $this->makeChallenge(['publish_date' => '2026-09-22']);

        $this->actingAs($user)->postJson(route('api.challenges.attempt', $challenge), ['option_id' => $this->rightOption($other)])
            ->assertJsonValidationErrors('option_id');
    }

    public function test_unpublished_challenges_are_404_for_developers(): void
    {
        $user = User::factory()->create();
        $future = $this->makeChallenge(['status' => 'scheduled', 'publish_date' => '2026-10-02']);

        $this->actingAs($user)->getJson(route('api.challenges.show', $future))->assertNotFound();
        $this->actingAs($user)->postJson(route('api.challenges.attempt', $future), ['option_id' => $this->rightOption($future)])->assertNotFound();
    }

    public function test_the_archive_lists_live_challenges_and_filters_by_state(): void
    {
        $user = User::factory()->create();
        $played = $this->makeChallenge(['publish_date' => '2026-09-29']);
        $todo = $this->makeChallenge();
        $this->makeChallenge(['status' => 'scheduled', 'publish_date' => '2026-10-02']);

        $this->actingAs($user)->postJson(route('api.challenges.attempt', $played), ['option_id' => $this->rightOption($played)]);

        $this->actingAs($user)->getJson(route('api.challenges.index'))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonMissingPath('data.0.options');

        $this->actingAs($user)->getJson(route('api.challenges.index', ['state' => 'todo']))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $todo->id);

        $this->actingAs($user)->getJson(route('api.challenges.index', ['state' => 'nope']))
            ->assertJsonValidationErrors('state');
    }

    public function test_leaderboard_badges_and_stats(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge();
        $this->actingAs($user)->postJson(route('api.challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)]);

        $this->actingAs($user)->getJson(route('api.leaderboard'))
            ->assertOk()
            ->assertJsonPath('period', 'week')
            ->assertJsonPath('me.rank', 1)
            ->assertJsonPath('data.0.id', $user->id);

        $this->actingAs($user)->getJson(route('api.badges'))->assertOk()->assertJsonStructure(['data']);

        $this->actingAs($user)->getJson(route('api.me.stats', ['weeks' => 4]))
            ->assertOk()
            ->assertJsonPath('data.summary.completed', 1)
            ->assertJsonPath('data.rank.all', 1)
            ->assertJsonCount(4, 'data.heatmap.weeks')
            ->assertJsonCount(7, 'data.week');
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson(route('api.me.update'), ['name' => 'Grace Hopper', 'show_on_leaderboard' => false])
            ->assertOk()
            ->assertJsonPath('data.name', 'Grace Hopper')
            ->assertJsonPath('data.show_on_leaderboard', false);

        $this->actingAs($user)->putJson(route('api.me.password'), [
            'current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertJsonValidationErrors('current_password');
    }
}
