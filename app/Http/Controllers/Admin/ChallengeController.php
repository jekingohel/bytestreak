<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChallengeStatus;
use App\Enums\ChallengeType;
use App\Enums\Difficulty;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChallengeRequest;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\ChallengeAttempt;
use App\Models\ChallengeOption;
use App\Models\User;
use App\Services\DailyChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeController extends Controller
{
    public function index(Request $request): Response
    {
        $status = ChallengeStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('search'));

        $challenges = Challenge::query()
            ->with('category')
            ->withCount(['attempts', 'attempts as correct_count' => fn ($q) => $q->where('is_correct', true)])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->query('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByRaw('publish_date IS NULL')
            ->orderByDesc('publish_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Challenge $challenge) => [
                ...$challenge->toCard(),
                'status' => $challenge->status->value,
                'is_queued' => $challenge->status === ChallengeStatus::Scheduled && $challenge->publish_date === null,
                'attempts' => $challenge->attempts_count,
                'accuracy' => $challenge->attempts_count > 0
                    ? (int) round($challenge->correct_count / $challenge->attempts_count * 100)
                    : null,
            ]);

        return Inertia::render('admin/challenges/Index', [
            'challenges' => $challenges,
            'filters' => [
                'status' => $status?->value,
                'category' => $request->query('category'),
                'search' => $search,
            ],
            'categories' => Category::ordered()->get()->map->toChip(),
            'counts' => Challenge::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/challenges/Form', [
            'challenge' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(ChallengeRequest $request): RedirectResponse
    {
        $challenge = $this->persist(new Challenge(['created_by' => $request->user()->id]), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "“{$challenge->title}” created."]);

        return redirect()->route('admin.challenges.index');
    }

    public function show(Challenge $challenge): Response
    {
        $challenge->load(['category', 'options']);

        $picks = $challenge->attempts()->selectRaw('challenge_option_id, COUNT(*) as picks')
            ->groupBy('challenge_option_id')->pluck('picks', 'challenge_option_id');
        $total = (int) $picks->sum();
        $correct = $challenge->attempts()->where('is_correct', true)->count();

        return Inertia::render('admin/challenges/Show', [
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
        ]);
    }

    public function edit(Challenge $challenge): Response
    {
        $challenge->load('options');

        return Inertia::render('admin/challenges/Form', [
            'challenge' => [
                'id' => $challenge->id,
                'category_id' => $challenge->category_id,
                'type' => $challenge->type->value,
                'difficulty' => $challenge->difficulty->value,
                'title' => $challenge->title,
                'question' => $challenge->question,
                'code_snippet' => $challenge->code_snippet,
                'code_language' => $challenge->code_language,
                'explanation' => $challenge->explanation,
                'xp' => $challenge->xp,
                'status' => $challenge->status->value,
                'publish_date' => $challenge->publish_date?->toDateString(),
                'options' => $challenge->options->map(fn (ChallengeOption $o) => ['id' => $o->id, 'label' => $o->label]),
                'correct_index' => max(0, (int) $challenge->options->search(fn (ChallengeOption $o) => $o->is_correct)),
                'attempts' => $challenge->attempts()->count(),
            ],
            ...$this->formOptions($challenge),
        ]);
    }

    public function update(ChallengeRequest $request, Challenge $challenge): RedirectResponse
    {
        $this->persist($challenge, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Challenge saved.']);

        return redirect()->route('admin.challenges.index');
    }

    public function destroy(Challenge $challenge): RedirectResponse
    {
        if ($challenge->attempts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'People have already answered this one — archive it instead of deleting.']);

            return back();
        }

        $challenge->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Challenge deleted.']);

        return redirect()->route('admin.challenges.index');
    }

    /** Go live now: as today's challenge if the day is still free, otherwise as a bonus in the archive. */
    public function publish(Challenge $challenge, DailyChallengeService $daily): RedirectResponse
    {
        $todayTaken = Challenge::whereDate('publish_date', today())->whereKeyNot($challenge->id)->exists();
        $keepsDate = $challenge->publish_date?->lte(today());

        $challenge->update([
            'status' => ChallengeStatus::Published,
            'publish_date' => $keepsDate ? $challenge->publish_date : ($todayTaken ? null : today()->toDateString()),
        ]);
        $daily->forget();

        Inertia::flash('toast', ['type' => 'success', 'message' => $challenge->isDailyOn(today())
            ? 'Published as today’s challenge.'
            : 'Published. It is live in the archive.']);

        return back();
    }

    /** Send to the back of the queue: it publishes on the next day with nothing scheduled. */
    public function queue(Challenge $challenge): RedirectResponse
    {
        if ($challenge->attempts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This challenge has answers already, so it cannot go back in the queue.']);

            return back();
        }

        $challenge->update(['status' => ChallengeStatus::Scheduled, 'publish_date' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Added to the queue.']);

        return back();
    }

    public function archive(Challenge $challenge, DailyChallengeService $daily): RedirectResponse
    {
        $challenge->update(['status' => ChallengeStatus::Archived]);
        $daily->forget();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Challenge archived. It is hidden from developers.']);

        return back();
    }

    private function persist(Challenge $challenge, array $data): Challenge
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

    /** @return array<string, mixed> */
    private function formOptions(?Challenge $editing = null): array
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
}
