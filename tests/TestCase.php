<?php

namespace Tests;

use App\Enums\ChallengeStatus;
use App\Models\Category;
use App\Models\Challenge;
use App\Services\DailyChallengeService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    /** A Wednesday, so "yesterday" and "tomorrow" are both weekdays. */
    protected const TODAY = '2026-09-30 10:00:00';

    /** Move the clock and drop the memoised "today's challenge". */
    protected function travelToDay(string $datetime): void
    {
        Carbon::setTestNow($datetime);
        app(DailyChallengeService::class)->forget();
    }

    /** A challenge with four options where the first one is correct. */
    protected function makeChallenge(array $attributes = []): Challenge
    {
        $category = Category::firstOrCreate(['slug' => 'php'], ['name' => 'PHP']);

        $challenge = Challenge::create([
            'category_id' => $category->id,
            'type' => 'multiple_choice',
            'difficulty' => 'medium',
            'title' => 'Sample challenge '.fake()->unique()->numberBetween(1, 99999),
            'question' => 'Pick the right one.',
            'explanation' => 'Because the first option is correct.',
            'xp' => 20,
            'status' => ChallengeStatus::Published,
            'publish_date' => today()->toDateString(),
            ...$attributes,
        ]);

        foreach (['Right', 'Wrong A', 'Wrong B', 'Wrong C'] as $index => $label) {
            $challenge->options()->create(['label' => $label, 'is_correct' => $index === 0, 'sort_order' => $index]);
        }

        return $challenge->load('options');
    }

    protected function rightOption(Challenge $challenge): int
    {
        return $challenge->options->firstWhere('is_correct', true)->id;
    }

    protected function wrongOption(Challenge $challenge): int
    {
        return $challenge->options->firstWhere('is_correct', false)->id;
    }
}
