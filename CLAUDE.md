# ByteStreak

Internal daily developer challenge: one question per working day, XP, streaks, badges, weekly leaderboard.
Laravel 13 · Vue 3 + Inertia 3 · Tailwind 4 · MySQL. See `README.md` for setup and the full game rules.

## Commands

```bash
php artisan serve                       # http://localhost:8000
npm run dev                             # Vite with HMR (or `npm run build`)
php artisan test                        # PHPUnit on in-memory SQLite
vendor/bin/pint                         # format PHP
php artisan migrate:fresh --seed        # reset: categories, badges, question bank, admin
php artisan db:seed --class=DemoSeeder  # optional demo team with history
php artisan challenges:publish-daily    # what the scheduler runs at 00:01
```

## Rules that are easy to break

- **Never send the answer early.** `ChallengeViewService::detail` (used by the page and the API) only adds
  `is_correct`, `percent` and `explanation` once the user has an attempt. `Challenge::toCard()` must stay free of answer data.
- **All scoring goes through `AttemptService::submit()`**: one transaction that writes the attempt,
  the `xp_ledger` rows, the streak and any badges. `users.xp`, `current_streak`, `longest_streak` and
  `streak_freezes` are caches of that; never update them anywhere else.
- **Game numbers live in `config/bytestreak.php`**, not in code.
- **Streaks count published daily challenges, not calendar days.** A day with no challenge never
  breaks a streak (`StreakService::missedSince`).
- **`publish_date` is a DATE column.** Filter it with `whereDate()`, never `where()`/`whereBetween()`
  with strings: tests run on SQLite, where a date cast is stored as `Y-m-d 00:00:00`.
- **Challenge options are edited in place** (`ChallengeService::save`) so existing
  attempts keep pointing at the right option. An option somebody picked cannot be removed.
- Tests move the clock with `$this->travelToDay()` (in `tests/TestCase.php`), which also clears the
  memoised "today's challenge".

## JSON API

- `routes/api.php`, served at `/api/v1/*` with route names `api.*`, guarded by `auth:sanctum`. Clients either send
  `Authorization: Bearer <token>` (from `POST /api/v1/auth/login`, or created by an admin on the profile page; each token carries its own
  `expires_at`, so `sanctum.expiration` stays null) or, from a
  `SANCTUM_STATEFUL_DOMAINS` origin, use the page session plus the X-XSRF-TOKEN header (`statefulApi()` in
  `bootstrap/app.php`).
- API and page controllers share their logic: `ChallengeViewService` and `ProgressService` (player), and
  `ChallengeService`, the form requests and the models' `toAdminRow()` (admin). Change it there, not in a controller.
- Conflicts (deleting something in use, re-queuing an answered challenge) return 409. Validation errors return 422.

## MCP server

- `laravel/mcp`: `POST /mcp` (`routes/ai.php`) serves `App\Mcp\Servers\ByteStreakServer`, behind `auth:sanctum` + `admin`.
  Admins create the token on their profile page.
- Tools live in `app/Mcp/Tools`. `create-challenge` validates through `ChallengeRequest::validateFor()` and saves
  through `ChallengeService::save()`. Keep it that way, so the admin form, the API and MCP share one set of rules.
- Test tools with `ByteStreakServer::actingAs($user)->tool(SomeTool::class, [...])` (see `tests/Feature/McpServerTest.php`).

## Front end

- Pages: `resources/js/pages/**` (Inertia component names such as `challenges/Show`).
  Layout is chosen in `resources/js/app.js`: `AppLayout` for signed-in pages, `GuestLayout` for `auth/*`.
- Use the design tokens from `resources/css/app.css` (`bg-surface`, `text-soft`, `text-flame`,
  `text-mint`, `text-rose`, `text-gold` …) and the component classes (`card`, `btn btn-primary`,
  `input`, `label`, `pill`, `eyebrow`, `num`). Both themes are driven by the same variables, so avoid
  hard-coded colours.
- One-time messages use Inertia flash: `Inertia::flash('toast', ['type' => 'success', 'message' => …])`
  is rendered by `components/Toasts.vue`; `Inertia::flash('result', …)` drives the celebration on the
  challenge page.
- Icons come from `@lucide/vue`. Emoji are content (categories, badges), not UI chrome.
- Code is shown with ligatures off on purpose: a question about `==` vs `===` must show those characters.
