<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\ChallengeOption;
use App\Services\AttemptService;
use App\Services\ChallengeViewService;
use App\Services\DailyChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChallengeController extends Controller
{
    /** Today's challenge, or `data: null` on a rest day. */
    public function today(Request $request, DailyChallengeService $daily, ChallengeViewService $view): JsonResponse
    {
        $challenge = $daily->today();

        return response()->json([
            'data' => $challenge ? $view->detail($challenge, $request->user()) : null,
            'next_challenge_at' => now()->addDay()->startOfDay()->toIso8601String(),
        ]);
    }

    /** The archive: every live challenge, filterable by the user's result and category. */
    public function index(Request $request, ChallengeViewService $view): JsonResponse
    {
        $filters = $request->validate([
            'state' => ['nullable', Rule::in(ChallengeViewService::ARCHIVE_STATES)],
            'category' => ['nullable', 'string', 'exists:categories,slug'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $category = isset($filters['category']) ? Category::where('slug', $filters['category'])->first() : null;

        return response()->json(
            $view->archive($request->user(), $filters['state'] ?? 'all', $category, (int) ($filters['per_page'] ?? 12)),
        );
    }

    public function show(Request $request, Challenge $challenge, ChallengeViewService $view): JsonResponse
    {
        $user = $request->user();

        // Admins may preview anything; everyone else only sees what has gone live.
        abort_unless($challenge->isVisible() || $user->is_admin, 404);

        return response()->json(['data' => [
            ...$view->detail($challenge, $user),
            'next_up' => $view->nextUp($user, $challenge),
        ]]);
    }

    /** Answer once. Returns the XP breakdown, streak change, badges and level-up, plus the revealed challenge. */
    public function attempt(Request $request, Challenge $challenge, AttemptService $attempts, ChallengeViewService $view): JsonResponse
    {
        abort_unless($challenge->isVisible(), 404);

        $data = $request->validate([
            'option_id' => ['required', 'integer', Rule::exists('challenge_options', 'id')->where('challenge_id', $challenge->id)],
        ]);

        $result = $attempts->submit($request->user(), $challenge, ChallengeOption::findOrFail($data['option_id']));

        return response()->json(['data' => [
            'result' => $result,
            ...$view->detail($challenge, $request->user()),
        ]], 201);
    }
}
