<?php

namespace App\Services;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeType;
use App\Enums\Difficulty;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\ChallengeAttempt;
use App\Models\ChallengeOption;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The admin side of challenges, shared by the admin pages and the admin API.
 */
class ChallengeService
{
    public function __construct(private DailyChallengeService $daily) {}

    /** The admin list: every challenge, dated ones newest first, then the undated ones. */
    public function adminIndex(?ChallengeStatus $status = null, mixed $categoryId = null, string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        return Challenge::query()
            ->with('category')
            ->withCount(['attempts', 'attempts as correct_count' => fn ($q) => $q->where('is_correct', true)])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($categoryId, fn ($q, $id) => $q->where('category_id', $id))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByRaw('publish_date IS NULL')
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Challenge $challenge) => $challenge->toAdminRow());
    }

    /**
     * Save a challenge and its options. Options are edited in place so existing attempts keep
     * pointing at the right one, and an option somebody picked cannot be removed.
     *
     * @param  array<string, mixed>  $data  validated ChallengeRequest data
     */
    public function save(Challenge $challenge, array $data): Challenge
    {
        return DB::transaction(function () use ($challenge, $data) {
            $challenge->fill(Arr::except($data, ['options', 'correct_index']))->save();

            $kept = [];
            foreach (array_values($data['options']) as $index => $option) {
                $model = isset($option['id']) ? $challenge->options()->whereKey($option['id'])->first() : null;
                $model ??= $challenge->options()->make();
                $model->fill([
                    'label' => $option['label'],
                    'is_correct' => $index === (int) $data['correct_index'],
                    'sort_order' => $index,
                ])->save();
                $kept[] = $model->id;
            }

            $removed = $challenge->options()->whereNotIn('id', $kept)->pluck('id');
            if ($removed->isNotEmpty() && ChallengeAttempt::whereIn('challenge_option_id', $removed)->exists()) {
                throw ValidationException::withMessages([
                    'options' => 'An option someone already picked cannot be removed.',
                ]);
            }
            ChallengeOption::whereIn('id', $removed)->delete();

            return $challenge;
        });
    }

    /** Go live now: as today's challenge if the day is still free, otherwise as a bonus in the archive. */
    public function publishNow(Challenge $challenge): Challenge
    {
        $todayTaken = Challenge::whereDate('publish_date', today())->whereKeyNot($challenge->id)->exists();
        $keepsDate = $challenge->publish_date?->lte(today());

        $challenge->update([
            'status' => ChallengeStatus::Published,
            'publish_date' => $keepsDate ? $challenge->publish_date : ($todayTaken ? null : today()->toDateString()),
        ]);
        $this->daily->forget();

        return $challenge;
    }

    /** Send to the back of the queue: it publishes on the next day with nothing scheduled. */
    public function queue(Challenge $challenge): Challenge
    {
        $challenge->update(['status' => ChallengeStatus::Scheduled, 'publish_date' => null]);

        return $challenge;
    }

    public function archive(Challenge $challenge): Challenge
    {
        $challenge->update(['status' => ChallengeStatus::Archived]);
        $this->daily->forget();

        return $challenge;
    }

    /**
     * The valid choices for a challenge's fields, and the dates other challenges already own.
     *
     * @return array<string, mixed>
     */
    public function formOptions(?Challenge $editing = null): array
    {
        return [
            'categories' => Category::ordered()->get()->map->toChip(),
            'types' => ChallengeType::options(),
            'difficulties' => Difficulty::options(),
            'languages' => Challenge::CODE_LANGUAGES,
            'takenDates' => Challenge::whereNotNull('publish_date')
                ->when($editing, fn ($q) => $q->whereKeyNot($editing->id))
                ->whereDate('publish_date', '>=', today())
                ->orderBy('publish_date')
                ->pluck('publish_date')
                ->map->toDateString(),
            'today' => today()->toDateString(),
        ];
    }

    /**
     * Everything an admin sees about one challenge, answer and team split included.
     *
     * @return array{challenge: array<string, mixed>, stats: array<string, mixed>, attempts: mixed}
     */
    public function adminDetail(Challenge $challenge): array
    {
        $challenge->load(['category', 'options']);

        $picks = $challenge->attempts()->selectRaw('challenge_option_id, COUNT(*) as picks')
            ->groupBy('challenge_option_id')->pluck('picks', 'challenge_option_id');
        $total = (int) $picks->sum();
        $correct = $challenge->attempts()->where('is_correct', true)->count();

        return [
            'challenge' => [
                ...$challenge->toCard(),
                'status' => $challenge->status->value,
                'question' => $challenge->question,
                'code_snippet' => $challenge->code_snippet,
                'code_language' => $challenge->code_language,
                'explanation' => $challenge->explanation,
                'options' => $challenge->options->map(fn (ChallengeOption $option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'is_correct' => $option->is_correct,
                    'picks' => (int) ($picks[$option->id] ?? 0),
                    'percent' => $total > 0 ? (int) round(($picks[$option->id] ?? 0) / $total * 100) : 0,
                ]),
            ],
            'stats' => [
                'attempts' => $total,
                'correct' => $correct,
                'accuracy' => $total > 0 ? (int) round($correct / $total * 100) : null,
                'daily_attempts' => $challenge->attempts()->where('is_daily', true)->count(),
                'team_size' => User::count(),
            ],
            'attempts' => $challenge->attempts()->with(['user', 'option'])->latest('submitted_at')->limit(50)->get()
                ->map(fn (ChallengeAttempt $attempt) => [
                    'id' => $attempt->id,
                    'user' => $attempt->user->toIdentity(),
                    'option' => $attempt->option?->label,
                    'is_correct' => $attempt->is_correct,
                    'is_daily' => $attempt->is_daily,
                    'submitted_at' => $attempt->submitted_at->toIso8601String(),
                ]),
        ];
    }
}
