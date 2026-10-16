<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ChallengeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChallengeRequest;
use App\Models\Challenge;
use App\Services\ChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ChallengeController extends Controller
{
    public function index(Request $request, ChallengeService $challenges): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ChallengeStatus::class)],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:160'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($challenges->adminIndex(
            ChallengeStatus::tryFrom($filters['status'] ?? ''),
            $filters['category'] ?? null,
            trim($filters['search'] ?? ''),
            (int) ($filters['per_page'] ?? 15),
        ));
    }

    /** What a new challenge may contain: category ids, types, difficulties (with default XP), languages, taken dates. */
    public function options(ChallengeService $challenges): JsonResponse
    {
        $options = $challenges->formOptions();

        return response()->json(['data' => [
            'categories' => $options['categories'],
            'types' => $options['types'],
            'difficulties' => $options['difficulties'],
            'statuses' => array_column(ChallengeStatus::cases(), 'value'),
            'code_languages' => $options['languages'],
            'taken_dates' => $options['takenDates'],
            'today' => $options['today'],
            'options' => ['min' => 2, 'max' => 6],
        ]]);
    }

    public function store(ChallengeRequest $request, ChallengeService $challenges): JsonResponse
    {
        $challenge = $challenges->save(new Challenge(['created_by' => $request->user()->id]), $request->validated());

        return response()->json(['data' => $this->detail($challenge, $challenges)], 201);
    }

    /** Answer, stats and recent attempts, plus `form`: the fields in the shape PUT expects. */
    public function show(Challenge $challenge, ChallengeService $challenges): JsonResponse
    {
        return response()->json(['data' => $this->detail($challenge, $challenges)]);
    }

    /** A full replace: send every field, and keep each option's `id` so existing answers stay linked. */
    public function update(ChallengeRequest $request, Challenge $challenge, ChallengeService $challenges): JsonResponse
    {
        $challenges->save($challenge, $request->validated());

        return response()->json(['data' => $this->detail($challenge, $challenges)]);
    }

    public function destroy(Challenge $challenge): Response
    {
        abort_if($challenge->attempts()->exists(), 409, 'People have already answered this one — archive it instead of deleting.');

        $challenge->delete();

        return response()->noContent();
    }

    public function publish(Challenge $challenge, ChallengeService $challenges): JsonResponse
    {
        $challenges->publishNow($challenge);

        return $this->stateResponse($challenge, $challenge->isDailyOn(today())
            ? 'Published as today’s challenge.'
            : 'Published. It is live in the archive.');
    }

    public function queue(Challenge $challenge, ChallengeService $challenges): JsonResponse
    {
        abort_if($challenge->attempts()->exists(), 409, 'This challenge has answers already, so it cannot go back in the queue.');

        $challenges->queue($challenge);

        return $this->stateResponse($challenge, 'Added to the queue.');
    }

    public function archive(Challenge $challenge, ChallengeService $challenges): JsonResponse
    {
        $challenges->archive($challenge);

        return $this->stateResponse($challenge, 'Challenge archived. It is hidden from developers.');
    }

    /** @return array<string, mixed> */
    private function detail(Challenge $challenge, ChallengeService $challenges): array
    {
        return [...$challenges->adminDetail($challenge), 'form' => $challenge->toForm()];
    }

    private function stateResponse(Challenge $challenge, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => [
                'id' => $challenge->id,
                'status' => $challenge->status->value,
                'publish_date' => $challenge->publish_date?->toDateString(),
                'is_today' => $challenge->isDailyOn(today()),
            ],
        ]);
    }
}
