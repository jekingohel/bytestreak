<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron entry on the server: * * * * * php artisan schedule:run
// (The site also publishes lazily on the first visit of the day, so local dev works without it.)
Schedule::command('challenges:publish-daily')->dailyAt('00:01');
