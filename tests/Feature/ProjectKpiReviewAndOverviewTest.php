<?php

namespace Tests\Feature;

use App\Enums\InvestmentRole;
use App\Models\KPI;
use App\Models\Project;
use App\Models\Project_step;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectKpiReviewAndOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_kpi_creates_a_new_revision_and_preserves_the_previous_record(): void
    {
        $user = $this->userWithRole(InvestmentRole::SuperAdmin);
        $project = $this->project();
        $original = $this->kpi($project, '100', '1405/03/31');

        $this->mock(ActivityLogService::class)->shouldReceive('record')->once();

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->patchJson("/panel/flow/{$project->id}/kpis/{$original->id}", [
                'kpi_number' => 1,
                'title' => 'رشد فروش اصلاح‌شده',
                'type' => 'کمی',
                'type_value' => 'حجم',
                'value' => '250',
                'unit' => 'عدد',
                'deadline' => '1405/06/31',
                'period_time' => 'فصلی',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.previous_kpi_id', $original->id)
            ->assertJsonPath('data.revision_number', 2);

        $original->refresh();
        $revision = KPI::query()->current()->where('project_id', $project->id)->firstOrFail();

        $this->assertFalse($original->is_current);
        $this->assertSame('superseded', $original->review_status);
        $this->assertSame('100', $original->value);
        $this->assertSame('1405/03/31', $original->deadline);
        $this->assertSame($original->id, $revision->previous_kpi_id);
        $this->assertSame($original->id, $revision->root_kpi_id);
        $this->assertSame('250', $revision->value);
        $this->assertSame('250.000000', $revision->target_value);
        $this->assertSame('1405/06/31', $revision->deadline);
        $this->assertSame('pending', $revision->review_status);
        $this->assertDatabaseCount('kpis', 2);

        $this->withoutMiddleware()
            ->actingAs($user)
            ->deleteJson("/panel/flow/{$project->id}/kpis/{$revision->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kpi');
    }

    public function test_assigned_investment_expert_can_approve_or_reject_current_kpi(): void
    {
        $expert = $this->userWithRole(InvestmentRole::Expert);
        $project = $this->project();
        $kpi = $this->kpi($project);
        $role = $expert->roles()->firstOrFail();
        ProjectAssignment::query()->create([
            'project_id' => $project->id,
            'user_id' => $expert->id,
            'role_id' => $role->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        $this->mock(ActivityLogService::class)->shouldReceive('record')->once();

        $this->withoutMiddleware()
            ->actingAs($expert)
            ->patchJson("/panel/flow/{$project->id}/kpis/{$kpi->id}/review", [
                'decision' => 'approved',
                'review_comment' => 'هدف و زمان‌بندی قابل قبول است.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.review_status', 'approved');

        $this->assertDatabaseHas('kpis', [
            'id' => $kpi->id,
            'review_status' => 'approved',
            'reviewed_by' => $expert->id,
            'review_comment' => 'هدف و زمان‌بندی قابل قبول است.',
        ]);
    }

    public function test_rejecting_a_kpi_requires_a_reason(): void
    {
        $user = $this->userWithRole(InvestmentRole::SuperAdmin);
        $project = $this->project();
        $kpi = $this->kpi($project);

        $this->withoutMiddleware()
            ->actingAs($user)
            ->patchJson("/panel/flow/{$project->id}/kpis/{$kpi->id}/review", [
                'decision' => 'rejected',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review_comment');
    }

    public function test_direct_company_page_has_the_panel_layout_and_deduplicates_workflow_steps(): void
    {
        $user = $this->userWithRole(InvestmentRole::SuperAdmin);
        $project = $this->project();
        Project_step::query()->create([
            'project_id' => $project->id,
            'title' => 'بررسی اولیه',
            'step_number' => 2,
            'description' => 'تصمیم قدیمی',
            'status' => 'approved',
            'user_id' => $user->id,
        ]);
        Project_step::query()->create([
            'project_id' => $project->id,
            'title' => 'بررسی اولیه',
            'step_number' => 2,
            'description' => 'آخرین تصمیم معتبر',
            'status' => 'approved',
            'user_id' => $user->id,
        ]);

        $this->withoutMiddleware()
            ->actingAs($user)
            ->get("/panel/flow/{$project->id}")
            ->assertOk()
            ->assertSee('<html lang="fa"', false)
            ->assertSee('نمای یکپارچه پرونده سرمایه‌گذاری')
            ->assertSee('آخرین تصمیم معتبر')
            ->assertDontSee('تصمیم قدیمی')
            ->assertSee('گزارش مالی شرکت');
    }

    private function userWithRole(InvestmentRole $investmentRole): User
    {
        $role = Role::query()->firstOrCreate(
            ['title' => $investmentRole->value],
            ['title_fa' => $investmentRole->label(), 'status' => 4]
        );
        $user = User::query()->create([
            'name' => $investmentRole->label(),
            'email' => $investmentRole->value.uniqid().'@example.test',
            'password' => 'password',
            'level' => 'admin',
            'status' => 4,
        ]);
        $user->roles()->attach($role->id);

        return $user->fresh();
    }

    private function project(): Project
    {
        return Project::withoutEvents(fn (): Project => Project::query()->create([
            'title' => 'طرح نمونه',
            'company_name' => 'شرکت نمونه',
            'invest_step' => Project::PORTFOLIO_MINIMUM_STEP,
            'progress_percentage' => 65,
            'amount_request_accept' => 1000000,
        ]));
    }

    private function kpi(Project $project, string $value = '100', string $deadline = '1405/03/31'): KPI
    {
        return KPI::query()->create([
            'project_id' => $project->id,
            'code' => "P{$project->id}-KPI-1",
            'title' => 'رشد فروش',
            'kpi_number' => 1,
            'value' => $value,
            'target_value' => $value,
            'unit' => 'عدد',
            'deadline' => $deadline,
            'period_time' => 'فصلی',
            'status' => 'active',
        ]);
    }
}
