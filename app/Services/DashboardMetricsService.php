<?php

namespace App\Services;

use App\Enums\InvestmentRole;
use App\Models\AdministrativeAsset;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\KPI;
use App\Models\KpiMeasurement;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\ProjectCommitment;
use App\Models\QuarterlyPerformanceReport;
use App\Models\User;
use App\Models\User_logs;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Morilog\Jalali\Jalalian;

class DashboardMetricsService
{
    public function __construct(
        private readonly InvestmentReminderService $reminders,
        private readonly OperationalAnalyticsService $operationalAnalytics,
        private readonly ActivityLogVisibilityService $activityLogVisibility,
    ) {}

    public function build(): array
    {
        /** @var User $viewer */
        $viewer = Auth::user();
        $viewer->loadMissing(['roles:id,title,title_fa', 'lastLogin']);

        $visibleProjectIds = $this->operationalAnalytics->visibleProjectIds($viewer);
        $operationalAlerts = $this->reminders->alertsForUser($viewer);

        return [
            'dashboardProfile' => $this->profile($viewer),
            'upcomingMeetings' => $this->upcomingMeetings($viewer),
            'personalCards' => $this->personalCards($viewer, $operationalAlerts),
            'recentActivities' => $this->recentActivities($viewer, $visibleProjectIds),
            'showsTeamActivity' => $this->activityLogVisibility->showsTeamActivity($viewer),
            'myAssignments' => $this->assignments($viewer),
            'operationalAlerts' => $operationalAlerts,
            'domainSections' => $this->domainSections($viewer, $visibleProjectIds),
            'generatedAt' => Jalalian::fromCarbon(now())->format('Y/m/d H:i'),
        ];
    }

    private function profile(User $viewer): array
    {
        $fields = collect([
            $viewer->name,
            $viewer->email,
            $viewer->phone,
            $viewer->national_id,
            $viewer->job_title ?: $viewer->user_job,
            $viewer->image,
        ]);
        $completed = $fields->filter(fn ($value): bool => filled($value))->count();

        return [
            'name' => $viewer->name,
            'email' => $viewer->email,
            'phone' => $viewer->phone,
            'national_id' => $viewer->national_id,
            'job_title' => $viewer->job_title ?: $viewer->user_job,
            'image' => $viewer->image,
            'roles' => $viewer->roles->map(fn ($role): string => $role->title_fa ?: $role->title)->values(),
            'completion' => (int) round(($completed / max(1, $fields->count())) * 100),
            'last_login' => $viewer->lastLogin?->created_at,
        ];
    }

    private function upcomingMeetings(User $viewer): Collection
    {
        $nowJalali = Jalalian::fromCarbon(now())->format('Y-m-d H:i:s');

        return Calendar::query()
            ->where(function ($query) use ($viewer): void {
                $query->where('created_by', $viewer->getKey())
                    ->orWhereJsonContains('guests', (string) $viewer->getKey())
                    ->orWhereJsonContains('guests', (int) $viewer->getKey());
            })
            ->where('start', '>=', $nowJalali)
            ->orderBy('start')
            ->limit(6)
            ->get(['id', 'title', 'label', 'start', 'end', 'location', 'all_day']);
    }

    private function personalCards(User $viewer, Collection $alerts): array
    {
        $workLogs = User_logs::query()
            ->where('user_id', $viewer->getKey())
            ->where('status', true)
            ->whereNotIn('action', ['login', 'logout', 'failed_login']);

        return [
            $this->card(
                'فعالیت امروز من',
                (clone $workLogs)->whereDate('created_at', today())->count(),
                'عملیات ثبت‌شده در سامانه',
                'mdi-check-circle-outline',
                'success'
            ),
            $this->card(
                'عملکرد ۳۰ روز اخیر',
                (clone $workLogs)->where('created_at', '>=', now()->subDays(30))->count(),
                'ایجاد، ویرایش، بررسی و تأیید',
                'mdi-chart-timeline-variant',
                'primary'
            ),
            $this->card(
                'وظایف فعال من',
                ProjectAssignment::query()
                    ->where('user_id', $viewer->getKey())
                    ->where('is_active', true)
                    ->count(),
                'تخصیص‌های فعال فرایند',
                'mdi-clipboard-account-outline',
                'info'
            ),
            $this->card(
                'هشدارهای کاری',
                $alerts->count(),
                'موارد نزدیک سررسید یا معوق',
                'mdi-bell-alert-outline',
                $alerts->isEmpty() ? 'secondary' : 'warning'
            ),
        ];
    }

