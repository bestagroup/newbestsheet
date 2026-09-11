<?php

namespace Tests\Feature;

use App\Enums\InvestmentRole;
use App\Http\Middleware\AuditInvestmentActivity;
use App\Models\Role;
use App\Models\User;
use App\Models\User_logs;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class InvestmentActivityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_investment_mutation_is_logged_with_actor_and_request_context(): void
    {
        $expert = $this->userWithRole(InvestmentRole::Expert, 'audit-expert@example.test', 'کارشناس آزمون');
        $request = $this->investmentRequest($expert, 'project.store', 'POST');

        app(AuditInvestmentActivity::class)->handle(
            $request,
            static fn () => response()->json(['ok' => true], 201)
        );

        $log = User_logs::query()->sole();

        $this->assertSame($expert->id, $log->user_id);
        $this->assertSame('project.created', $log->action);
        $this->assertStringContainsString('کارشناس آزمون', $log->description);
        $this->assertSame('project.store', $log->metadata['route_name']);
        $this->assertSame('POST', $log->metadata['request_method']);
        $this->assertContains(InvestmentRole::Expert->value, $log->metadata['actor_roles']);
    }

    public function test_existing_detailed_log_prevents_duplicate_fallback_log(): void
    {
        $expert = $this->userWithRole(InvestmentRole::Expert, 'audit-deduplicate@example.test', 'کارشناس دقیق');
        $request = $this->investmentRequest($expert, 'flow.kpis.update', 'PATCH');

        app(AuditInvestmentActivity::class)->handle($request, function () use ($expert, $request) {
            app(ActivityLogService::class)->record(
                'kpi.updated',
                'شاخص با جزئیات ویرایش شد.',
                $expert->id,
                true,
                $request,
            );

            return response()->json(['ok' => true]);
        });

        $this->assertDatabaseCount('user_logs', 1);
        $this->assertDatabaseHas('user_logs', ['action' => 'kpi.updated']);
    }

    public function test_failed_mutation_and_investee_actions_do_not_create_investment_staff_audit(): void
    {
        $expert = $this->userWithRole(InvestmentRole::Expert, 'audit-failed@example.test', 'کارشناس خطادار');
        $failedRequest = $this->investmentRequest($expert, 'project.update', 'PATCH');

        app(AuditInvestmentActivity::class)->handle(
            $failedRequest,
            static fn () => response()->json(['message' => 'invalid'], 422)
        );

        $investee = $this->userWithRole(InvestmentRole::InvesteeRepresentative, 'audit-investee@example.test', 'نماینده شرکت');
        $investeeRequest = $this->investmentRequest($investee, 'project.store', 'POST');

        app(AuditInvestmentActivity::class)->handle(
            $investeeRequest,
            static fn () => response()->json(['ok' => true], 201)
        );

        $this->assertDatabaseCount('user_logs', 0);
    }

    public function test_legacy_json_error_with_successful_http_status_is_not_logged(): void
    {
        $expert = $this->userWithRole(InvestmentRole::Expert, 'audit-json-error@example.test', 'کارشناس خطای قدیمی');
        $request = $this->investmentRequest($expert, 'filestatus', 'POST');

        app(AuditInvestmentActivity::class)->handle(
            $request,
            static fn () => response()->json(['success' => false, 'flag' => 'error'])
        );

        $this->assertDatabaseCount('user_logs', 0);
    }

    private function investmentRequest(User $user, string $routeName, string $method): Request
    {
        $request = Request::create('/panel/audit-test', $method);
        $route = new Route([$method], '/panel/audit-test', static fn () => null);
        $route->name($routeName);
        $route->bind($request);
        $request->setRouteResolver(static fn () => $route);
        $request->setUserResolver(static fn () => $user);

        return $request;
    }

    private function userWithRole(InvestmentRole $investmentRole, string $email, string $name): User
    {
        $role = Role::query()->where('title', $investmentRole->value)->firstOrFail();
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'level' => 'admin',
            'status' => 4,
            'change_password' => 1,
        ]);
        $user->roles()->attach($role->id);

        return $user->fresh('roles');
    }
}
