<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Schedule (Laravel 11) — run via `php artisan schedule:run`
|--------------------------------------------------------------------------
| cPanel cron: every 5 minutes: cd /home/username/unify-backend && php artisan schedule:run
*/

// F20 + C3: grace-period wipe every 5 minutes (lazy check lives in EnrollmentController@finalize)
Schedule::command('enrollments:wipe-grace')->everyFiveMinutes();

// F08: ticket escalation (48h no staff reply -> level 1, 48h more -> level 2)
Schedule::command('tickets:escalate')->hourly();

// F11: academic calendar warnings (7d / 24h)
Schedule::command('calendar:warn')->dailyAt('08:00');

// F05: hard-delete superseded resource file content after 30 days
Schedule::command('resources:cleanup-old-versions')->dailyAt('03:00');

// F05 + C4: LRU cleanup to keep storage under 80% of 50GB
Schedule::command('files:lru-cleanup')->dailyAt('04:00');

// H1: purge expired idempotency keys
Schedule::command('idempotency:cleanup')->dailyAt('02:00');

// C4: storage usage stats for monitoring
Schedule::command('storage:calculate-stats')->dailyAt('01:00');

// F12: mark late assignments
Schedule::command('assignments:mark-late')->hourly();

// Backups (DB audit fix): these commands existed but were scheduled nowhere.
Schedule::command('backup:database --compress')->dailyAt('02:30');
Schedule::command('backup:files --compress')->dailyAt('03:30');

// SEC-03 fix: drop expired Sanctum tokens nightly.
Schedule::command('sanctum:prune-expired --hours=24')->dailyAt('04:30');

// PERF-09: purge expired golden-schedule cache rows (the shared cache would
// otherwise grow unbounded during enrollment weeks).
Schedule::call(function () {
    \App\Models\GoldenScheduleCache::where('expires_at', '<', now())->delete();
})->dailyAt('02:15')->name('golden-cache-purge');

// Artisan inspire (dev nicety)
Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');
