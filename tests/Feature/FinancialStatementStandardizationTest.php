<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Financial_statement;
use App\Models\Project;
use App\Services\BoardFinancialChartsService;
use App\Services\FinancialStatementService;
use App\Services\OperationalAnalyticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialStatementStandardizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $this->withoutMiddleware();
    }

    private function project(?Company $company = null): Project
    {
        $company ??= Company::create(['company_name' => 'شرکت نمونه']);

        return Project::withoutEvents(fn () => Project::create(['title' => 'پرونده', 'company_name' => 'شرکت نمونه', 'company_id' => $company->id, 'invest_step' => 14]));
    }

    private function data(Project $project, array $extra = []): array
    {
        return array_replace(['project_id' => $project->id, 'year' => 1404, 'month' => 6, 'period_type' => 'quarterly', 'period_start' => '1404/04/01', 'period_end' => '1404/06/31', 'income_basis' => 'standalone', 'net_sales' => '9007199254740993.25', 'net_profit' => '-25.75'], $extra);
    }

    public function test_localized_period_and_precise_amounts_are_saved_with_company_owner(): void
    {
        $project = $this->project();
        $this->postJson('/panel/financialstatement', $this->data($project, ['period_start' => '۱۴۰۴/۰۴/۰۱', 'period_end' => '۱۴۰۴/۰۶/۳۱', 'company_id' => 999]))->assertOk();
        $this->assertDatabaseHas('financial_statements', ['company_id' => $project->company_id, 'period_start' => '1404/04/01', 'period_end' => '1404/06/31', 'net_sales' => '9007199254740993.25', 'net_profit' => '-25.75']);
        $this->assertDatabaseHas('business_audits', ['subject_type' => Financial_statement::class, 'action' => 'created']);
    }

    public function test_invalid_dates_and_incompatible_lengths_cannot_be_written(): void
    {
        $project = $this->project();
        foreach ([['period_end' => '1404/06/32'], ['period_start' => '1404/01/01'], ['period_start' => '1404/07/01'], ['month' => 5], ['period_start' => null], ['income_basis' => 'unconfirmed']] as $extra) {
            $this->postJson('/panel/financialstatement', $this->data($project, $extra))->assertUnprocessable();
        }
        $this->assertDatabaseCount('financial_statements', 0);
    }

    public function test_ytd_and_non_calendar_quarter_can_be_recorded_but_metadata_cannot_be_erased(): void
    {
        $project = $this->project();
        $service = app(FinancialStatementService::class);
        $ytd = $service->create($this->data($project, ['period_start' => '1404/01/01', 'income_basis' => 'ytd']));
        $this->assertSame('ytd', $ytd->income_basis);
        $this->postJson('/panel/financialstatement', $this->data($project, ['month' => 9, 'period_start' => '1404/07/01', 'period_end' => '1404/09/30']))->assertOk();
        $this->postJson('/panel/financialstatement', $this->data($project, ['month' => 5, 'period_start' => '1404/03/01', 'period_end' => '1404/05/31']))->assertOk();
        $this->patchJson('/panel/financialstatement/'.$ytd->id, $this->data($project, ['income_basis' => 'unconfirmed', 'period_start' => null, 'period_end' => null]))->assertUnprocessable()->assertJsonValidationErrors('income_basis');
        $this->assertSame('ytd', $ytd->fresh()->income_basis);
    }

    public function test_same_company_period_is_rejected_across_different_projects(): void
    {
        $a = $this->project();
        $b = $this->project($a->company);
        $service = app(FinancialStatementService::class);
        $service->create($this->data($a));
        try {
            $service->create($this->data($b));
            $this->fail('Duplicate company period must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('project_id', $e->errors());
        }
        $this->assertDatabaseCount('financial_statements', 1);
    }

    public function test_migration_resumes_without_inferring_periods_merging_duplicates_or_changing_amounts(): void
    {
        $a = $this->project();
        $b = $this->project($a->company);
        foreach ([$a, $b] as $project) {
            Financial_statement::create(['project_id' => $project->id, 'year' => 1404, 'month' => 6, 'period_type' => 'quarterly', 'net_sales' => '9007199254740993.25']);
        }
        Schema::table('financial_statements', function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['company_id'] : 'fs_company_fk');
            $table->dropIndex('fs_company_period_index');
            $table->dropColumn(['company_id', 'period_start', 'period_end', 'income_basis']);
        });
        $this->assertSame(1, Artisan::call('financial-statements:audit', ['--json' => true]));
        $this->assertFalse(json_decode(Artisan::output(), true)['metadata_schema_present']);
        // Simulate a previous interrupted DDL attempt with just one added column.
        Schema::table('financial_statements', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $migration = require database_path('migrations/2026_10_19_000001_standardize_financial_statements.php');
        $migration->up();
        $migration->up();
        $this->assertDatabaseCount('financial_statements', 2);
        foreach (Financial_statement::all() as $row) {
            $this->assertSame($a->company_id, $row->company_id);
            $this->assertSame('9007199254740993.25', $row->net_sales);
            $this->assertNull($row->period_start);
            $this->assertSame('unconfirmed', $row->income_basis);
        }
        $before = DB::table('financial_statements')->get()->toJson();
        $this->assertSame(1, Artisan::call('financial-statements:audit', ['--json' => true]));
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(1, $report['duplicate_groups']);
        $this->assertCount(2, $report['issues']);
        $this->assertSame($before, DB::table('financial_statements')->get()->toJson());
    }

    public function test_chart_separates_bases_and_exact_ranges_and_preserves_source_access(): void
    {
        $a = $this->project();
        $b = $this->project();
        $hidden = $this->project($a->company);
        $service = app(FinancialStatementService::class);
        $service->create($this->data($a));
        $service->create($this->data($b, ['income_basis' => 'ytd', 'period_start' => '1404/01/01', 'net_sales' => '500']));
        Financial_statement::create($this->data($hidden, ['month' => 9, 'period_start' => '1404/07/01', 'period_end' => '1404/09/30', 'net_sales' => '987654321.12']));
        $access = $this->mock(OperationalAnalyticsService::class);
        $access->shouldReceive('visibleProjectIds')->andReturn(collect([$a->id, $b->id]));
        $report = app(BoardFinancialChartsService::class)->build(Request::create('/', 'GET', ['chart_companies' => ['company:'.$a->company_id, 'company:'.$b->company_id]]));
        $charts = collect($report['charts']);
        $standalone = $charts->firstWhere('id', 'quarterly-standalone-sales');
        $ytd = $charts->firstWhere('id', 'quarterly-ytd-sales');
        $this->assertSame(['1404/06/31 (از 1404/04/01)'], $standalone['labels']);
        $this->assertSame(['9007199254740993.25'], $standalone['series'][0]['values']);
        $this->assertSame([null], $standalone['series'][1]['values']);
        $this->assertSame(['500'], $ytd['series'][1]['values']);
        $this->assertStringNotContainsString('987654321.12', json_encode($report));
    }

    public function test_company_reassignment_does_not_relabel_old_financial_history(): void
    {
        $a = $this->project();
        $statement = app(FinancialStatementService::class)->create($this->data($a));
        $oldCompany = $a->company_id;
        $newCompany = Company::create(['company_name' => 'شرکت دیگر']);
        DB::table('projects')->where('id', $a->id)->update(['company_id' => $newCompany->id]);
        $this->assertSame(0, Financial_statement::consistentOwner()->count());
        $this->patchJson('/panel/financialstatement/'.$statement->id, $this->data($a))->assertUnprocessable()->assertJsonValidationErrors('project_id');
        $this->assertSame($oldCompany, $statement->fresh()->company_id);
        $this->assertSame(1, Artisan::call('financial-statements:audit', ['--json' => true]));
        $this->assertStringContainsString('owner_mismatch', Artisan::output());
    }

    public function test_clean_standardized_statement_passes_quality_audit(): void
    {
        app(FinancialStatementService::class)->create($this->data($this->project()));
        $this->assertSame(0, Artisan::call('financial-statements:audit', ['--json' => true]));
        $this->assertSame([], json_decode(Artisan::output(), true)['issues']);
    }
}