    private function recentActivities(User $viewer, Collection $visibleProjectIds): Collection
    {
        $query = User_logs::query()
            ->with(['user:id,name', 'user.roles:id,title,title_fa'])
            ->where('status', true)
            ->whereNotIn('action', ['login', 'logout', 'failed_login'])
            ->latest('id');

        return $this->activityLogVisibility
            ->scopeForViewer($query, $viewer, $visibleProjectIds)
            ->limit(8)
            ->get(['id', 'user_id', 'action', 'description', 'subject_type', 'subject_id', 'metadata', 'created_at'])
            ->map(fn (User_logs $log): array => [
                'title' => $this->actionLabel($log->action),
                'description' => $log->description ?: 'عملیات با موفقیت در سامانه ثبت شد.',
                'actor_name' => $log->user?->name ?: 'سیستم',
                'actor_role' => $log->user?->roles
                    ->map(fn ($role): string => $role->title_fa ?: $role->title)
                    ->filter()
                    ->implode('، '),
                'created_at' => $log->created_at,
                'icon' => $this->actionIcon($log->action),
            ]);
    }

    private function assignments(User $viewer): Collection
    {
        return ProjectAssignment::query()
            ->with(['project:id,title,company_name,invest_step,progress_percentage', 'investStep:id,title', 'role:id,title_fa,title'])
            ->where('user_id', $viewer->getKey())
            ->where('is_active', true)
            ->latest('id')
            ->limit(6)
            ->get();
    }

    private function domainSections(User $viewer, Collection $visibleProjectIds): Collection
    {
        $sections = collect();
        $isSuperAdmin = $viewer->hasRole(InvestmentRole::SuperAdmin->value);
        $isManager = $viewer->hasRole(InvestmentRole::Manager->value);
        $hasOrganizationalRole = $viewer->hasRole(InvestmentRole::organizationalRoleSlugs());

        if ($isSuperAdmin || $isManager || $viewer->hasRole(InvestmentRole::ExecutiveBoard->value)) {
            $sections->push($this->executiveSection($viewer, $visibleProjectIds));
        }

        if ($isSuperAdmin
            || $viewer->hasRole(InvestmentRole::FinanceManagement->value)
            || (! $hasOrganizationalRole && $this->canAny($viewer, ['finance', 'financialstatement']))) {
            $sections->push($this->financeSection($viewer, $visibleProjectIds));
        }

        if ($isSuperAdmin
            || $isManager
            || $viewer->hasRole(InvestmentRole::InvestmentManagement->value)
            || (! $hasOrganizationalRole && $this->canAny($viewer, ['company', 'project', 'flow']))) {
            $sections->push($this->investmentSection($viewer, $visibleProjectIds));
        }

        if ($isSuperAdmin || $isManager || $viewer->hasRole(InvestmentRole::PortfolioAffairsManagement->value)) {
            $sections->push($this->portfolioSection($viewer, $visibleProjectIds));
        }

        if ($isSuperAdmin
            || $viewer->hasRole(InvestmentRole::AdministrativeSupportManagement->value)
            || (! $hasOrganizationalRole && $this->canAny($viewer, ['employees', 'assets']))) {
            $sections->push($this->administrativeSection($viewer));
        }

        if ($sections->isEmpty() && $this->can($viewer, 'report')) {
            $sections->push($this->executiveSection($viewer, $visibleProjectIds));
        }

        return $sections->filter()->values();
    }

