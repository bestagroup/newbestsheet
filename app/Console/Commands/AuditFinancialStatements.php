<?php

namespace App\Console\Commands;

use App\Support\FinancialPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Read-only inventory; also usable before the v3.4 metadata migration. */
class AuditFinancialStatements extends Command
{
    protected $signature = 'financial-statements:audit {--json : Machine-readable report}';

    protected $description = 'Read-only audit of financial statement ownership, periods and duplicate company observations';

    public function handle(): int
    {
        if (! Schema::hasTable('financial_statements')) {
            $this->error('financial_statements table is missing.');

            return self::FAILURE;
        }
        $hasOwner = Schema::hasColumn('financial_statements', 'company_id');
        $issues = [];
        $seen = [];
        $count = 0;
        $workflowCounts = [];
        DB::table('financial_statements')->orderBy('id')->chunkById(500, function ($rows) use (&$issues, &$seen, &$count, &$workflowCounts, $hasOwner): void {
            $projects = DB::table('projects')->whereIn('id', $rows->pluck('project_id'))->get(['id', 'company_id'])->keyBy('id');
            foreach ($rows as $row) {
                $count++;
                $status = $row->workflow_status ?? 'legacy';
                $workflowCounts[$status] = ($workflowCounts[$status] ?? 0) + 1;
                $project = $projects->get($row->project_id);
                $owner = $row->company_id ?? $project?->company_id;
                $codes = [];
                if (! $project) {
                    $codes[] = 'missing_project';
                }
                if (! $owner) {
                    $codes[] = 'missing_company';
                }
                if ($hasOwner && empty($row->company_id)) {
                    $codes[] = 'unassigned_owner';
                }
                if (! empty($row->company_id) && (int) $row->company_id !== (int) $project?->company_id) {
                    $codes[] = 'owner_mismatch';
                }
                if (($row->income_basis ?? 'unconfirmed') === 'unconfirmed') {
                    $codes[] = 'unconfirmed_period';
                }
                if (FinancialPeriod::errors((array) $row)) {
                    $codes[] = 'invalid_period';
                }
                if (! in_array($row->period_type ?? 'legacy', ['annual', 'quarterly', 'legacy'], true)
                    || ! in_array($row->income_basis ?? 'unconfirmed', ['standalone', 'ytd', 'unconfirmed'], true)
                    || $row->year < 1300 || $row->year > 1600 || $row->month < 1 || $row->month > 12) {
                    $codes[] = 'invalid_classification';
                }
                if ($codes) {
                    $issues[] = ['id' => $row->id, 'project_id' => $row->project_id, 'company_id' => $owner, 'issues' => $codes];
                }
                $key = ($owner ? 'company:'.$owner : 'project:'.$row->project_id).'/'.$row->year.'/'.$row->month.'/'.($row->period_type ?? 'legacy');
                $seen[$key][] = $row->id;
            }
        });
        $duplicates = array_filter($seen, fn ($ids) => count($ids) > 1);
        $report = ['records' => $count, 'metadata_schema_present' => $hasOwner, 'workflow_counts' => $workflowCounts, 'issues' => $issues, 'duplicate_groups' => $duplicates];
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        } else {
            $this->info('Records: '.$count.'; issue records: '.count($issues).'; duplicate groups: '.count($duplicates));
            foreach ($issues as $issue) {
                $this->line('Statement '.$issue['id'].': '.implode(', ', $issue['issues']));
            }
            foreach ($duplicates as $key => $ids) {
                $this->line($key.': '.implode(', ', $ids));
            }
        }

        return $issues || $duplicates ? self::FAILURE : self::SUCCESS;
    }
}
