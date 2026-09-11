<?php

namespace Tests\Unit;

use App\Enums\InvestmentRole;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\InvestmentWorkflowAccessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizationalWorkflowAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('project_assignments');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('status')->default(4);
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->unique();
            $table->string('title_fa')->nullable();
            $table->integer('status')->default(4);
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->primary(['role_id', 'user_id']);
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedSmallInteger('invest_step')->default(1);
            $table->timestamps();
        });
        Schema::create('project_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('invest_step_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_investment_management_is_limited_to_steps_one_through_contract(): void
    {
        [$user, $role] = $this->userWithRole(InvestmentRole::InvestmentManagement);
        $service = app(InvestmentWorkflowAccessService::class);
        $preContract = $this->project(13);
        $postContract = $this->project(Project::PORTFOLIO_MINIMUM_STEP);

        $this->assertTrue($service->canManageStage($user, 1));
        $this->assertTrue($service->canManageStage($user, 13));
        $this->assertFalse($service->canManageStage($user, Project::PORTFOLIO_MINIMUM_STEP));
        $this->assertTrue($service->canViewProject($user, $preContract->id));
        $this->assertFalse($service->canViewProject($user, $postContract->id));
        $this->assertSame($role->id, $service->decisionContext($user, $preContract->id, 13)['role']->id);
        $this->assertNull($service->decisionContext($user, $postContract->id, Project::PORTFOLIO_MINIMUM_STEP));
    }

    public function test_portfolio_affairs_management_only_controls_post_contract_steps(): void
    {
        [$user, $role] = $this->userWithRole(InvestmentRole::PortfolioAffairsManagement);
        $service = app(InvestmentWorkflowAccessService::class);
        $preContract = $this->project(13);
        $postContract = $this->project(Project::PORTFOLIO_MINIMUM_STEP);

        $this->assertFalse($service->canManageStage($user, 13));
        $this->assertTrue($service->canManageStage($user, Project::PORTFOLIO_MINIMUM_STEP));
        $this->assertFalse($service->canViewProject($user, $preContract->id));
        $this->assertTrue($service->canViewProject($user, $postContract->id));
        $this->assertSame(
            $role->id,
            $service->decisionContext($user, $postContract->id, Project::PORTFOLIO_MINIMUM_STEP)['role']->id
        );
    }

    public function test_board_and_finance_roles_cannot_make_workflow_decisions(): void
    {
        [$board] = $this->userWithRole(InvestmentRole::ExecutiveBoard);
        [$finance] = $this->userWithRole(InvestmentRole::FinanceManagement);
        $service = app(InvestmentWorkflowAccessService::class);

        $this->assertFalse($service->canManageStage($board, 1));
        $this->assertFalse($service->canManageStage($board, 14));
        $this->assertFalse($service->canManageStage($finance, 1));
        $this->assertFalse($service->canManageStage($finance, 14));
    }

    /** @return array{User, Role} */
    private function userWithRole(InvestmentRole $investmentRole): array
    {
        $role = Role::query()->create([
            'title' => $investmentRole->value,
            'title_fa' => $investmentRole->label(),
            'status' => 4,
        ]);
        $user = User::query()->create([
            'name' => $investmentRole->label(),
            'email' => $investmentRole->value.'@example.test',
            'password' => 'password',
            'status' => 4,
        ]);
        $user->roles()->attach($role->id);

        return [$user->fresh(), $role];
    }

    private function project(int $step): Project
    {
        return Project::withoutEvents(fn (): Project => Project::query()->create([
            'title' => 'طرح مرحله '.$step,
            'invest_step' => $step,
        ]));
    }
}
