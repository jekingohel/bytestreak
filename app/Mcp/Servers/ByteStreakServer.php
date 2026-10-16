<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateChallengeTool;
use App\Mcp\Tools\GetChallengeOptionsTool;
use App\Mcp\Tools\SearchChallengesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Lets an admin's AI assistant add questions to ByteStreak. Served at POST /mcp (routes/ai.php)
 * behind a Sanctum token created on the admin's profile page.
 */
#[Name('ByteStreak')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
    ByteStreak is an internal daily developer quiz: one multiple-choice question per working day.
    These tools add questions to its question bank on behalf of the signed-in admin.

    How to add a question:
    1. Call `get-challenge-options` for the valid category ids, types, difficulties (with default XP)
       and code languages, and the dates that already have a question.
    2. Call `search-challenges` with a few keywords to make sure the question does not exist yet.
    3. Call `create-challenge`.

    Writing good questions:
    - Exactly one option is correct; give 2–6 options. Wrong options should be plausible.
    - Keep code out of `question`: put it in `code_snippet` and set `code_language`.
    - The question and options must not give the answer away; `explanation` is shown only after answering,
      so say why the right answer is right (and why a tempting wrong one is wrong) in 1–3 sentences.
    - `true_false` questions have exactly two options: "True" and "False".
    - Create as `draft` unless the admin asks otherwise. `scheduled` without a date joins the queue
      (published on the next free weekday); `scheduled` with a date goes live that day.
    MARKDOWN)]
class ByteStreakServer extends Server
{
    protected array $tools = [
        GetChallengeOptionsTool::class,
        SearchChallengesTool::class,
        CreateChallengeTool::class,
    ];
}
