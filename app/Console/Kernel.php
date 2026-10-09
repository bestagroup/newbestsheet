<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('documents:scan')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('auth:clear-resets')
            ->dailyAt('02:10')
            ->withoutOverlapping();

        $schedule->command('queue:prune-failed', [
            '--hours' => (string) config('operations.failed_jobs_retention_hours', 720),
        ])->dailyAt('02:20')->withoutOverlapping();

        $schedule->command('investment:process-reminders')
            ->everyThirtyMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
