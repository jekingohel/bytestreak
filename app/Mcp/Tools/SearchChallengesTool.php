<?php

namespace App\Mcp\Tools;

use App\Enums\ChallengeStatus;
use App\Models\Challenge;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('search-challenges')]
#[Description('Find existing questions by keyword (matched against title and question text), in any status. Use it to avoid adding a duplicate.')]
#[IsReadOnly]
class SearchChallengesTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        if (! $request->user()?->is_admin) {
            return Response::error('Only ByteStreak admins can use this tool.');
        }

        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:100'],
            'status' => ['nullable', Rule::enum(ChallengeStatus::class)],
            'category_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $matches = Challenge::query()
            ->with('category')
            ->where(fn ($q) => $q->where('title', 'like', "%{$data['query']}%")->orWhere('question', 'like', "%{$data['query']}%"))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->latest('id')
            ->limit($data['limit'] ?? 10)
            ->get()
            ->map(fn (Challenge $challenge) => [
                ...$challenge->toCard(),
                'status' => $challenge->status->value,
                'question' => $challenge->question,
            ]);

        return Response::structured(['count' => $matches->count(), 'challenges' => $matches->all()]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Keywords to look for, e.g. "COUNT NULL".')->required(),
            'status' => $schema->string()->enum(array_column(ChallengeStatus::cases(), 'value'))->description('Only this status.'),
            'category_id' => $schema->integer()->description('Only this category (ids from get-challenge-options).'),
            'limit' => $schema->integer()->min(1)->max(50)->description('Maximum results, default 10.'),
        ];
    }
}