    private function administrativeSection(User $viewer): array
    {
        $cards = collect();
        $items = collect();
        $canEmployees = $this->can($viewer, 'employees');
        $canAssets = $this->can($viewer, 'assets');

        if ($canEmployees) {
            $cards->push($this->card('کل پرونده‌های پرسنلی', Employee::query()->count(), 'تمام کارکنان ثبت‌شده', 'mdi-account-group-outline', 'primary', route('employees.index')));
            $cards->push($this->card('کارکنان فعال', Employee::query()->where('status', 'active')->count(), 'در حال همکاری', 'mdi-account-check-outline', 'success', route('employees.index')));
            $cards->push($this->card('مدارک پرسنلی', EmployeeDocument::query()->count(), 'اسناد بارگذاری‌شده', 'mdi-file-account-outline', 'info', route('employees.index')));

            $items = $items->merge(Employee::query()->latest('id')->limit(4)->get()->map(fn (Employee $employee): array => [
                'title' => $employee->full_name,
                'subtitle' => collect([$employee->job_title, $employee->department])->filter()->implode(' — ') ?: 'پرونده پرسنلی',
                'meta' => 'کد پرسنلی: '.$employee->personnel_code,
                'created_at' => $employee->created_at,
                'url' => route('employees.index', ['q' => $employee->personnel_code]),
            ]));
        }

        if ($canAssets) {
            $assetValue = (float) (AdministrativeAsset::query()
                ->selectRaw('COALESCE(SUM(purchase_cost * quantity), 0) as total')
                ->value('total') ?? 0);
            $cards->push($this->card('اقلام و اموال', AdministrativeAsset::query()->sum('quantity'), 'تعداد کل اقلام ثبت‌شده', 'mdi-package-variant-closed', 'warning', route('assets.index')));
            $cards->push($this->card('ارزش اموال', $this->money($assetValue), 'ارزش خرید اقلام فعال', 'mdi-cash-multiple', 'success', route('assets.index')));
            $cards->push($this->card('اقلام تحویل‌شده', AdministrativeAsset::query()->where('status', 'assigned')->sum('quantity'), 'تحویل کارکنان', 'mdi-account-arrow-left-outline', 'info', route('assets.index')));

            $items = $items->merge(AdministrativeAsset::query()->latest('id')->limit(4)->get()->map(fn (AdministrativeAsset $asset): array => [
                'title' => $asset->name,
                'subtitle' => $asset->category ?: 'کالا/مال اداری',
                'meta' => 'کد مال: '.$asset->asset_code.' — تعداد: '.number_format($asset->quantity),
                'created_at' => $asset->created_at,
                'url' => route('assets.index', ['q' => $asset->asset_code]),
            ]));
        }

        if ($canAssets) {
            $chartRows = AdministrativeAsset::query()
                ->selectRaw("COALESCE(NULLIF(category, ''), 'بدون دسته‌بندی') as label, SUM(quantity) as aggregate")
                ->groupBy('category')
                ->orderByDesc('aggregate')
                ->limit(8)
                ->get();
            $chart = $this->chart('doughnut', $chartRows->pluck('label'), $chartRows->pluck('aggregate'));
        } else {
            $employeeStatuses = Employee::query()->select('status', DB::raw('COUNT(*) as aggregate'))->groupBy('status')->get();
            $labels = Employee::statusLabels();
            $chart = $this->chart(
                'doughnut',
                $employeeStatuses->map(fn ($row): string => $labels[$row->status] ?? $row->status),
                $employeeStatuses->pluck('aggregate')
            );
        }

        return $this->section(
            'administrative',
            'گزارش مدیریت اداری و پشتیبانی',
            'وضعیت کارکنان، مدارک پرسنلی و دفتر اموال شرکت',
            'mdi-briefcase-account-outline',
            'primary',
            $cards,
            $items->sortByDesc('created_at')->take(6)->values(),
            $chart
        );
    }

