<?php

namespace Tests\Feature;

use App\Enums\InvestmentRole;
use App\Models\AdministrativeAsset;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\KPI;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\User;
use App\Models\User_logs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PersonalizedDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrative_user_sees_personal_information_and_only_administrative_metrics(): void
    {
        $user = $this->userWithRole(InvestmentRole::AdministrativeSupportManagement, 'admin-office@example.test');
        Employee::query()->create([
            'personnel_code' => 'P-100',
            'first_name' => 'مریم',
            'last_name' => 'احمدی',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        AdministrativeAsset::query()->create([
            'asset_code' => 'A-100',
            'name' => 'رایانه همراه',
            'category' => 'رایانه',
            'quantity' => 3,
            'unit' => 'عدد',
            'purchase_cost' => 100,
            'condition' => 'good',
            'status' => 'in_stock',
            'created_by' => $user->id,
        ]);
        Calendar::query()->create([
            'created_by' => $user->id,
            'title' => 'جلسه برنامه‌ریزی اداری',
            'start' => Jalalian::fromCarbon(now()->addDay())->format('Y-m-d H:i:s'),
            'guests' => [(string) $user->id],
        ]);
        User_logs::query()->create([
            'user_id' => $user->id,
            'action' => 'employee.created',
            'status' => true,
            'description' => 'پرونده پرسنلی آزمایشی ثبت شد.',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('اطلاعات شخصی و جلسات من')
            ->assertSee('جلسه برنامه‌ریزی اداری')
            ->assertSee('آخرین فعالیت‌های انجام‌شده توسط من')
            ->assertSee('گزارش مدیریت اداری و پشتیبانی')
            ->assertSee('آخرین پرونده‌های پرسنلی و اموال')
            ->assertSee('js-domain-search', false)
            ->assertSee('ارزش اموال')
            ->assertSee('300 ریال')
            ->assertDontSee('گزارش مدیریت مالی')
            ->assertDontSee('گزارش مدیریت سرمایه‌گذاری');
    }

    public function test_finance_user_sees_financial_metrics_without_other_department_reports(): void
    {
        $user = $this->userWithRole(InvestmentRole::FinanceManagement, 'finance-dashboard@example.test');
        $project = Project::withoutEvents(fn (): Project => Project::query()->create([
            'title' => 'شرکت مالی نمونه',
            'company_name' => 'شرکت مالی نمونه',
            'invest_step' => Project::PORTFOLIO_MINIMUM_STEP,
            'progress_percentage' => 45,
        ]));
        Finance::query()->create([
            'project_id' => $project->id,
            'amount' => 2500000,
            'date' => Jalalian::fromCarbon(now())->format('Y-m-d'),
        ]);
        Financial_statement::query()->create([
            'project_id' => $project->id,
            'year' => (int) Jalalian::fromCarbon(now())->format('Y'),
            'month' => 6,
            'net_profit' => 500000,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('گزارش مدیریت مالی')
            ->assertSee('آخرین پرداخت‌ها و صورت‌های مالی')
            ->assertSee('2,500,000 ریال')
            ->assertSee('صورت‌های مالی ثبت‌شده')
            ->assertDontSee('گزارش مدیریت اداری و پشتیبانی')
            ->assertDontSee('گزارش مدیریت سرمایه‌گذاری');
    }

    public function test_investment_user_sees_company_and_project_pipeline_metrics_only(): void
    {
        $user = $this->userWithRole(InvestmentRole::InvestmentManagement, 'investment-dashboard@example.test');
        $company = Company::query()->create([
            'title' => 'شرکت متقاضی نمونه',
            'company_name' => 'شرکت متقاضی نمونه',
            'user_id' => $user->id,
        ]);
        Project::withoutEvents(fn (): Project => Project::query()->create([
            'title' => 'طرح سرمایه‌گذاری نمونه',
            'company_name' => $company->company_name,
            'company_id' => $company->id,
            'invest_step' => 5,
            'progress_percentage' => 30,
        ]));

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('گزارش مدیریت سرمایه‌گذاری')
            ->assertSee('آخرین طرح‌های به‌روزشده')
            ->assertSee('شرکت‌های متقاضی')
            ->assertSee('طرح سرمایه‌گذاری نمونه')
            ->assertDontSee('گزارش مدیریت مالی')
            ->assertDontSee('گزارش مدیریت اداری و پشتیبانی');
    }

    public function test_portfolio_user_sees_post_contract_performance_without_finance_dashboard(): void
    {
        $user = $this->userWithRole(InvestmentRole::PortfolioAffairsManagement, 'portfolio-dashboard@example.test');
        $project = Project::withoutEvents(fn (): Project => Project::query()->create([
            'title' => 'شرکت پورتفوی نمونه',
            'company_name' => 'شرکت پورتفوی نمونه',
            'invest_step' => Project::PORTFOLIO_MINIMUM_STEP,
            'progress_percentage' => 65,
        ]));
        KPI::query()->create([
            'project_id' => $project->id,
            'title' => 'رشد فروش',
            'kpi_number' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('گزارش مدیریت امور مجامع و پورتفو')
            ->assertSee('شاخص‌های کلیدی فعال')
            ->assertSee('شرکت پورتفوی نمونه')
            ->assertDontSee('گزارش مدیریت مالی')
            ->assertDontSee('گزارش مدیریت سرمایه‌گذاری');
    }

    public function test_executive_user_sees_only_organization_wide_executive_summary(): void
    {
        $user = $this->userWithRole(InvestmentRole::ExecutiveBoard, 'executive-dashboard@example.test');
        Project::withoutEvents(fn (): Project => Project::query()->create([
            'title' => 'طرح مدیریتی نمونه',
            'company_name' => 'شرکت مدیریتی نمونه',
            'invest_step' => 8,
            'progress_percentage' => 50,
        ]));

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('گزارش مدیریتی مدیرعامل و هیأت‌مدیره')
            ->assertSee('طرح مدیریتی نمونه')
            ->assertDontSee('گزارش مدیریت مالی')
            ->assertDontSee('گزارش مدیریت اداری و پشتیبانی');
    }

    public function test_investment_manager_sees_named_activity_only_for_experts_in_visible_projects(): void
    {
        $manager = $this->userWithRole(InvestmentRole::InvestmentManagement, 'activity-manager@example.test');
        $assignedExpert = $this->userWithRole(InvestmentRole::Expert, 'assigned-expert@example.test');
        $unrelatedExpert = $this->userWithRole(InvestmentRole::Expert, 'unrelated-expert@example.test');
        $project = Project::withoutEvents(fn (): Project => Project::query()->create([
            'title' => 'طرح حوزه مدیر',
            'company_name' => 'شرکت حوزه مدیر',
            'invest_step' => 5,
            'progress_percentage' => 25,
        ]));
        $expertRole = Role::query()->where('title', InvestmentRole::Expert->value)->firstOrFail();

        ProjectAssignment::query()->create([
            'project_id' => $project->id,
            'user_id' => $assignedExpert->id,
            'role_id' => $expertRole->id,
            'invest_step_id' => null,
            'assigned_by' => $manager->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);
        User_logs::query()->create([
            'user_id' => $assignedExpert->id,
            'action' => 'project.updated',
            'status' => true,
            'description' => 'ویرایش مرتبط با حوزه مدیر',
        ]);
        User_logs::query()->create([
            'user_id' => $unrelatedExpert->id,
            'action' => 'project.updated',
            'status' => true,
            'description' => 'ویرایش خارج از حوزه مدیر',
        ]);

        $this->actingAs($manager)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('آخرین فعالیت‌های کارشناسان حوزه من')
            ->assertSee($assignedExpert->name)
            ->assertSee('ویرایش مرتبط با حوزه مدیر')
            ->assertDontSee('ویرایش خارج از حوزه مدیر');
    }

    #[DataProvider('birthdayReminderDates')]
    public function test_admin_sees_employee_birthday_reminder_two_days_before_the_jalali_anniversary(string $today, string $birthDate): void
    {
        $this->travelTo(Jalalian::fromFormat('Y/m/d', $today)->toCarbon()->setTime(23, 59));
        $admin = $this->userWithRole(InvestmentRole::SuperAdmin, 'birthday-admin@example.test');
        $employee = Employee::query()->create([
            'personnel_code' => 'B-100',
            'first_name' => 'مریم',
            'last_name' => 'احمدی',
            'birth_date' => $birthDate,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('birthdayReminders', fn ($employees): bool => $employees->modelKeys() === [$employee->id])
            ->assertSee('۲ روز مانده تا تولد مریم احمدی.')
            ->assertSee('تولد این همکار گرامی را پیشاپیش تبریک می‌گوییم.');
    }

    public static function birthdayReminderDates(): array
    {
        return [
            'ordinary day' => ['1405/07/25', '1380/07/27'],
            'Persian digits' => ['1405/07/25', '۱۳۸۰/۰۷/۲۷'],
            'Arabic digits and hyphens' => ['1405/07/25', '١٣٨٠-٧-٢٧'],
            'compact date' => ['1405/07/25', '13800727'],
            '31-day month boundary' => ['1405/06/30', '1380/07/01'],
            '30-day month boundary' => ['1405/07/29', '1380/08/01'],
            'common year boundary' => ['1404/12/28', '1380/01/01'],
            'leap year boundary' => ['1403/12/29', '1380/01/01'],
            'Esfand 30 in a leap year' => ['1403/12/28', '1399/12/30'],
        ];
    }

    public function test_birthday_reminders_exclude_other_days_invalid_dates_and_archived_employees(): void
    {
        $this->travelTo(Jalalian::fromFormat('Y/m/d', '1403/12/28')->toCarbon());
        $admin = $this->userWithRole(InvestmentRole::SuperAdmin, 'birthday-filter-admin@example.test');
        $employees = collect([
            '1399/12/30',
            '1380/12/28',
            '1380/12/29',
            '1380/01/01',
            '1398/12/30',
            '1380/13/30',
            'تاریخ نامعتبر',
            null,
            '',
            '1399/12/30',
        ])->map(fn ($date, $index) => Employee::query()->create([
            'personnel_code' => 'B-'.$index,
            'first_name' => 'کارمند',
            'last_name' => (string) $index,
            'birth_date' => $date,
        ]));
        $employees->last()->delete();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('birthdayReminders', fn ($reminders): bool => $reminders->modelKeys() === [$employees->first()->id]);
    }

    public function test_non_admin_users_do_not_receive_employee_birthday_reminders(): void
    {
        $this->travelTo(Jalalian::fromFormat('Y/m/d', '1405/07/25')->toCarbon());
        Employee::query()->create([
            'personnel_code' => 'B-100',
            'first_name' => 'مریم',
            'last_name' => 'احمدی',
            'birth_date' => '1380/07/27',
        ]);

        foreach ([InvestmentRole::AdministrativeSupportManagement, InvestmentRole::Manager, InvestmentRole::FinanceManagement, InvestmentRole::Expert] as $role) {
            $user = $this->userWithRole($role, $role->value.'-birthday@example.test');

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertViewHas('birthdayReminders', fn ($reminders): bool => $reminders->isEmpty())
                ->assertDontSee('۲ روز مانده تا تولد مریم احمدی.')
                ->assertDontSee('یادآوری تولد کارکنان');
        }
    }

    public function test_birthday_reminders_include_all_matching_employees_beyond_the_recent_list_limit(): void
    {
        $this->travelTo(Jalalian::fromFormat('Y/m/d', '1405/07/25')->toCarbon());
        $admin = $this->userWithRole(InvestmentRole::SuperAdmin, 'all-birthdays-admin@example.test');
        $birthdayEmployees = collect(['مریم', 'علی'])->map(fn ($name, $index) => Employee::query()->create([
            'personnel_code' => 'B-'.$index,
            'first_name' => $name,
            'last_name' => 'احمدی',
            'birth_date' => '1380/07/27',
        ]));
        foreach (range(1, 16) as $index) {
            Employee::query()->create([
                'personnel_code' => 'OTHER-'.$index,
                'first_name' => 'کارمند',
                'last_name' => (string) $index,
            ]);
        }

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('birthdayReminders', fn ($reminders): bool => $reminders->count() === 2
                && $reminders->contains('id', $birthdayEmployees[0]->id)
                && $reminders->contains('id', $birthdayEmployees[1]->id))
            ->assertSee('۲ روز مانده تا تولد مریم احمدی.')
            ->assertSee('۲ روز مانده تا تولد علی احمدی.');
    }

    private function userWithRole(InvestmentRole $investmentRole, string $email): User
    {
        $role = Role::query()->where('title', $investmentRole->value)->firstOrFail();
        $user = User::query()->create([
            'name' => $investmentRole->label(),
            'email' => $email,
            'password' => 'password',
            'level' => 'admin',
            'status' => 4,
            'change_password' => 1,
        ]);
        $user->roles()->attach($role->id);

        return $user->fresh();
    }
}
