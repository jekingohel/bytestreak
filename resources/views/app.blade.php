<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0b0d12">
        <meta name="description" content="ByteStreak: one short developer challenge a day, with XP, streaks, badges and a team leaderboard.">

        <title inertia>{{ config('app.name') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Apply the saved theme before first paint so the page never flashes the wrong colours. --}}
        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('bytestreak-theme');
                    if (theme === 'light' || theme === 'dark') {
                        document.documentElement.dataset.theme = theme;
                    }
                } catch (e) {}
            })();
        </script>

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
