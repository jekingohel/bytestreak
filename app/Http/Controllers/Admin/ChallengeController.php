<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChallengeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChallengeRequest;
use App\Models\Category;
use App\Models\Challenge;
use App\Services\ChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChallengeController extends Controller
{
    public function index(Request $request, ChallengeService $challenges): Response
    {
        $status = ChallengeStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('search'));

        $list = $challenges->adminIndex($status, $request->query('category'), $search);

        return Inertia::render('admin/challenges/Index', [
            'challenges' => $list,
            'filters' => [
                'status' => $status?->value,
                'category' => $request->query('category'),
                'search' => $search,
            ],
            'categories' => Category::ordered()->get()->map->toChip(),
            'counts' => Challenge::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create(ChallengeService $challenges): Response
    {
        return Inertia::render('admin/challenges/Form', [
            'challenge' => null,
            ...$challenges->formOptions(),
        ]);
    }

    public function store(ChallengeRequest $request, ChallengeService $challenges): RedirectResponse
    {
        $challenge = $challenges->save(new Challenge(['created_by' => $request->user()->id]), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "“{$challenge->title}” created."]);

        return redirect()->route('admin.challenges.index');
    }

    public function show(Challenge $challenge, ChallengeService $challenges): Response
    {
        return Inertia::render('admin/challenges/Show', $challenges->adminDetail($challenge));
    }

    public function edit(Challenge $challenge, ChallengeService $challenges): Response
    {
        return Inertia::render('admin/challenges/Form', [
            'challenge' => $challenge->toForm(),
            ...$challenges->formOptions($challenge),
        ]);
    }

    public function update(ChallengeRequest $request, Challenge $challenge, ChallengeService $challenges): RedirectResponse
    {
        $challenges->save($challenge, $request->validated());

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
    public function publish(Challenge $challenge, ChallengeService $challenges): RedirectResponse
    {
        $challenges->publishNow($challenge);

        Inertia::flash('toast', ['type' => 'success', 'message' => $challenge->isDailyOn(today())
            ? 'Published as today’s challenge.'
            : 'Published. It is live in the archive.']);

        return back();
    }

    /** Send to the back of the queue: it publishes on the next day with nothing scheduled. */
    public function queue(Challenge $challenge, ChallengeService $challenges): RedirectResponse
    {
        if ($challenge->attempts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'This challenge has answers already, so it cannot go back in the queue.']);

            return back();
        }

        $challenges->queue($challenge);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Added to the queue.']);

        return back();
    }

    public function archive(Challenge $challenge, ChallengeService $challenges): RedirectResponse
    {
        $challenges->archive($challenge);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Challenge archived. It is hidden from developers.']);

        return back();
    }
}
