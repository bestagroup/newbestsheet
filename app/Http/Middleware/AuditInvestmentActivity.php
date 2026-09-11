<?php

namespace App\Http\Middleware;

use App\Enums\InvestmentRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditInvestmentActivity
{
    private const AUDITED_ROUTE_PREFIXES = [
        'quarterly-performance.',
        'finance.',
        'paidmanage.',
        'panel.company.',
        'company.',
        'project.',
        'minute.',
        'flow.',
        'investsteps.',
        'project-stage-forms.',
        'financialstatement.',
        'calendar.',
        'owner.',
        'filemanager.',
    ];

    private const AUDITED_ROUTE_NAMES = [
        'correspondence.store',
        'filestatus',
        'storemedia',
        'deletefile',
    ];

    private const INVESTMENT_SIDE_ROLES = [
        InvestmentRole::SuperAdmin->value,
        InvestmentRole::Manager->value,
        InvestmentRole::SeniorInvestmentExpert->value,
        InvestmentRole::Expert->value,
        InvestmentRole::Observer->value,
        InvestmentRole::Evaluator->value,
        InvestmentRole::ExecutiveBoard->value,
        InvestmentRole::FinanceManagement->value,
        InvestmentRole::InvestmentManagement->value,
        InvestmentRole::PortfolioAffairsManagement->value,
    ];

    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldAudit($request, $response)) {
            return $response;
        }

        /** @var User $user */
        $user = $request->user();
        $routeName = (string) $request->route()?->getName();
        $operation = $this->operation($request, $routeName);
        $subject = $this->subject($routeName);

        $this->activityLog->record(
            $this->actionPrefix($routeName).'.'.$operation,
            sprintf('%s توسط %s %s شد.', $subject, $user->name, $this->operationLabel($operation)),
            (int) $user->getKey(),
            true,
            $request,
        );

        return $response;
    }

    private function shouldAudit(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            || $request->attributes->get(ActivityLogService::REQUEST_LOGGED_ATTRIBUTE, false)
            || ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400)
            || $this->isUnsuccessfulJsonResponse($response)
            || $this->hasValidationOrOperationError($request)) {
            return false;
        }

        $routeName = (string) $request->route()?->getName();
        if (! in_array($routeName, self::AUDITED_ROUTE_NAMES, true)
            && ! collect(self::AUDITED_ROUTE_PREFIXES)->contains(
                static fn (string $prefix): bool => str_starts_with($routeName, $prefix)
            )) {
            return false;
        }

        $user = $request->user();

        return $user instanceof User && $user->hasRole(self::INVESTMENT_SIDE_ROLES);
    }

    private function isUnsuccessfulJsonResponse(Response $response): bool
    {
        if (! $response instanceof JsonResponse) {
            return false;
        }

        $payload = $response->getData(true);

        return is_array($payload) && (
            (array_key_exists('success', $payload) && $payload['success'] === false)
            || ($payload['status'] ?? null) === false
            || ($payload['flag'] ?? null) === 'error'
        );
    }

    private function hasValidationOrOperationError(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        return $session->has('errors') || $session->has('error') || $session->has('danger');
    }

    private function operation(Request $request, string $routeName): string
    {
        return match (true) {
            str_ends_with($routeName, '.review') => 'reviewed',
            str_ends_with($routeName, '.destroy'), $request->isMethod('DELETE') => 'deleted',
            $routeName === 'filestatus' => 'updated',
            str_ends_with($routeName, '.update'), $request->isMethod('PUT'), $request->isMethod('PATCH') => 'updated',
            default => 'created',
        };
    }

    private function operationLabel(string $operation): string
    {
        return match ($operation) {
            'deleted' => 'حذف',
            'updated' => 'ویرایش',
            'reviewed' => 'بررسی',
            default => 'ایجاد',
        };
    }

    private function actionPrefix(string $routeName): string
    {
        return match (true) {
            str_starts_with($routeName, 'flow.kpis.') => 'kpi',
            str_starts_with($routeName, 'flow.commitments.') => 'commitment',
            str_starts_with($routeName, 'flow.contracts.') => 'contract',
            str_starts_with($routeName, 'flow.assignments.') => 'assignment',
            str_starts_with($routeName, 'flow.members.') => 'project_member',
            str_starts_with($routeName, 'flow.stages.comments.') => 'stage_comment',
            str_starts_with($routeName, 'flow.') => 'workflow',
            str_starts_with($routeName, 'financialstatement.') => 'financial_statement',
            str_starts_with($routeName, 'finance.'), str_starts_with($routeName, 'paidmanage.') => 'finance',
            str_starts_with($routeName, 'quarterly-performance.') => 'quarterly_performance',
            str_starts_with($routeName, 'project-stage-forms.') => 'stage_form',
            str_starts_with($routeName, 'investsteps.') => 'investment_step',
            str_starts_with($routeName, 'minute.') => 'minute',
            str_starts_with($routeName, 'calendar.') => 'calendar',
            str_starts_with($routeName, 'correspondence.') => 'correspondence',
            str_contains($routeName, 'company') => 'company',
            str_starts_with($routeName, 'project.') => 'project',
            str_starts_with($routeName, 'owner.') => 'owner',
            in_array($routeName, ['filestatus', 'storemedia', 'deletefile'], true), str_starts_with($routeName, 'filemanager.') => 'project_file',
            default => 'investment_activity',
        };
    }

    private function subject(string $routeName): string
    {
        return match ($this->actionPrefix($routeName)) {
            'kpi' => 'شاخص کلیدی عملکرد',
            'commitment' => 'تعهد پروژه',
            'contract' => 'قرارداد سرمایه‌گذاری',
            'assignment' => 'تخصیص کارشناس',
            'project_member' => 'اطلاعات اعضای تیم طرح',
            'stage_comment' => 'یادداشت مرحله سرمایه‌گذاری',
            'workflow' => 'اطلاعات گردش کار',
            'financial_statement' => 'صورت مالی',
            'finance' => 'اطلاعات پرداخت سرمایه‌گذاری',
            'quarterly_performance' => 'گزارش عملکرد فصلی',
            'stage_form' => 'فرم مرحله سرمایه‌گذاری',
            'investment_step' => 'تنظیمات مرحله سرمایه‌گذاری',
            'minute' => 'صورتجلسه',
            'calendar' => 'رویداد تقویم',
            'correspondence' => 'مکاتبه',
            'company' => 'اطلاعات شرکت',
            'project' => 'اطلاعات طرح',
            'owner' => 'اطلاعات سرمایه‌گذار',
            'project_file' => 'فایل و مستندات طرح',
            default => 'اطلاعات حوزه سرمایه‌گذاری',
        };
    }
}
