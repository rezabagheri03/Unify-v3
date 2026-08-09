<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Milestone 2: Grace period wipe (every 5 min + lazy check fallback)
        $schedule->command('enrollments:wipe-grace')->everyFiveMinutes();

        // Milestone 5: Ticket escalation
        $schedule->command('tickets:escalate')->hourly();

        // Milestone 7: Calendar warnings
        $schedule->command('calendar:warn')->dailyAt('08:00');

        // Milestone 3: Resource old versions cleanup
        $schedule->command('resources:cleanup-old-versions')->dailyAt('03:00');

        // Milestone 3 + C4: LRU cleanup for 50GB storage
        $schedule->command('files:lru-cleanup')->dailyAt('04:00');

        // Milestone 1 + H1: Idempotency cleanup
        $schedule->command('idempotency:cleanup')->dailyAt('02:00');

        // Storage stats
        $schedule->command('storage:calculate-stats')->dailyAt('01:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        if (file_exists(base_path('routes/console.php'))) {
            require base_path('routes/console.php');
        }
    }
}