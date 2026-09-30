<?php

namespace App\Providers;

use App\Services\DailyChallengeService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One instance per request so "today's challenge" is resolved once and shared.
        $this->app->scoped(DailyChallengeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
