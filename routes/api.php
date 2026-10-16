<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| JSON API  —  /api/v1/*, named api.*
|--------------------------------------------------------------------------
| Authenticated by Sanctum: send `Authorization: Bearer <token>` (from POST /auth/login),
| or use the browser session from /login on a SANCTUM_STATEFUL_DOMAINS origin
| (writes then need the X-XSRF-TOKEN header).
*/

Route::name('api.')->group(function () {
    Route::post('/auth/login', [Api\AuthController::class, 'login'])->middleware('throttle:20,1')->name('auth.login');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('/auth/logout', [Api\AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/logout-everywhere', [Api\AuthController::class, 'logoutEverywhere'])->name('auth.logout-everywhere');

        Route::get('/me', [Api\MeController::class, 'show'])->name('me');
        Route::patch('/me', [Api\MeController::class, 'update'])->name('me.update');
        Route::put('/me/password', [Api\MeController::class, 'password'])->name('me.password');
        Route::get('/me/stats', [Api\MeController::class, 'stats'])->name('me.stats');

        Route::get('/today', [Api\ChallengeController::class, 'today'])->name('today');
        Route::get('/challenges', [Api\ChallengeController::class, 'index'])->name('challenges.index');
        Route::get('/challenges/{challenge}', [Api\ChallengeController::class, 'show'])->name('challenges.show');
        Route::post('/challenges/{challenge}/attempt', [Api\ChallengeController::class, 'attempt'])->name('challenges.attempt');

        Route::get('/categories', Api\CategoryController::class)->name('categories');
        Route::get('/leaderboard', Api\LeaderboardController::class)->name('leaderboard');
        Route::get('/badges', Api\BadgeController::class)->name('badges');

        Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/challenges/options', [Api\Admin\ChallengeController::class, 'options'])->name('challenges.options');
            Route::apiResource('challenges', Api\Admin\ChallengeController::class);
            Route::post('/challenges/{challenge}/publish', [Api\Admin\ChallengeController::class, 'publish'])->name('challenges.publish');
            Route::post('/challenges/{challenge}/queue', [Api\Admin\ChallengeController::class, 'queue'])->name('challenges.queue');
            Route::post('/challenges/{challenge}/archive', [Api\Admin\ChallengeController::class, 'archive'])->name('challenges.archive');

            Route::apiResource('categories', Api\Admin\CategoryController::class)->except('show');
            Route::apiResource('badges', Api\Admin\BadgeController::class)->except('show');
            Route::apiResource('users', Api\Admin\UserController::class)->only(['index', 'update']);
        });
    });
});
