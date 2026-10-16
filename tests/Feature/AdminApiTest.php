<?php

namespace Tests\Feature;

use App\Enums\ChallengeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
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

    public function test_developers_get_403_from_the_admin_api(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('api.admin.challenges.index'))->assertForbidden();
        $this->actingAs(User::factory()->create())->postJson(route('api.admin.categories.store'), [])->assertForbidden();
    }

    public function test_an_admin_can_create_read_and_list_a_challenge(): void
    {
        $admin = $this->admin();

        $id = $this->actingAs($admin)->postJson(route('api.admin.challenges.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.challenge.status', 'scheduled')
            ->assertJsonPath('data.challenge.options.0.is_correct', true)
            ->assertJsonPath('data.form.correct_index', 0)
            ->json('data.challenge.id');

        $this->assertSame($admin->id, Challenge::find($id)->created_by);

        $this->actingAs($admin)->getJson(route('api.admin.challenges.index', ['status' => 'scheduled']))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $id);
    }

    public function test_validation_errors_come_back_as_422(): void
    {
        $this->makeChallenge(['status' => 'scheduled', 'publish_date' => '2026-10-01']);

        $this->actingAs($this->admin())->postJson(route('api.admin.challenges.store'), $this->payload(['correct_index' => 5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['publish_date']);
    }

    public function test_updating_keeps_option_ids_so_existing_answers_stay_linked(): void
    {
        $admin = $this->admin();
        $challenge = $this->makeChallenge();
        $player = User::factory()->create();
        $this->actingAs($player)->postJson(route('api.challenges.attempt', $challenge), ['option_id' => $this->rightOption($challenge)]);

        $form = $this->actingAs($admin)->getJson(route('api.admin.challenges.show', $challenge))->json('data.form');
        $form['options'][0]['label'] = 'Right (reworded)';

        $this->actingAs($admin)->putJson(route('api.admin.challenges.update', $challenge), $form)
            ->assertOk()
            ->assertJsonPath('data.challenge.options.0.label', 'Right (reworded)')
            ->assertJsonPath('data.challenge.options.0.picks', 1);

        array_shift($form['options']);
        $this->actingAs($admin)->putJson(route('api.admin.challenges.update', $challenge), $form)
            ->assertJsonValidationErrors('options');
    }

    public function test_publish_queue_archive_and_delete(): void
    {
        $admin = $this->admin();
        $draft = $this->makeChallenge(['status' => 'draft', 'publish_date' => null]);

        $this->actingAs($admin)->postJson(route('api.admin.challenges.publish', $draft))
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.is_today', true);

        $this->actingAs(User::factory()->create())->postJson(route('api.challenges.attempt', $draft), ['option_id' => $this->rightOption($draft)]);

        $this->actingAs($admin)->postJson(route('api.admin.challenges.queue', $draft))->assertStatus(409);
        $this->actingAs($admin)->deleteJson(route('api.admin.challenges.destroy', $draft))
            ->assertStatus(409)
            ->assertJsonPath('message', 'People have already answered this one — archive it instead of deleting.');

        $this->actingAs($admin)->postJson(route('api.admin.challenges.archive', $draft))->assertJsonPath('data.status', 'archived');
        $this->assertSame(ChallengeStatus::Archived, $draft->fresh()->status);

        $unused = $this->makeChallenge(['status' => 'draft', 'publish_date' => null]);
        $this->actingAs($admin)->deleteJson(route('api.admin.challenges.destroy', $unused))->assertNoContent();
        $this->assertModelMissing($unused);
    }

    public function test_categories_and_badges_crud(): void
    {
        $admin = $this->admin();
        $category = ['name' => 'Rust', 'emoji' => '🦀', 'color' => '#DEA584', 'description' => null, 'sort_order' => 5, 'is_active' => true];

        $id = $this->actingAs($admin)->postJson(route('api.admin.categories.store'), $category)
            ->assertCreated()
            ->assertJsonPath('data.slug', 'rust')
            ->assertJsonPath('data.challenges', 0)
            ->json('data.id');

        $this->actingAs($admin)->postJson(route('api.admin.categories.store'), $category)->assertJsonValidationErrors('name');
        $this->actingAs($admin)->putJson(route('api.admin.categories.update', $id), [...$category, 'is_active' => false])
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($admin)->postJson(route('api.admin.badges.store'), [
            'name' => 'Crab Whisperer', 'description' => 'Five correct Rust answers.', 'emoji' => '🦀',
            'criteria_type' => 'correct_total', 'criteria_value' => 5, 'category_id' => $id,
            'xp_bonus' => 15, 'sort_order' => 1, 'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.criteria_label', 'Correct answers')
            // Only category badges keep a category.
            ->assertJsonPath('data.category_id', null);

        $this->actingAs($admin)->deleteJson(route('api.admin.categories.destroy', $id))->assertNoContent();
    }

    public function test_admins_can_promote_others_but_not_demote_themselves(): void
    {
        $admin = $this->admin();
        $dev = User::factory()->create();

        $this->actingAs($admin)->patchJson(route('api.admin.users.update', $dev), ['is_admin' => true])
            ->assertOk()
            ->assertJsonPath('data.is_admin', true);

        $this->actingAs($admin)->patchJson(route('api.admin.users.update', $admin), ['is_admin' => false])
            ->assertJsonValidationErrors('is_admin');

        $this->actingAs($admin)->getJson(route('api.admin.users.index'))->assertOk()->assertJsonCount(2, 'data');
    }
}