    private function financeSection(User $viewer, Collection $visibleProjectIds): array
    {
        $cards = collect();
        $items = collect();
        $canPayments = $this->can($viewer, 'finance');
        $canStatements = $this->can($viewer, 'financialstatement');

        if ($canPayments) {
            $paymentQuery = Finance::query()->whereIn('project_id', $visibleProjectIds)->where('amount', '>', 0);
            $cards->push($this->card('مجموع پرداخت سرمایه‌گذاری', $this->money((float) (clone $paymentQuery)->sum('amount')), 'پرداخت‌های ثبت‌شده برای پورتفو', 'mdi-cash-check', 'success', route('finance.index')));
            $cards->push($this->card('تعداد پرداخت‌ها', (clone $paymentQuery)->count(), 'اسناد پرداخت سرمایه‌گذاری', 'mdi-receipt-text-outline', 'info', route('finance.index')));

            $items = $items->merge((clone $paymentQuery)->with('project:id,title')->latest('id')->limit(6)->get()->map(fn (Finance $finance): array => [
                'title' => $finance->project?->title ?: 'پرداخت سرمایه‌گذاری',
                'subtitle' => $this->money((float) $finance->amount),
                'meta' => $finance->date ?: 'تاریخ ثبت نشده',
                'created_at' => $finance->created_at,
                'url' => route('finance.index'),
            ]));
        }

        if ($canStatements) {
            $statementQuery = Financial_statement::query()->whereIn('project_id', $visibleProjectIds);
            $cards->push($this->card('صورت‌های مالی ثبت‌شده', (clone $statementQuery)->count(), 'دوره‌های مالی شرکت‌های پورتفو', 'mdi-file-chart-outline', 'primary', route('financialstatement.index')));
            $cards->push($this->card('شرکت‌های دارای صورت مالی', (clone $statementQuery)->distinct()->count('project_id'), 'پوشش اطلاعات مالی پورتفو', 'mdi-domain', 'warning', route('financialstatement.index')));

            if ($items->isEmpty()) {
                $items = (clone $statementQuery)->with('project:id,title')->orderByDesc('year')->orderByDesc('month')->limit(6)->get()->map(fn (Financial_statement $statement): array => [
                    'title' => $statement->project?->title ?: 'صورت مالی',
                    'subtitle' => 'دوره '.$statement->year.'/'.str_pad((string) $statement->month, 2, '0', STR_PAD_LEFT),
                    'meta' => 'سود خالص: '.$this->money((float) $statement->net_profit),
                    'created_at' => $statement->created_at,
                    'url' => route('financialstatement.index'),
                ]);
            }
        }

        $monthly = array_fill(1, 12, 0.0);
        if ($canPayments) {
            $currentYear = (int) Jalalian::fromCarbon(now())->format('Y');
            Finance::query()->whereIn('project_id', $visibleProjectIds)->where('amount', '>', 0)->get(['amount', 'date'])->each(function (Finance $finance) use (&$monthly, $currentYear): void {
                [$year, $month] = $this->jalaliYearMonth($finance->date);
                if ($year === $currentYear && $month >= 1 && $month <= 12) {
                    $monthly[$month] += (float) $finance->amount;
                }
            });
        }

        return $this->section(
            'finance',
            'گزارش مدیریت مالی',
            'نمای لحظه‌ای پرداخت‌ها و پوشش صورت‌های مالی شرکت‌های پورتفو',
            'mdi-calculator-variant-outline',
            'success',
            $cards,
            $items->sortByDesc('created_at')->take(6)->values(),
            $this->chart('bar', $this->monthLabels(), array_values($monthly))
        );
    }

