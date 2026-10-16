<?php

namespace App\Mcp\Tools;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeType;
use App\Enums\Difficulty;
use App\Http\Requests\ChallengeRequest;
use App\Models\Challenge;
use App\Services\ChallengeService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('create-challenge')]
#[Description('Add a new question to ByteStreak. It is validated with the same rules as the admin form. Defaults to a draft that an admin reviews and publishes on the site.')]
class CreateChallengeTool extends Tool
{
    public function handle(Request $request, ChallengeService $challenges): Response|ResponseFactory
    {
        $user = $request->user();

        if (! $user?->is_admin) {
            return Response::error('Only ByteStreak admins can add questions.');
        }

        $difficulty = Difficulty::tryFrom((string) $request->get('difficulty'));

        // Same rules as the admin form; a ValidationException becomes an error result listing every problem.
        $data = ChallengeRequest::validateFor($user, [
            'category_id' => $request->get('category_id'),
            'type' => $request->get('type'),
            'difficulty' => $request->get('difficulty'),
            'title' => $request->get('title'),
            'question' => $request->get('question'),
            'code_snippet' => $request->get('code_snippet'),
            'code_language' => $request->get('code_language'),
            'explanation' => $request->get('explanation'),
            'xp' => $request->get('xp') ?? $difficulty?->defaultXp(),
            'status' => $request->get('status') ?? ChallengeStatus::Draft->value,
            'publish_date' => $request->get('publish_date'),
            'options' => array_map(fn ($label) => ['label' => $label], (array) $request->get('options', [])),
            'correct_index' => $request->get('correct_option'),
        ]);

        $challenge = $challenges->save(new Challenge(['created_by' => $user->id]), $data);
        $detail = $challenges->adminDetail($challenge)['challenge'];

        return Response::structured([
            'message' => "Created “{$challenge->title}” as {$challenge->status->value}.",
            'id' => $challenge->id,
            'status' => $challenge->status->value,
            'publish_date' => $challenge->publish_date?->toDateString(),
            'admin_url' => route('admin.challenges.show', $challenge),
            'edit_url' => route('admin.challenges.edit', $challenge),
            'challenge' => [
                'title' => $detail['title'],
                'category' => $detail['category']['name'],
                'type' => $detail['type']['value'],
                'difficulty' => $detail['difficulty'],
                'xp' => $detail['xp'],
                'options' => $detail['options']->map(fn (array $o) => ['label' => $o['label'], 'is_correct' => $o['is_correct']])->all(),
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category_id' => $schema->integer()->description('Category id from get-challenge-options.')->required(),
            'type' => $schema->string()->enum(array_column(ChallengeType::cases(), 'value'))->required(),
            'difficulty' => $schema->string()->enum(array_column(Difficulty::cases(), 'value'))->required(),
            'title' => $schema->string()->max(160)->description('Short headline shown on cards, e.g. "COUNT(*) vs COUNT(column)".')->required(),
            'question' => $schema->string()->max(5000)->description('The question itself, without code.')->required(),
            'code_snippet' => $schema->string()->max(10000)->description('Optional code shown under the question.'),
            'code_language' => $schema->string()->enum(Challenge::CODE_LANGUAGES)->description('Language of code_snippet, for syntax highlighting.'),
            'options' => $schema->array()->items($schema->string()->max(1000))->min(2)->max(6)
                ->description('The answer options, in display order.')->required(),
            'correct_option' => $schema->integer()->min(0)->max(5)->description('0-based index of the correct entry in options.')->required(),
            'explanation' => $schema->string()->max(5000)->description('Shown after answering: why the correct option is correct.')->required(),
            'xp' => $schema->integer()->min(1)->max(200)->description('XP for a correct answer. Defaults to the difficulty\'s XP.'),
            'status' => $schema->string()->enum(array_column(ChallengeStatus::cases(), 'value'))
                ->description('Default "draft". "scheduled" without publish_date joins the queue.'),
            'publish_date' => $schema->string()->format('date')
                ->description('YYYY-MM-DD. Only with status "scheduled" (today or later) or "published" (today or earlier). Must be free.'),
        ];
    }
}
