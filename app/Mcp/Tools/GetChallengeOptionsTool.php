<?php

namespace App\Mcp\Tools;

use App\Enums\ChallengeStatus;
use App\Services\ChallengeService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get-challenge-options')]
#[Description('Valid values for a new question: category ids, question types, difficulties with their default XP, statuses, code languages, and the dates that already have a question. Call this before create-challenge.')]
#[IsReadOnly]
class GetChallengeOptionsTool extends Tool
{
    public function handle(Request $request, ChallengeService $challenges): Response|ResponseFactory
    {
        if (! $request->user()?->is_admin) {
            return Response::error('Only ByteStreak admins can use this tool.');
        }

        $options = $challenges->formOptions();

        return Response::structured([
            'categories' => $options['categories']->all(),
            'types' => $options['types'],
            'difficulties' => $options['difficulties'],
            'statuses' => array_column(ChallengeStatus::cases(), 'value'),
            'code_languages' => $options['languages'],
            'taken_dates' => $options['takenDates']->all(),
            'today' => $options['today'],
            'options_per_question' => ['min' => 2, 'max' => 6],
        ]);
    }
}
