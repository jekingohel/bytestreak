<?php

namespace Tests\Feature;

use App\Enums\BadgeCriteria;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PlayingChallengesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelToDay(self::TODAY);
    }

    public function test_a_correct_daily_answer_awards_full_xp_and_starts_the_streak(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge(['xp' => 20]);

        $this->actingAs($user)
            ->post(route('challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)])
            ->assertRedirect(route('challenges.show', $challenge));

        $user->refresh();
        $this->assertSame(20, $user->xp);
        $this->assertSame(1, $user->current_streak);
        $this->assertSame(20, (int) $user->xpEntries()->sum('amount'));
        $this->assertDatabaseHas('challenge_attempts', [
            'user_id' => $user->id, 'challenge_id' => $challenge->id, 'is_correct' => true, 'is_daily' => true, 'xp_awarded' => 20,
        ]);
    }

    public function test_a_wrong_daily_answer_still_earns_participation_xp_and_keeps_the_streak(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge();

        $this->actingAs($user)->post(route('challenges.attempt', $challenge), ['option_id' => $this->wrongOption($challenge)]);

        $user->refresh();
        $this->assertSame(config('bytestreak.xp.participation'), $user->xp);
        $this->assertSame(1, $user->current_streak);
    }

    public function test_the_streak_bonus_grows_with_consecutive_days(): void
    {
        $user = User::factory()->create();

        $this->travelToDay('2026-09-29 10:00:00');
        $tuesday = $this->makeChallenge(['xp' => 10]);
        $this->actingAs($user)->post(route('challenges.attempt', $tuesday), ['option_id' => $this->rightOption($tuesday)]);

        $this->travelToDay('2026-09-30 10:00:00');
        $wednesday = $this->makeChallenge(['xp' => 10]);
        $this->actingAs($user)->post(route('challenges.attempt', $wednesday), ['option_id' => $this->rightOption($wednesday)]);

        $user->refresh();
        $this->assertSame(2, $user->current_streak);
        // 10 (day one) + 10 + 1 streak bonus (day two)
        $this->assertSame(21, $user->xp);
    }

    public function test_a_day_without_a_challenge_does_not_break_the_streak(): void
    {
        $user = User::factory()->create();

        $this->travelToDay('2026-09-25 10:00:00'); // Friday
        $friday = $this->makeChallenge();
        $this->actingAs($user)->post(route('challenges.attempt', $friday), ['option_id' => $this->rightOption($friday)]);

        $this->travelToDay('2026-09-28 10:00:00'); // Monday — nothing was published over the weekend
        $monday = $this->makeChallenge();
        $this->actingAs($user)->post(route('challenges.attempt', $monday), ['option_id' => $this->rightOption($monday)]);

        $this->assertSame(2, $user->refresh()->current_streak);
    }

    public function test_missing_a_published_challenge_resets_the_streak(): void
    {
        $user = User::factory()->create();

        $this->travelToDay('2026-09-28 10:00:00');
        $monday = $this->makeChallenge();
        $this->actingAs($user)->post(route('challenges.attempt', $monday), ['option_id' => $this->rightOption($monday)]);

        $this->travelToDay('2026-09-29 10:00:00');
        $this->makeChallenge(); // published, but the user never plays it

        $this->travelToDay('2026-09-30 10:00:00');
        $wednesday = $this->makeChallenge();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user.streak', 0));

        $this->actingAs($user)->post(route('challenges.attempt', $wednesday), ['option_id' => $this->rightOption($wednesday)]);

        $user->refresh();
        $this->assertSame(1, $user->current_streak);
        $this->assertSame(1, $user->longest_streak);
    }

    public function test_a_streak_freeze_absorbs_a_missed_day(): void
    {
        $user = User::factory()->create();

        $this->travelToDay('2026-09-28 10:00:00');
        $monday = $this->makeChallenge();
        $this->actingAs($user)->post(route('challenges.attempt', $monday), ['option_id' => $this->rightOption($monday)]);
        $user->forceFill(['streak_freezes' => 1])->save();

        $this->travelToDay('2026-09-29 10:00:00');
        $this->makeChallenge(); // missed

        $this->travelToDay('2026-09-30 10:00:00');
        $wednesday = $this->makeChallenge();
        $this->actingAs($user)->post(route('challenges.attempt', $wednesday), ['option_id' => $this->rightOption($wednesday)]);

        $user->refresh();
        $this->assertSame(2, $user->current_streak);
        $this->assertSame(0, $user->streak_freezes);
    }

    public function test_an_archive_challenge_pays_half_and_leaves_the_streak_alone(): void
    {
        $user = User::factory()->create();
        $old = $this->makeChallenge(['xp' => 20, 'publish_date' => '2026-09-22']);

        $this->actingAs($user)->post(route('challenges.attempt', $old), ['option_id' => $this->rightOption($old)]);

        $user->refresh();
        $this->assertSame(10, $user->xp);
        $this->assertSame(0, $user->current_streak);
        $this->assertDatabaseHas('challenge_attempts', ['challenge_id' => $old->id, 'is_daily' => false]);
    }

    public function test_a_challenge_can_only_be_answered_once(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge(['xp' => 20]);

        $this->actingAs($user)->post(route('challenges.attempt', $challenge), ['option_id' => $this->wrongOption($challenge)]);
        $this->actingAs($user)->post(route('challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)])
            ->assertSessionHasErrors('option_id');

        $this->assertSame(1, $user->attempts()->count());
        $this->assertSame(config('bytestreak.xp.participation'), $user->refresh()->xp);
    }

    public function test_an_option_from_another_challenge_is_rejected(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge();
        $other = $this->makeChallenge(['publish_date' => '2026-09-22']);

        $this->actingAs($user)->post(route('challenges.attempt', $challenge), ['option_id' => $this->rightOption($other)])
            ->assertSessionHasErrors('option_id');

        $this->assertSame(0, $user->attempts()->count());
    }

    public function test_the_answer_is_hidden_until_the_user_has_answered(): void
    {
        $user = User::factory()->create();
        $challenge = $this->makeChallenge();

        $this->actingAs($user)->get(route('challenges.show', $challenge))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('challenges/Show')
                ->where('challenge.explanation', null)
                ->where('attempt', null)
                ->missing('challenge.options.0.is_correct')
                ->missing('challenge.options.0.percent'));

        $this->actingAs($user)->post(route('challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)]);

        $this->actingAs($user)->get(route('challenges.show', $challenge))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('challenge.explanation', $challenge->explanation)
                ->where('challenge.options.0.is_correct', true)
                ->where('challenge.options.0.percent', 100)
                ->where('attempt.is_correct', true));
    }

    public function test_unpublished_challenges_are_not_reachable_by_developers(): void
    {
        $user = User::factory()->create();
        $future = $this->makeChallenge(['status' => 'scheduled', 'publish_date' => '2026-10-02']);

        $this->actingAs($user)->get(route('challenges.show', $future))->assertNotFound();
        $this->actingAs($user)->post(route('challenges.attempt', $future), ['option_id' => $this->rightOption($future)])->assertNotFound();
    }

    public function test_badges_unlock_and_pay_their_bonus(): void
    {
        $user = User::factory()->create();
        $badge = Badge::create([
            'slug' => 'first-commit', 'name' => 'First Commit', 'description' => 'Answer your first challenge.',
            'criteria_type' => BadgeCriteria::AttemptsTotal, 'criteria_value' => 1, 'xp_bonus' => 10,
        ]);
        $challenge = $this->makeChallenge(['xp' => 20]);

        $this->actingAs($user)->post(route('challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)]);

        $user->refresh();
        $this->assertTrue($user->badges->contains($badge));
        $this->assertSame(30, $user->xp);
        $this->assertSame(30, (int) $user->xpEntries()->sum('amount'));
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('home'))->assertOk();
    }
}
