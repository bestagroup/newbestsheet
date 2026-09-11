<?php

namespace Tests\Feature;

use App\Models\Financial_statement;
use App\Models\Project;
use App\Services\ActivityLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialStatementManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = 'http://localhost';
        config(['app.url' => 'http://localhost']);
        $this->app['url']->forceRootUrl('http://localhost');

        Schema::dropIfExists('financial_statements');
        Schema::dropIfExists('projects');

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('company_name')->nullable();
            $table->unsignedSmallInteger('invest_step')->default(1);
            $table->timestamps();
        });

        Schema::create('financial_statements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            foreach (Financial_statement::monetaryFields() as $field) {
                $table->string($field)->nullable();
            }

            $table->timestamps();
            $table->unique(['project_id', 'year', 'month']);
        });

        $this->withoutMiddleware();
    }

    public function test_ajax_list_only_contains_portfolio_records_and_supports_filters(): void
    {
        $portfolioProject = $this->project('شرکت پورتفو', Project::PORTFOLIO_MINIMUM_STEP);
        $prePortfolioProject = $this->project('طرح در حال ارزیابی', Project::PORTFOLIO_MINIMUM_STEP - 1);

        $visibleStatement = $this->statement($portfolioProject, 1404, 12, '1250000');
        $this->statement($portfolioProject, 1403, 12, '900000');
        $this->statement($prePortfolioProject, 1404, 12, '777000');

        $dataTableRequest = [
            'draw' => 1,
            'start' => 0,
            'length' => 15,
            'project_id' => $portfolioProject->id,
            'year' => 1404,
            'columns' => [
                ['data' => 'company_name', 'name' => 'company_name', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'period', 'name' => 'period_sort', 'searchable' => 'false', 'orderable' => 'true'],
            ],
            'order' => [['column' => 1, 'dir' => 'desc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ];

        $response = $this
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/panel/financialstatement?'.http_build_query($dataTableRequest));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleStatement->id)
            ->assertJsonPath('data.0.company_name', 'شرکت پورتفو')
            ->assertJsonPath('data.0.period', '1404/12')
            ->assertJsonPath('data.0.net_sales', '1250000');
    }

    public function test_portfolio_statement_can_be_edited_and_deleted_without_losing_large_amounts(): void
    {
        $project = $this->project('شرکت تست', Project::PORTFOLIO_MINIMUM_STEP);
        $statement = $this->statement($project, 1404, 12, '100');

        $activity = $this->mock(ActivityLogService::class);
        $activity->shouldReceive('record')->twice();

        $largeAmount = '۹,۰۰۷,۱۹۹,۲۵۴,۷۴۰,۹۹۳';
        $updateResponse = $this->patchJson('/panel/financialstatement/'.$statement->id, [
            'project_id' => (string) $project->id,
            'year' => '۱۴۰۴',
            'month' => '۱۲',
            'net_sales' => $largeAmount,
            'net_profit' => '-۲۵۰,۰۰۰',
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('financial_statements', [
            'id' => $statement->id,
            'net_sales' => '9007199254740993',
            'net_profit' => '-250000',
        ]);

        $deleteResponse = $this->deleteJson('/panel/financialstatement/'.$statement->id);

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('financial_statements', ['id' => $statement->id]);

        $this->deleteJson('/panel/financialstatement/'.$statement->id)
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_non_portfolio_project_is_rejected_by_validation(): void
    {
        $project = $this->project('طرح پیش از قرارداد', Project::PORTFOLIO_MINIMUM_STEP - 1);

        $response = $this->postJson('/panel/financialstatement', [
            'project_id' => $project->id,
            'year' => 1404,
            'month' => 12,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
        $this->assertDatabaseCount('financial_statements', 0);
    }

    public function test_every_monetary_field_has_a_form_and_detail_label(): void
    {
        $groupedFields = array_merge(...array_values(array_map('array_keys', Financial_statement::fieldGroups())));

        $this->assertSame(Financial_statement::monetaryFields(), $groupedFields);
    }

    private function project(string $companyName, int $investStep): Project
    {
        return Project::withoutEvents(fn () => Project::query()->create([
            'title' => 'طرح '.$companyName,
            'company_name' => $companyName,
            'invest_step' => $investStep,
        ]));
    }

    private function statement(Project $project, int $year, int $month, string $netSales): Financial_statement
    {
        return Financial_statement::query()->create([
            'project_id' => $project->id,
            'year' => $year,
            'month' => $month,
            'net_sales' => $netSales,
        ]);
    }
}