    private function investmentSection(User $viewer, Collection $visibleProjectIds): array
    {
        $projects = Project::query()->whereIn('id', $visibleProjectIds);
        $canCompanies = $this->can($viewer, 'company');
        $companyCount = $canCompanies
            ? Company::query()->count()
            : (clone $projects)->whereNotNull('company_id')->distinct()->count('company_id');

        $cards = collect([
            $this->card('شرکت‌های متقاضی', $companyCount, 'شرکت‌های ثبت‌شده در سامانه', 'mdi-domain-plus', 'info', $canCompanies ? route('panel.company.index') : null),
            $this->card('طرح‌های قابل بررسی', (clone $projects)->count(), 'متناسب با حوزه و تخصیص شما', 'mdi-lightbulb-on-outline', 'primary', $this->can($viewer, 'project') ? route('project.index') : null),
            $this->card('طرح‌های فعال', (clone $projects)->where(fn ($query) => $query->whereNull('is_rejected')->orWhere('is_rejected', 0))->count(), 'در جریان ارزیابی و تصمیم‌گیری', 'mdi-progress-check', 'success', $this->can($viewer, 'flow') ? route('flow.index') : null),
            $this->card('مرحله عقد قرارداد', (clone $projects)->where('invest_step', 13)->count(), 'طرح‌های رسیده به مرحله قرارداد', 'mdi-file-sign', 'warning', $this->can($viewer, 'flow') ? route('flow.index') : null),
            $this->card('طرح‌های ردشده', (clone $projects)->where('is_rejected', 1)->count(), 'خارج‌شده از فرایند', 'mdi-close-octagon-outline', 'danger', $this->can($viewer, 'project') ? route('project.index') : null),
        ]);

        $stageRows = Project::query()
            ->whereIn('projects.id', $visibleProjectIds)
            ->leftJoin('investsteps', 'projects.invest_step', '=', 'investsteps.id')
            ->selectRaw("COALESCE(investsteps.title, 'مرحله نامشخص') as label, COUNT(projects.id) as aggregate")
            ->groupBy('investsteps.id', 'investsteps.title')
            ->orderBy('investsteps.id')
            ->get();
        $items = Project::query()
            ->with('currentStep:id,title')
            ->whereIn('id', $visibleProjectIds)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Project $project): array => [
                'title' => $project->title,
                'subtitle' => $project->company_name ?: 'طرح سرمایه‌گذاری',
                'meta' => ($project->currentStep?->title ?: 'مرحله نامشخص').' — پیشرفت '.number_format((float) $project->progress_percentage, 0).'٪',
                'created_at' => $project->updated_at,
                'url' => $this->can($viewer, 'flow') ? route('flow.show', $project) : route('project.index'),
            ]);

        return $this->section(
            'investment',
            'گزارش مدیریت سرمایه‌گذاری',
            'تعداد شرکت‌ها، طرح‌ها و توزیع پرونده‌ها در مراحل پیش از قرارداد',
            'mdi-finance',
            'info',
            $cards,
            $items,
            $this->chart('bar', $stageRows->pluck('label'), $stageRows->pluck('aggregate'))
        );
    }

    private function portfolioSection(User $viewer, Collection $visibleProjectIds): array
    {
        $projectCount = Project::query()->whereIn('id', $visibleProjectIds)->count();
        $kpiQuery = KPI::query()->current()->whereIn('project_id', $visibleProjectIds);
        $reportQuery = QuarterlyPerformanceReport::query()->whereIn('project_id', $visibleProjectIds)->where('is_current', true);
        $commitmentQuery = ProjectCommitment::query()->whereIn('project_id', $visibleProjectIds);

        $cards = collect([
            $this->card('شرکت‌های پورتفو', $projectCount, 'پرونده‌های پس از عقد قرارداد', 'mdi-domain', 'primary', $this->can($viewer, 'flow') ? route('flow.index') : null),
            $this->card('شاخص‌های کلیدی فعال', (clone $kpiQuery)->whereNull('completed_at')->count(), 'KPIهای در حال پایش', 'mdi-target', 'success', $this->can($viewer, 'flow') ? route('flow.index') : null),
            $this->card('گزارش‌های فصلی', (clone $reportQuery)->count(), 'نسخه‌های جاری عملکرد پورتفو', 'mdi-chart-box-outline', 'info', $this->can($viewer, 'report') ? route('report.index') : null),
            $this->card('تعهدات باز', (clone $commitmentQuery)->where('status', 'pending')->whereNull('completed_at')->count(), 'تعهدات نیازمند پیگیری', 'mdi-clipboard-clock-outline', 'warning', $this->can($viewer, 'flow') ? route('flow.index') : null),
            $this->card('تعهدات معوق', (clone $commitmentQuery)->where('status', 'pending')->whereNull('completed_at')->whereDate('due_at', '<', today())->count(), 'عبورکرده از سررسید', 'mdi-alert-octagon-outline', 'danger', $this->can($viewer, 'flow') ? route('flow.index') : null),
            $this->card('میانگین تحقق KPI', number_format((float) KpiMeasurement::query()->whereHas('kpi', fn ($query) => $query->current()->whereIn('project_id', $visibleProjectIds))->avg('achievement_percentage'), 1).'٪', 'براساس اندازه‌گیری‌های ثبت‌شده', 'mdi-chart-areaspline', 'success'),
        ]);

        $statusRows = (clone $kpiQuery)
            ->selectRaw("COALESCE(NULLIF(status, ''), 'نامشخص') as label, COUNT(*) as aggregate")
            ->groupBy('status')
            ->get();
        $items = Project::query()
            ->withCount(['currentKpis as kpis_count', 'quarterlyPerformanceReports'])
            ->whereIn('id', $visibleProjectIds)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Project $project): array => [
                'title' => $project->title,
                'subtitle' => $project->company_name ?: 'شرکت پورتفو',
                'meta' => number_format($project->kpis_count).' KPI — '.number_format($project->quarterly_performance_reports_count).' گزارش فصلی',
                'created_at' => $project->updated_at,
                'url' => $this->can($viewer, 'flow') ? route('flow.show', $project) : null,
            ]);

        return $this->section(
            'portfolio',
            'گزارش مدیریت امور مجامع و پورتفو',
            'عملکرد پس از قرارداد، KPIها، گزارش‌های فصلی و تعهدات شرکت‌ها',
            'mdi-chart-timeline-variant-shimmer',
            'warning',
            $cards,
            $items,
            $this->chart('doughnut', $statusRows->pluck('label')->map(fn (string $status): string => $this->kpiStatusLabel($status)), $statusRows->pluck('aggregate'))
        );
    }

    private function executiveSection(User $viewer, Collection $visibleProjectIds): array
    {
        $projects = Project::query()->whereIn('id', $visibleProjectIds);
        $cards = collect([
            $this->card('کل طرح‌ها', (clone $projects)->count(), 'تمام پرونده‌های قابل مشاهده', 'mdi-lightbulb-group-outline', 'primary', $this->can($viewer, 'report') ? route('report.index') : null),
            $this->card('شرکت‌های پورتفو', (clone $projects)->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)->count(), 'پرونده‌های پس از قرارداد', 'mdi-domain', 'success', $this->can($viewer, 'report') ? route('report.index') : null),
            $this->card('طرح‌های فعال', (clone $projects)->where(fn ($query) => $query->whereNull('is_rejected')->orWhere('is_rejected', 0))->where('progress_percentage', '<', 100)->count(), 'در حال پیشروی در فرایند', 'mdi-progress-check', 'info', $this->can($viewer, 'report') ? route('report.index') : null),
            $this->card('طرح‌های تکمیل‌شده', (clone $projects)->where(fn ($query) => $query->whereNull('is_rejected')->orWhere('is_rejected', 0))->where('progress_percentage', '>=', 100)->count(), 'فرایندهای تکمیل‌شده', 'mdi-check-decagram-outline', 'success', $this->can($viewer, 'report') ? route('report.index') : null),
            $this->card('طرح‌های ردشده', (clone $projects)->where('is_rejected', 1)->count(), 'خارج‌شده از فرایند', 'mdi-close-octagon-outline', 'danger', $this->can($viewer, 'report') ? route('report.index') : null),
            $this->card('کل پرداخت سرمایه‌گذاری', $this->money((float) Finance::query()->whereIn('project_id', $visibleProjectIds)->sum('amount')), 'پرداخت ثبت‌شده برای طرح‌ها', 'mdi-cash-multiple', 'warning', $this->can($viewer, 'report') ? route('report.index') : null),
        ]);

        $stageRows = Project::query()
            ->whereIn('projects.id', $visibleProjectIds)
            ->leftJoin('investsteps', 'projects.invest_step', '=', 'investsteps.id')
            ->selectRaw("COALESCE(investsteps.title, 'مرحله نامشخص') as label, COUNT(projects.id) as aggregate")
            ->groupBy('investsteps.id', 'investsteps.title')
            ->orderBy('investsteps.id')
            ->get();
        $items = Project::query()
            ->with('currentStep:id,title')
            ->whereIn('id', $visibleProjectIds)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Project $project): array => [
                'title' => $project->title,
                'subtitle' => $project->company_name ?: 'طرح سرمایه‌گذاری',
                'meta' => ($project->currentStep?->title ?: 'مرحله نامشخص').' — پیشرفت '.number_format((float) $project->progress_percentage, 0).'٪',
                'created_at' => $project->updated_at,
                'url' => $this->can($viewer, 'report') ? route('report.index') : null,
            ]);

        return $this->section(
            'executive',
            'گزارش مدیریتی مدیرعامل و هیأت‌مدیره',
            'نمای کلان عملکرد فرایند سرمایه‌گذاری، پورتفو و وضعیت مالی',
            'mdi-view-dashboard-outline',
            'primary',
            $cards,
            $items,
            $this->chart('bar', $stageRows->pluck('label'), $stageRows->pluck('aggregate'))
        );
    }

    private function card(string $label, string|int|float $value, string $hint, string $icon, string $tone, ?string $url = null): array
    {
        return compact('label', 'value', 'hint', 'icon', 'tone', 'url');
    }

    private function section(string $key, string $title, string $description, string $icon, string $tone, Collection $cards, Collection $items, array $chart): array
    {
        return compact('key', 'title', 'description', 'icon', 'tone', 'cards', 'items', 'chart');
    }

    private function chart(string $type, Collection|array $labels, Collection|array $data): array
    {
        return [
            'type' => $type,
            'labels' => collect($labels)->values(),
            'data' => collect($data)->map(fn ($value): float => (float) $value)->values(),
        ];
    }

    private function can(User $viewer, string $slug, string $action = 'view'): bool
    {
        return Gate::forUser($viewer)->allows('can-access', [$slug, $action]);
    }

    private function canAny(User $viewer, array $slugs): bool
    {
        return collect($slugs)->contains(fn (string $slug): bool => $this->can($viewer, $slug));
    }

    private function money(float $value): string
    {
        return number_format($value, 0).' ریال';
    }

    private function actionLabel(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'employee.') => 'عملیات پرونده پرسنلی',
            str_starts_with($action, 'administrative_asset.') => 'عملیات کالا و اموال',
            str_starts_with($action, 'finance.') => 'عملیات پرداخت مالی',
            str_starts_with($action, 'financial_statement.') => 'عملیات صورت مالی',
            str_starts_with($action, 'calendar.') => 'عملیات جلسه و تقویم',
            str_starts_with($action, 'workflow.') => 'تصمیم فرایند سرمایه‌گذاری',
            str_starts_with($action, 'assignment.') => 'تخصیص وظیفه فرایند',
            str_starts_with($action, 'kpi.') => 'عملیات شاخص کلیدی عملکرد',
            str_starts_with($action, 'commitment.') => 'عملیات تعهدات پروژه',
            str_starts_with($action, 'quarterly_performance.') => 'عملیات گزارش عملکرد فصلی',
            str_starts_with($action, 'contract.') => 'عملیات قرارداد سرمایه‌گذاری',
            str_starts_with($action, 'minute.') => 'عملیات صورتجلسه',
            str_starts_with($action, 'correspondence.') => 'عملیات مکاتبات',
            str_starts_with($action, 'project.') => 'عملیات طرح سرمایه‌گذاری',
            str_starts_with($action, 'company.') => 'عملیات اطلاعات شرکت',
            str_starts_with($action, 'investment_step.'), str_starts_with($action, 'stage_form.') => 'عملیات مرحله سرمایه‌گذاری',
            str_starts_with($action, 'project_member.') => 'عملیات اعضای تیم طرح',
            str_starts_with($action, 'stage_comment.') => 'ثبت یادداشت مرحله',
            str_starts_with($action, 'project_file.') => 'عملیات فایل و مستندات طرح',
            str_starts_with($action, 'investment_activity.') => 'فعالیت حوزه سرمایه‌گذاری',
            default => 'فعالیت سامانه',
        };
    }

    private function actionIcon(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'employee.') => 'mdi-account-edit-outline',
            str_starts_with($action, 'administrative_asset.') => 'mdi-package-variant',
            str_starts_with($action, 'finance.'), str_starts_with($action, 'financial_statement.') => 'mdi-cash-check',
            str_starts_with($action, 'calendar.') => 'mdi-calendar-check-outline',
            str_starts_with($action, 'kpi.'), str_starts_with($action, 'commitment.') => 'mdi-target',
            str_starts_with($action, 'workflow.'), str_starts_with($action, 'assignment.') => 'mdi-source-branch-check',
            default => 'mdi-check-circle-outline',
        };
    }

    private function kpiStatusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'فعال',
            'completed' => 'تکمیل‌شده',
            'paused' => 'متوقف',
            'cancelled' => 'لغوشده',
            default => $status,
        };
    }

    private function monthLabels(): array
    {
        return ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    }

    private function jalaliYearMonth(?string $date): array
    {
        if (! $date) {
            return [0, 0];
        }

        $date = trim($date);
        if (preg_match('/^(1[34]\d{2})[\/\-]?(\d{1,2})/', $date, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        if (preg_match('/^(20\d{2})-(\d{2})-(\d{2})/', $date, $matches)) {
            try {
                $jalali = Jalalian::fromCarbon(Carbon::create((int) $matches[1], (int) $matches[2], (int) $matches[3])->startOfDay());

                return [(int) $jalali->format('Y'), (int) $jalali->format('m')];
            } catch (\Throwable) {
                return [0, 0];
            }
        }

        return [0, 0];
    }
}
