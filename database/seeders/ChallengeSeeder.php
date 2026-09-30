<?php

namespace Database\Seeders;

use App\Enums\ChallengeStatus;
use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Loads the starter question bank and lays it out relative to the day it is run:
 * three weeks of past dailies, today's challenge, a few scheduled days, a queue and two drafts.
 */
class ChallengeSeeder extends Seeder
{
    private const PAST_DAYS = 15;

    private const SCHEDULED_DAYS = 3;

    private const DRAFTS = 2;

    /** Weekday themes (1 = Monday). Weeks alternate between the two rows. */
    private const THEMES = [
        [1 => 'laravel', 2 => 'php', 3 => 'mysql', 4 => 'git', 5 => 'debugging'],
        [1 => 'javascript', 2 => 'vue', 3 => 'linux', 4 => 'html-css', 5 => 'general'],
    ];

    public function run(): void
    {
        if (Challenge::exists()) {
            $this->command?->warn('Challenges already exist — skipping the starter question bank.');

            return;
        }

        $categories = Category::pluck('id', 'slug');

        /** @var Collection<string, Collection<int, array>> $pools */
        $pools = collect(require __DIR__.'/data/challenges.php')->groupBy('category');

        foreach ($this->calendar() as [$date, $status]) {
            $theme = self::THEMES[$date->isoWeek() % 2][$date->dayOfWeekIso] ?? null;
            $row = $this->take($pools, $theme);

            if ($row) {
                $this->create($row, $categories, $status, $date);
            }
        }

        // Everything left: a queue the scheduler draws from, plus a couple of drafts.
        $rest = collect();
        while ($pools->flatten(1)->isNotEmpty()) {
            foreach ($pools->keys() as $slug) {
                if ($row = $this->take($pools, $slug, fallback: false)) {
                    $rest->push($row);
                }
            }
        }

        $drafts = $rest->splice(max(0, $rest->count() - self::DRAFTS));
        $rest->each(fn (array $row) => $this->create($row, $categories, ChallengeStatus::Scheduled, null));
        $drafts->each(fn (array $row) => $this->create($row, $categories, ChallengeStatus::Draft, null));
    }

    /**
     * Past weekdays (oldest first), today if it is a weekday, then the next weekdays.
     *
     * @return array<int, array{0: Carbon, 1: ChallengeStatus}>
     */
    private function calendar(): array
    {
        $days = [];

        for ($date = today()->subDay(); count($days) < self::PAST_DAYS; $date = $date->copy()->subDay()) {
            if ($date->isWeekday()) {
                array_unshift($days, [$date, ChallengeStatus::Published]);
            }
        }

        if (today()->isWeekday()) {
            $days[] = [today(), ChallengeStatus::Published];
        }

        for ($date = today()->addDay(), $added = 0; $added < self::SCHEDULED_DAYS; $date = $date->copy()->addDay()) {
            if ($date->isWeekday()) {
                $days[] = [$date, ChallengeStatus::Scheduled];
                $added++;
            }
        }

        return $days;
    }

    /** Pull the next question from a category pool, falling back to the fullest pool. */
    private function take(Collection $pools, ?string $slug, bool $fallback = true): ?array
    {
        if (! $slug || ! $pools->has($slug) || $pools[$slug]->isEmpty()) {
            if (! $fallback) {
                return null;
            }
            $slug = $pools->sortByDesc(fn (Collection $pool) => $pool->count())->keys()->first();
        }

        return $slug ? $pools[$slug]->shift() : null;
    }

    private function create(array $row, Collection $categories, ChallengeStatus $status, ?Carbon $date): void
    {
        $difficulty = Difficulty::from($row['difficulty']);

        $challenge = Challenge::create([
            'category_id' => $categories[$row['category']],
            'type' => $row['type'],
            'difficulty' => $difficulty,
            'title' => $row['title'],
            'question' => $row['question'],
            'code_snippet' => $row['code'] ?? null,
            'code_language' => $row['language'] ?? null,
            'explanation' => $row['explanation'],
            'xp' => $difficulty->defaultXp(),
            'status' => $status,
            'publish_date' => $date?->toDateString(),
        ]);

        foreach ($row['options'] as $index => $label) {
            $challenge->options()->create([
                'label' => $label,
                'is_correct' => $index === $row['answer'],
                'sort_order' => $index,
            ]);
        }
    }
}
