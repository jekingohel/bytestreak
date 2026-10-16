<?php

namespace Tests\Feature;

use App\Enums\ChallengeStatus;
use App\Mcp\Servers\ByteStreakServer;
use App\Mcp\Tools\CreateChallengeTool;
use App\Mcp\Tools\GetChallengeOptionsTool;
use App\Mcp\Tools\SearchChallengesTool;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private Category $sql;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelToDay(self::TODAY);
        $this->sql = Category::create(['name' => 'SQL', 'slug' => 'sql', 'emoji' => '🗄️']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    /** @return array<string, mixed> */
    private function question(array $overrides = []): array
    {
        return [
            'category_id' => $this->sql->id,
            'type' => 'sql',
            'difficulty' => 'medium',
            'title' => 'COUNT(*) vs COUNT(column)',
            'question' => 'The table has 5 rows and 2 of them have email = NULL. What does the query return?',
            'code_snippet' => 'SELECT COUNT(email) FROM users;',
            'code_language' => 'sql',
            'options' => ['5', '3', '2', 'NULL'],
            'correct_option' => 1,
            'explanation' => 'COUNT(column) skips NULLs, so only 3 rows are counted. COUNT(*) would return 5.',
            ...$overrides,
        ];
    }

    public function test_create_challenge_adds_a_draft_with_the_right_answer(): void
    {
        $admin = $this->admin();

        ByteStreakServer::actingAs($admin)->tool(CreateChallengeTool::class, $this->question())
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Created “COUNT(*) vs COUNT(column)” as draft.');

        $challenge = Challenge::with('options')->sole();
        $this->assertSame(ChallengeStatus::Draft, $challenge->status);
        $this->assertSame($admin->id, $challenge->created_by);
        $this->assertSame(20, $challenge->xp); // the medium default, from config
        $this->assertSame(['5', '3', '2', 'NULL'], $challenge->options->pluck('label')->all());
        $this->assertSame('3', $challenge->options->firstWhere('is_correct', true)->label);
    }

    public function test_it_can_join_the_queue_or_take_a_free_date(): void
    {
        $admin = $this->admin();

        ByteStreakServer::actingAs($admin)->tool(CreateChallengeTool::class, $this->question(['status' => 'scheduled']))->assertHasNoErrors();
        ByteStreakServer::actingAs($admin)->tool(CreateChallengeTool::class, $this->question([
            'title' => 'Another one', 'status' => 'scheduled', 'publish_date' => '2026-10-05',
        ]))->assertHasNoErrors();

        $this->assertSame(1, Challenge::queued()->count());
        $this->assertTrue(Challenge::whereDate('publish_date', '2026-10-05')->exists());
    }

    public function test_the_admin_form_rules_apply(): void
    {
        $admin = $this->admin();
        $this->makeChallenge(['status' => 'scheduled', 'publish_date' => '2026-10-05']);

        ByteStreakServer::actingAs($admin)->tool(CreateChallengeTool::class, $this->question(['status' => 'scheduled', 'publish_date' => '2026-10-05']))
            ->assertHasErrors(['Another challenge already owns that date.']);

        ByteStreakServer::actingAs($admin)->tool(CreateChallengeTool::class, $this->question(['options' => ['Only one']]))
            ->assertHasErrors();

        ByteStreakServer::actingAs($admin)->tool(CreateChallengeTool::class, $this->question(['correct_option' => 4]))
            ->assertHasErrors(['Mark one option as the correct answer.']);

        $this->assertSame(1, Challenge::count());
    }

    public function test_developers_cannot_use_the_tools(): void
    {
        $dev = User::factory()->create();

        ByteStreakServer::actingAs($dev)->tool(CreateChallengeTool::class, $this->question())
            ->assertHasErrors(['Only ByteStreak admins can add questions.']);

        $this->assertSame(0, Challenge::count());
    }

    public function test_options_and_search_help_the_assistant_avoid_mistakes(): void
    {
        $admin = $this->admin();
        $this->makeChallenge(['title' => 'Strict comparison in PHP', 'question' => 'What does 0 == "a" return?']);

        ByteStreakServer::actingAs($admin)->tool(GetChallengeOptionsTool::class)
            ->assertOk()
            ->assertSee(['"slug":"sql"', '"value":"predict_output"', '"code_languages"']);

        ByteStreakServer::actingAs($admin)->tool(SearchChallengesTool::class, ['query' => '0 == "a"'])
            ->assertSee(['"count":1', 'Strict comparison in PHP']);

        ByteStreakServer::actingAs($admin)->tool(SearchChallengesTool::class, ['query' => 'kubernetes'])
            ->assertSee('"count":0');
    }

    public function test_the_http_endpoint_needs_an_admin_token(): void
    {
        $listTools = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => (object) []];

        $this->postJson('/mcp', $listTools)
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", error="invalid_token"');

        $devToken = User::factory()->create()->createToken('mcp')->plainTextToken;
        $adminToken = $this->admin()->createToken('mcp')->plainTextToken;

        $this->app['auth']->forgetGuards();
        $this->withToken($devToken)->postJson('/mcp', $listTools)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $tools = $this->withToken($adminToken)->postJson('/mcp', $listTools)->assertOk()->json('result.tools');

        $this->assertEqualsCanonicalizing(
            ['get-challenge-options', 'search-challenges', 'create-challenge'],
            array_column($tools, 'name'),
        );
    }
}
