<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ApiTokenTest extends TestCase
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

    public function test_an_admin_creates_a_token_and_sees_it_exactly_once(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('profile'))
            ->post(route('profile.tokens.store'), ['name' => 'ByteStreak MCP', 'expires_in_days' => 365])
            ->assertRedirect(route('profile'))
            ->assertInertiaFlash('token.name', 'ByteStreak MCP');

        $token = $admin->tokens()->sole();
        $this->assertSame('ByteStreak MCP', $token->name);
        $this->assertTrue($token->expires_at->isSameDay(today()->addDays(365)));

        // The list shows metadata only, never the token itself.
        $this->actingAs($admin)->get(route('profile'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('apiTokens.tokens.0.name', 'ByteStreak MCP')
                ->where('apiTokens.tokens.0.last_used_at', null)
                ->missing('apiTokens.tokens.0.token')
                ->missing('apiTokens.tokens.0.plain_text'));
    }

    public function test_a_never_expiring_token_has_no_expiry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('profile.tokens.store'), ['name' => 'CI', 'expires_in_days' => 0]);

        $this->assertNull($admin->tokens()->sole()->expires_at);
        $this->actingAs($admin)->post(route('profile.tokens.store'), ['name' => 'CI', 'expires_in_days' => 7])
            ->assertSessionHasErrors('expires_in_days');
    }

    public function test_developers_cannot_create_tokens_or_see_the_section(): void
    {
        $dev = User::factory()->create();

        $this->actingAs($dev)->post(route('profile.tokens.store'), ['name' => 'x', 'expires_in_days' => 30])->assertForbidden();
        $this->actingAs($dev)->get(route('profile'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('apiTokens', null));
    }

    public function test_a_token_can_only_be_revoked_by_its_owner(): void
    {
        $admin = $this->admin();
        $other = $this->admin();
        $token = $admin->createToken('mine')->accessToken;

        $this->actingAs($other)->delete(route('profile.tokens.destroy', $token->id))->assertNotFound();
        $this->actingAs($admin)->delete(route('profile.tokens.destroy', $token->id))->assertRedirect();

        $this->assertSame(0, $admin->tokens()->count());
    }

    public function test_a_profile_token_can_discover_options_and_add_a_question(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'SQL', 'slug' => 'sql']);
        $plain = $admin->createToken('ByteStreak MCP', ['*'], now()->addYear())->plainTextToken;

        $this->withToken($plain)->getJson(route('api.admin.challenges.options'))
            ->assertOk()
            ->assertJsonPath('data.categories.0.id', $category->id)
            ->assertJsonPath('data.difficulties.0.value', 'easy')
            ->assertJsonPath('data.statuses', ['draft', 'scheduled', 'published', 'archived']);

        $this->withToken($plain)->postJson(route('api.admin.challenges.store'), [
            'category_id' => $category->id,
            'type' => 'sql',
            'difficulty' => 'medium',
            'title' => 'COUNT(*) vs COUNT(column)',
            'question' => 'Which one skips NULLs?',
            'explanation' => 'COUNT(column) ignores NULL values; COUNT(*) counts rows.',
            'xp' => 20,
            'status' => 'draft',
            'publish_date' => null,
            'options' => [['label' => 'COUNT(column)'], ['label' => 'COUNT(*)']],
            'correct_index' => 0,
        ])
            ->assertCreated()
            ->assertJsonPath('data.challenge.status', 'draft')
            ->assertJsonPath('data.challenge.options.0.is_correct', true);

        $this->assertNotNull($admin->tokens()->sole()->last_used_at);
    }
}
