<?php

namespace Tests\Feature;

use App\Enums\ChallengeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminChallengesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelToDay(self::TODAY);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function payload(array $overrides = []): array
    {
        return [
            'category_id' => Category::firstOrCreate(['slug' => 'php'], ['name' => 'PHP'])->id,
            'type' => 'true_false',
            'difficulty' => 'easy',
            'title' => 'PHP arrays are ordered maps',
            'question' => 'True or false?',
            'code_snippet' => null,
            'code_language' => null,
            'explanation' => 'They are.',
            'xp' => 10,
            'status' => 'scheduled',
            'publish_date' => '2026-10-01',
            'options' => [['label' => 'True'], ['label' => 'False']],
            'correct_index' => 0,
            ...$overrides,
        ];
    }

    public function test_developers_cannot_open_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('admin.challenges.store'), $this->payload())->assertForbidden();
    }

    public function test_an_admin_can_create_a_scheduled_challenge_with_options(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.challenges.store'), $this->payload())
            ->assertRedirect(route('admin.challenges.index'));

        $challenge = Challenge::with('options')->sole();
        $this->assertSame(ChallengeStatus::Scheduled, $challenge->status);
        $this->assertSame('2026-10-01', $challenge->publish_date->toDateString());
        $this->assertSame(['True', 'False'], $challenge->options->pluck('label')->all());
        $this->assertTrue($challenge->options[0]->is_correct);
        $this->assertFalse($challenge->options[1]->is_correct);
    }

    public function test_two_challenges_cannot_share_a_date(): void
    {
        $this->makeChallenge(['status' => ChallengeStatus::Scheduled, 'publish_date' => '2026-10-01']);

        $this->actingAs($this->admin())
            ->post(route('admin.challenges.store'), $this->payload())
            ->assertSessionHasErrors('publish_date');
    }

    public function test_a_correct_answer_must_be_marked(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.challenges.store'), $this->payload(['correct_index' => 5]))
            ->assertSessionHasErrors('correct_index');
    }

    public function test_editing_keeps_option_ids_so_existing_answers_stay_linked(): void
    {
        $admin = $this->admin();
        $challenge = $this->makeChallenge();
        $player = User::factory()->create();
        $this->actingAs($player)->post(route('challenges.attempt', $challenge), ['option_id' => $this->wrongOption($challenge)]);

        $options = $challenge->options->map(fn ($o) => ['id' => $o->id, 'label' => $o->label.' (edited)'])->all();

        $this->actingAs($admin)
            ->put(route('admin.challenges.update', $challenge), $this->payload([
                'type' => 'multiple_choice', 'status' => 'published', 'publish_date' => '2026-09-30',
                'options' => $options, 'correct_index' => 0,
            ]))
            ->assertRedirect(route('admin.challenges.index'));

        $this->assertSame($challenge->options->pluck('id')->all(), $challenge->refresh()->options->pluck('id')->all());
        $this->assertSame('Right (edited)', $challenge->options[0]->label);

        // Dropping the option somebody picked is refused.
        $this->actingAs($admin)
            ->put(route('admin.challenges.update', $challenge), $this->payload([
                'type' => 'multiple_choice', 'status' => 'published', 'publish_date' => '2026-09-30',
                'options' => [$options[0], $options[2]], 'correct_index' => 0,
            ]))
            ->assertSessionHasErrors('options');
    }

    public function test_publish_now_makes_it_todays_challenge_when_the_day_is_free(): void
    {
        $draft = $this->makeChallenge(['status' => ChallengeStatus::Draft, 'publish_date' => null]);

        $this->actingAs($this->admin())->post(route('admin.challenges.publish', $draft));

        $draft->refresh();
        $this->assertSame(ChallengeStatus::Published, $draft->status);
        $this->assertSame('2026-09-30', $draft->publish_date->toDateString());
    }

    public function test_publish_now_becomes_a_bonus_when_today_is_taken(): void
    {
        $this->makeChallenge();
        $draft = $this->makeChallenge(['status' => ChallengeStatus::Draft, 'publish_date' => null]);

        $this->actingAs($this->admin())->post(route('admin.challenges.publish', $draft));

        $draft->refresh();
        $this->assertSame(ChallengeStatus::Published, $draft->status);
        $this->assertNull($draft->publish_date);
    }

    public function test_answered_challenges_cannot_be_deleted(): void
    {
        $challenge = $this->makeChallenge();
        $player = User::factory()->create();
        $this->actingAs($player)->post(route('challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)]);

        $this->actingAs($this->admin())->delete(route('admin.challenges.destroy', $challenge));

        $this->assertModelExists($challenge);
    }
}
