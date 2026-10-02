<?php

return [

    /*
    |--------------------------------------------------------------------------
    | XP rules
    |--------------------------------------------------------------------------
    | by_difficulty is only the default the admin form suggests; every
    | challenge stores its own XP value.
    */
    'xp' => [
        'by_difficulty' => ['easy' => 10, 'medium' => 20, 'hard' => 30],

        // Showing up matters: a wrong answer on the daily challenge still earns this.
        'participation' => 2,

        // Correct daily answers earn +1 XP per streak day beyond the first, up to the cap.
        'streak_bonus_per_day' => 1,
        'streak_bonus_cap' => 10,

        // Archive / bonus challenges pay a share of the XP and never touch the streak.
        'practice_multiplier' => 0.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Streak rules
    |--------------------------------------------------------------------------
    | A streak only breaks when a published daily challenge is missed, so days
    | without a challenge (weekends, holidays) never cost anyone their streak.
    */
    'streak' => [
        'freeze_every' => 7,   // earn one freeze each time the streak hits a multiple of this
        'max_freezes' => 2,
    ],

    // When nothing is scheduled for a day, the oldest queued challenge is published.
    // Weekends are skipped unless this is on (an explicitly scheduled date always publishes).
    'publish_on_weekends' => (bool) env('BYTESTREAK_WEEKENDS', false),

    // One-click sign-in buttons on the login page. Only ever active when APP_ENV=local;
    // set BYTESTREAK_DEV_LOGIN=false to require a password even while developing.
    'dev_login' => (bool) env('BYTESTREAK_DEV_LOGIN', true),

    // The admin account `php artisan db:seed` creates. Read through config (not env() in the
    // seeder) so it still works when the config is cached on a server.
    'seed_admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Admin'),
        'email' => env('SEED_ADMIN_EMAIL', 'admin@bytestreak.test'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

    // Limit self sign-up to one email domain (null = open).
    'allowed_email_domain' => env('BYTESTREAK_ALLOWED_DOMAIN') ?: null,

    /*
    |--------------------------------------------------------------------------
    | Levels
    |--------------------------------------------------------------------------
    | Total XP needed to reach level L is 25 * (L - 1) * L:
    | 0, 50, 150, 300, 500, 750, 1050, 1400, 1800, 2250, 2750, 3300 …
    */
    'level_titles' => [
        1 => 'Hello World',
        2 => 'Script Rookie',
        3 => 'Bug Squasher',
        4 => 'Syntax Scout',
        5 => 'Merge Master',
        6 => 'Refactor Ranger',
        7 => 'Code Ninja',
        8 => 'Query Wizard',
        9 => 'Stack Sage',
        10 => 'Kernel Knight',
        11 => 'System Architect',
        12 => '10x Legend',
    ],

];
