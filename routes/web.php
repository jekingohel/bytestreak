<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    // One-click sign-in for local development only (BYTESTREAK_DEV_LOGIN=false turns it off).
    if (LoginController::devLoginEnabled()) {
        Route::post('/dev-login/{user}', [LoginController::class, 'devLogin'])->name('dev-login');
    }
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/today', [ChallengeController::class, 'today'])->name('today');
    Route::get('/challenges/{challenge}', [ChallengeController::class, 'show'])->name('challenges.show');
    Route::post('/challenges/{challenge}/attempt', [ChallengeController::class, 'attempt'])->name('challenges.attempt');

    Route::get('/archive', ArchiveController::class)->name('archive');
    Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard');
    Route::get('/badges', BadgeController::class)->name('badges');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::resource('challenges', Admin\ChallengeController::class);
        Route::post('/challenges/{challenge}/publish', [Admin\ChallengeController::class, 'publish'])->name('challenges.publish');
        Route::post('/challenges/{challenge}/queue', [Admin\ChallengeController::class, 'queue'])->name('challenges.queue');
        Route::post('/challenges/{challenge}/archive', [Admin\ChallengeController::class, 'archive'])->name('challenges.archive');

        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('badges', Admin\BadgeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('users', Admin\UserController::class)->only(['index', 'update']);
    });
});
