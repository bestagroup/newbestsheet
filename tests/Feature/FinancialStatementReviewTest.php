<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Financial_statement;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\FinancialStatementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialStatementReviewTest extends TestCase
{
    private User $preparer;

    private User $reviewer;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        $this->artisan('db:seed', ['--force' => true])->assertExitCode(0);
        config(['operations.notifications.sms_enabled' => false]);
        $this->preparer = $this->user();
        $this->reviewer = $this->user();
        $company = Company::create(['company_name' => 'شرکت آزمون']);
        $this->project = Project::create(['title' => 'پرونده آزمون', 'company_name' => 'شرکت آزمون', 'company_id' => $company->id, 'invest_step' => 14]);
        $this->actingAs($this->preparer);
    }

    private function user(string $role = 'superadmin'): User
    {
        $user = User::create(['name' => 'Reviewer', 'email' => Str::uuid().'@example.test', 'password' => 'SafePassword123!', 'status' => 4, 'level' => 'admin', 'change_password' => 1]);
        if ($role !== '') {
            $user->roles()->attach(Role::where('title', $role)->firstOrFail());
        }

        return $user;
    }

    private function data(array $extra = []): array
    {
        return array_replace(['project_id' => $this->project->id, 'year' => 1404, 'month' => 6, 'period_type' => 'quarterly', 'period_start' => '1404/04/01', 'period_end' => '1404/06/31', 'income_basis' => 'standalone', 'net_sales' => '9007199254740993.25', 'net_profit' => '-25.50', 'total_assets' => '100.25', 'total_liabilities' => '40.10', 'total_equity' => '60.15'], $extra);
    }

    private function statement(array $extra = []): Financial_statement
    {
        return app(FinancialStatementService::class)->create($this->data($extra))->fresh();
    }

    private function transition(Financial_statement $row, string $action, ?string $note = null)
    {
        return $this->postJson('/panel/financialstatement/'.$row->id.'/review/'.$action, ['lock_version' => $row->fresh()->lock_version, 'note' => $note]);
    }

    public function test_submission_is_locked_and_self_approval_is_forbidden_even_for_superadmin(): void
    {
        $row = $this->statement();
        $this->assertSame('draft', $row->workflow_status);
        $this->transition($row, 'submit')->assertOk();
        $this->patchJson('/panel/financialstatement/'.$row->id, $this->data())->assertUnprocessable();
        $this->deleteJson('/panel/financialstatement/'.$row->id)->assertUnprocessable();
        $this->transition($row, 'approve')->assertUnprocessable()->assertJsonValidationErrors('workflow');
        $this->actingAs($this->reviewer);
        $this->transition($row, 'approve')->assertOk();
        $this->assertSame('approved', $row->fresh()->workflow_status);
        $this->assertSame('9007199254740993.25', $row->approvedRevisions()->first()->payload['net_sales']);
        $this->patchJson('/panel/financialstatement/'.$row->id, $this->data())->assertUnprocessable();
        $this->deleteJson('/panel/financialstatement/'.$row->id)->assertUnprocessable();
        $this->assertDatabaseHas('business_audits', ['subject_id' => $row->id, 'action' => 'review_approve', 'actor_id' => $this->reviewer->id]);
    }

    public function test_review_requires_separate_permission_and_history_requires_view_access(): void
    {
        $row = $this->statement();
        $this->transition($row, 'submit')->assertOk();
        $financialReviewer = $this->user('finance_management');
        $this->actingAs($financialReviewer);
        $this->transition($row, 'approve')->assertForbidden();
        DB::table('permission_role')->updateOrInsert(['role_id' => Role::where('title', 'finance_management')->value('id'), 'permission_id' => DB::table('permissions')->where('slug', 'financialstatement-review')->value('id')], ['can_view' => true, 'can_edit' => true, 'can_insert' => false, 'can_delete' => false]);
        $this->actingAs($financialReviewer->fresh());
        $this->transition($row, 'approve')->assertOk();
        $this->actingAs($this->user(''));
        $this->transition($row, 'submit')->assertForbidden();
        $this->getJson('/panel/financialstatement/'.$row->id.'/revisions')->assertForbidden();
    }

    public function test_invalid_balance_or_incomplete_financial_data_cannot_be_submitted(): void
    {
        $row = $this->statement(['total_equity' => '60.16']);
        $this->transition($row, 'submit')->assertUnprocessable();
        app(FinancialStatementService::class)->update($row->id, $this->data(['net_profit' => null]));
        $this->transition($row, 'submit')->assertUnprocessable();
        app(FinancialStatementService::class)->update($row->id, $this->data(['total_current_assets' => '70', 'total_non_current_assets' => '31']));
        $this->transition($row, 'submit')->assertUnprocessable();
        $this->assertSame('draft', $row->fresh()->workflow_status);
        $this->assertDatabaseCount('financial_statement_revisions', 0);
        app(FinancialStatementService::class)->update($row->id, $this->data(['total_liabilities' => '120.25', 'total_equity' => '-20', 'total_current_assets' => null, 'total_non_current_assets' => null]));
        $this->transition($row, 'submit')->assertOk();
    }

    public function test_return_requires_reason_and_tracks_every_editor_as_a_preparer(): void
    {
        $row = $this->statement();
        $this->actingAs($this->reviewer);
        app(FinancialStatementService::class)->update($row->id, $this->data());
        $this->actingAs($this->preparer);
        $this->transition($row, 'submit')->assertOk();
        $this->actingAs($this->reviewer);
        $this->transition($row, 'approve')->assertUnprocessable();
        $this->actingAs($this->user());
        $this->transition($row, 'return')->assertUnprocessable();
        $this->transition($row, 'return', 'رفع مغایرت با مستند اصلی')->assertOk();
        $this->assertSame('draft', $row->fresh()->workflow_status);
        $this->assertSame('رفع مغایرت با مستند اصلی', $row->fresh()->review_note);
    }

    public function test_correction_keeps_approved_values_in_reports_until_new_approval(): void
    {
        $row = $this->statement();
        $url = '/panel/report?'.http_build_query(['chart_companies' => ['company:'.$this->project->company_id]]);
        $this->get($url)->assertOk()->assertViewHas('investmentSummary', fn ($s) => $s['profit'] === null);
        $this->transition($row, 'submit')->assertOk();
        $this->actingAs($this->reviewer);
        $this->transition($row, 'approve')->assertOk();
        $this->actingAs($this->preparer);
        $this->transition($row, 'revise', 'اصلاح سود خالص')->assertOk();
        $this->patchJson('/panel/financialstatement/'.$row->id, $this->data(['net_profit' => '75.55']))->assertOk();
        $this->get($url)->assertOk()->assertViewHas('investmentSummary', fn ($s) => $s['profit'] === '-25.50')
            ->assertViewHas('boardCharts', fn ($data) => collect($data['charts'])->firstWhere('id', 'quarterly-standalone-profit')['series'][0]['values'] === ['-25.50']);
        $this->patchJson('/panel/financialstatement/'.$row->id, $this->data(['month' => 9, 'period_end' => '1404/09/30']))->assertUnprocessable();
        $this->deleteJson('/panel/financialstatement/'.$row->id)->assertUnprocessable();
        $this->transition($row, 'submit')->assertOk();
        $this->actingAs($this->reviewer);
        $this->transition($row, 'approve')->assertOk();
        $this->get($url)->assertOk()->assertViewHas('investmentSummary', fn ($s) => $s['profit'] === '75.55');
        $this->getJson('/panel/financialstatement/'.$row->id.'/revisions')->assertOk()->assertJsonCount(2, 'revisions')->assertJsonPath('revisions.0.revision', 2);
        $this->assertSame('-25.50', $row->approvedRevisions()->where('revision', 1)->first()->payload['net_profit']);
    }

    public function test_stale_commands_and_repeated_approvals_do_not_create_extra_versions(): void
    {
        $row = $this->statement();
        $this->transition($row, 'submit')->assertOk();
        $this->actingAs($this->reviewer);
        $this->postJson('/panel/financialstatement/'.$row->id.'/review/approve', ['lock_version' => 1])->assertUnprocessable();
        $this->transition($row, 'approve')->assertOk();
        $this->transition($row, 'approve')->assertUnprocessable();
        $this->assertDatabaseCount('financial_statement_revisions', 1);
    }

    public function test_audit_failure_rolls_back_approval_and_snapshot(): void
    {
        $row = $this->statement();
        $this->transition($row, 'submit')->assertOk();
        $this->actingAs($this->reviewer);
        // Real database failure verifies the transaction boundary, not a mocked final service.
        Schema::drop('business_audits');
        $this->transition($row, 'approve')->assertServerError();
        $this->assertSame('submitted', $row->fresh()->workflow_status);
        $this->assertDatabaseCount('financial_statement_revisions', 0);
    }

    public function test_approved_snapshots_are_immutable_and_rerunning_migration_preserves_review_state(): void
    {
        $row = $this->statement();
        $this->transition($row, 'submit')->assertOk();
        $this->actingAs($this->reviewer);
        $this->transition($row, 'approve')->assertOk();
        $migration = require database_path('migrations/2026_10_20_000001_add_financial_review_workflow.php');
        $migration->up();
        $migration->up();
        $this->assertSame('approved', $row->fresh()->workflow_status);
        $snapshot = $row->approvedRevisions()->first();
        $this->expectException(\LogicException::class);
        $snapshot->update(['payload' => ['net_profit' => '0']]);
    }

    public function test_upgrade_marks_existing_records_unreviewed_and_retains_known_preparers(): void
    {
        $row = $this->statement();
        Schema::drop('financial_statement_revisions');
        Schema::table('financial_statements', fn ($table) => $table->dropColumn(['workflow_status', 'revision', 'lock_version', 'preparer_ids', 'submitted_by', 'submitted_at', 'review_note']));
        $migration = require database_path('migrations/2026_10_20_000001_add_financial_review_workflow.php');
        $migration->up();
        $migration->up();
        $upgraded = $row->fresh();
        $this->assertSame('legacy', $upgraded->workflow_status);
        $this->assertSame([$this->preparer->id], $upgraded->preparer_ids);
        $this->assertSame('9007199254740993.25', $upgraded->net_sales);
        $this->assertDatabaseCount('financial_statement_revisions', 0);
        $this->actingAs($this->reviewer);
        $this->transition($upgraded, 'submit')->assertOk();
        $this->actingAs($this->preparer);
        $this->transition($upgraded, 'approve')->assertUnprocessable();
    }

    public function test_client_cannot_inject_approval_or_revision_metadata(): void
    {
        $this->postJson('/panel/financialstatement', $this->data(['workflow_status' => 'approved', 'revision' => 99, 'preparer_ids' => [], 'submitted_by' => $this->reviewer->id]))->assertOk();
        $row = Financial_statement::firstOrFail();
        $this->assertSame('draft', $row->workflow_status);
        $this->assertSame(1, $row->revision);
        $this->assertSame([$this->preparer->id], $row->preparer_ids);
        $this->assertNull($row->submitted_by);
        $this->assertDatabaseCount('financial_statement_revisions', 0);
    }
}
