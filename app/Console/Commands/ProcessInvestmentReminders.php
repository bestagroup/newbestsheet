<?php

namespace App\Console\Commands;

use App\Services\InvestmentReminderService;
use Illuminate\Console\Command;

class ProcessInvestmentReminders extends Command
{
    protected $signature = 'investment:process-reminders';

    protected $description = 'Process KPI, commitment and calendar operational reminders.';

    public function handle(InvestmentReminderService $reminders): int
    {
        if (! config('operations.reminders.enabled', true)) {
            $this->info('Operational reminders are disabled.');

            return self::SUCCESS;
        }

        $result = $reminders->process();
        $this->info(sprintf(
            'Processed reminders: KPI=%d, commitments=%d, calendar=%d',
            $result['kpis'],
            $result['commitments'],
            $result['calendar']
        ));

        return self::SUCCESS;
    }
}
