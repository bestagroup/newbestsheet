<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemHealthCheck extends Command
{
    protected $signature = 'system:health-check';

    protected $description = 'Validate production-critical application, storage, database, queue, and integration configuration.';

    public function handle(): int
    {
        $failures = 0;
        $warnings = 0;

        $this->components->info('BestSheet VC system health check');

        $this->check(
            filled(config('app.key')),
            'APP_KEY is configured.',
            'APP_KEY is missing.',
            $failures
        );

        if (app()->environment('production')) {
            $this->check(
                config('app.debug') === false,
                'APP_DEBUG is disabled in production.',
                'APP_DEBUG must be false in production.',
                $failures
            );

            $this->warnIf(
                config('logging.default') === 'single',
                'Single-file logging is enabled in production; daily rotation is recommended.',
                $warnings
            );
        }

        try {
            DB::connection()->getPdo();
            $this->components->task('Database connection', static fn (): bool => true);
        } catch (Throwable $exception) {
            $this->components->error('Database connection failed: '.$exception->getMessage());
            $failures++;
        }

        foreach ([storage_path(), storage_path('framework'), storage_path('logs')] as $directory) {
            $this->check(
                is_dir($directory) && is_writable($directory),
                sprintf('%s is writable.', $directory),
                sprintf('%s must exist and be writable.', $directory),
                $failures
            );
        }

        if (config('queue.default') === 'database') {
            $this->check(
                Schema::hasTable('jobs'),
                'Database queue table exists.',
                'QUEUE_CONNECTION=database requires the jobs table. Run pending migrations.',
                $failures
            );
        }

        foreach (['project_commitments', 'operational_notification_deliveries'] as $table) {
            $this->check(
                Schema::hasTable($table),
                sprintf('%s table exists.', $table),
                sprintf('%s table is missing. Run pending migrations.', $table),
                $failures
            );
        }

        if (Schema::hasTable('operational_notification_deliveries')) {
            $maxAttempts = max(1, (int) config('operations.notifications.delivery_max_attempts', 3));
            $claimTimeout = max(1, (int) config('operations.notifications.delivery_claim_timeout_minutes', 15));
            $staleBefore = now()->subMinutes($claimTimeout);

            $staleProcessing = DB::table('operational_notification_deliveries')
                ->where('status', 'processing')
                ->where('updated_at', '<=', $staleBefore)
                ->count();
            $exhaustedFailures = DB::table('operational_notification_deliveries')
                ->where('status', 'failed')
                ->where('attempts', '>=', $maxAttempts)
                ->count();

            $this->warnIf(
                $staleProcessing > 0,
                sprintf('%d operational notification delivery claim(s) are stale and will be reclaimed on the next matching run.', $staleProcessing),
                $warnings
            );
            $this->warnIf(
                $exhaustedFailures > 0,
                sprintf('%d operational notification delivery item(s) exhausted their retry budget and require review.', $exhaustedFailures),
                $warnings
            );
        }

        if (app()->environment('production')) {
            $this->check(
                filled(config('services.ghasedak.api_key')),
                'Ghasedak API key is configured.',
                'Ghasedak API key is missing while OTP or operational SMS is enabled.',
                $failures
            );

            $this->warnIf(
                ! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret')),
                'Google OAuth credentials are not configured; Google sign-in/calendar sync will be unavailable.',
                $warnings
            );
        }

        $this->newLine();
        $this->line(sprintf('Failures: %d | Warnings: %d', $failures, $warnings));

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(bool $condition, string $success, string $failure, int &$failures): void
    {
        if ($condition) {
            $this->components->task($success, static fn (): bool => true);

            return;
        }

        $this->components->error($failure);
        $failures++;
    }

    private function warnIf(bool $condition, string $message, int &$warnings): void
    {
        if (! $condition) {
            return;
        }

        $this->components->warn($message);
        $warnings++;
    }
}
