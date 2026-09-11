<?php

namespace Database\Seeders;

use App\Enums\InvestmentRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrganizationalAccessSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $creatorId = Schema::hasTable('users') ? DB::table('users')->min('id') : null;

        foreach (InvestmentRole::organizationalRoles() as $role) {
            DB::table('roles')->updateOrInsert(
                ['title' => $role->value],
                [
                    'title_fa' => $role->label(),
                    'status' => 4,
                    'user_id' => $creatorId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $menuIds = $this->menus($creatorId, $now);
        $submenuIds = $this->submenus($menuIds, $creatorId, $now);

        $permissionLabels = [
            'company' => 'مشاهده شرکت‌های متقاضی',
            'project' => 'مشاهده طرح‌های سرمایه‌گذاری',
            'flow' => 'مدیریت فرایند سرمایه‌گذاری',
            'filemanager' => 'مدیریت مستندات طرح‌ها',
            'finance' => 'مدیریت پرداخت‌های سرمایه‌گذاری',
            'financialstatement' => 'مدیریت صورت‌های مالی پورتفو',
            'report' => 'گزارش‌ها و آمار مدیریتی',
            'employees' => 'مدیریت کارکنان و مدارک پرسنلی',
            'assets' => 'مدیریت کالاها و اموال',
        ];

        $permissionIds = [];
        foreach ($permissionLabels as $slug => $label) {
            $existing = DB::table('permissions')->where('slug', $slug)->first();
            $values = [
                'title' => $slug,
                'label' => $label,
                'updated_at' => $now,
            ];

            if (! $existing) {
                $values += [
                    'menu_panel_id' => in_array($slug, ['employees', 'assets'], true)
                        ? $menuIds['administrative-support']
                        : null,
                    'submenu_panel_id' => $submenuIds['administrative-support:'.$slug] ?? null,
                    'user_id' => $creatorId,
                    'created_at' => $now,
                ];
                $permissionIds[$slug] = DB::table('permissions')->insertGetId(['slug' => $slug, ...$values]);
            } else {
                DB::table('permissions')->where('id', $existing->id)->update($values);
                $permissionIds[$slug] = (int) $existing->id;
            }
        }

        if (! Schema::hasTable('permission_role')) {
            return;
        }

        $matrix = [
            InvestmentRole::ExecutiveBoard->value => [
                'report' => [true, false, false, false],
            ],
            InvestmentRole::FinanceManagement->value => [
                'finance' => [true, true, true, true],
                'financialstatement' => [true, true, true, true],
                'report' => [true, false, false, false],
            ],
            InvestmentRole::InvestmentManagement->value => [
                'company' => [true, false, false, false],
                'project' => [true, false, false, false],
                'flow' => [true, false, true, false],
                'filemanager' => [true, true, true, false],
            ],
            InvestmentRole::PortfolioAffairsManagement->value => [
                'flow' => [true, false, true, false],
                'filemanager' => [true, true, true, true],
                'financialstatement' => [true, false, false, false],
                'report' => [true, false, false, false],
            ],
            InvestmentRole::AdministrativeSupportManagement->value => [
                'employees' => [true, true, true, true],
                'assets' => [true, true, true, true],
            ],
        ];

        foreach ($matrix as $roleSlug => $permissions) {
            $roleId = DB::table('roles')->where('title', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }

            foreach ($permissions as $permissionSlug => [$view, $insert, $edit, $delete]) {
                $permissionId = $permissionIds[$permissionSlug] ?? null;
                if (! $permissionId) {
                    continue;
                }

                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    [
                        'can_view' => $view,
                        'can_insert' => $insert,
                        'can_edit' => $edit,
                        'can_delete' => $delete,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }

        $superAdminId = DB::table('roles')
            ->where('title', InvestmentRole::SuperAdmin->value)
            ->value('id');

        if ($superAdminId) {
            foreach (DB::table('permissions')->pluck('id') as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $superAdminId, 'permission_id' => $permissionId],
                    [
                        'can_view' => true,
                        'can_insert' => true,
                        'can_edit' => true,
                        'can_delete' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    /** @return array<string, int> */
    private function menus(?int $creatorId, mixed $now): array
    {
        if (! Schema::hasTable('menu_panels')) {
            return [];
        }

        $definitions = [
            'directors' => [10, 'مدیرعامل و هیأت مدیره', 'mdi-chart-box-outline'],
            'finance-department' => [11, 'مدیریت مالی', 'mdi-calculator-variant-outline'],
            'investment-department' => [12, 'مدیریت سرمایه‌گذاری', 'mdi-finance'],
            'portfolio-affairs' => [13, 'مدیریت امور مجامع شرکت‌ها', 'mdi-domain'],
            'administrative-support' => [14, 'مدیریت اداری و پشتیبانی', 'mdi-briefcase-account-outline'],
        ];

        $ids = [];
        foreach ($definitions as $slug => [$priority, $label, $icon]) {
            DB::table('menu_panels')->updateOrInsert(
                ['slug' => $slug],
                [
                    'priority' => $priority,
                    'label' => $label,
                    'title' => $slug,
                    'icon' => $icon,
                    'submenu' => true,
                    'class' => 'panel',
                    'level' => 'admin',
                    'controller' => null,
                    'is_public' => false,
                    'status' => 4,
                    'user_id' => $creatorId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $ids[$slug] = (int) DB::table('menu_panels')->where('slug', $slug)->value('id');
        }

        return $ids;
    }

    /** @return array<string, int> */
    private function submenus(array $menuIds, ?int $creatorId, mixed $now): array
    {
        if (! Schema::hasTable('submenu_panels') || $menuIds === []) {
            return [];
        }

        $definitions = [
            'directors' => [
                ['report', 'گزارش‌ها و آمار مدیریتی', 'ReportController'],
            ],
            'finance-department' => [
                ['financialstatement', 'صورت‌های مالی شرکت‌های پورتفو', 'FinancialstatementController'],
                ['finance', 'پرداخت‌های سرمایه‌گذاری', 'FinancialController'],
                ['report', 'آمار و گزارش‌های پورتفو', 'ReportController'],
            ],
            'investment-department' => [
                ['company', 'شرکت‌های متقاضی', 'CompanyController'],
                ['project', 'طرح‌های سرمایه‌گذاری', 'ProjectController'],
                ['flow', 'مراحل ۱ تا عقد قرارداد', 'FlowController'],
                ['filemanager', 'مستندات فرایند سرمایه‌گذاری', 'FilemanagerController'],
            ],
            'portfolio-affairs' => [
                ['flow', 'پایش پس از عقد قرارداد و KPI', 'FlowController'],
                ['filemanager', 'فایل‌ها و گزارش‌های عملکرد', 'FilemanagerController'],
                ['financialstatement', 'صورت‌های مالی پورتفو', 'FinancialstatementController'],
                ['report', 'گزارش عملکرد شرکت‌های پورتفو', 'ReportController'],
            ],
            'administrative-support' => [
                ['employees', 'اطلاعات کارکنان و مدارک پرسنلی', 'EmployeeController'],
                ['assets', 'کالاها و اموال', 'AdministrativeAssetController'],
            ],
        ];

        $ids = [];
        foreach ($definitions as $menuSlug => $items) {
            foreach ($items as $index => [$slug, $label, $controller]) {
                DB::table('submenu_panels')->updateOrInsert(
                    ['menu_id' => $menuIds[$menuSlug], 'slug' => $slug],
                    [
                        'priority' => $index + 1,
                        'title' => $slug,
                        'label' => $label,
                        'level' => 'admin',
                        'class' => 'index',
                        'controller' => $controller,
                        'status' => 4,
                        'user_id' => $creatorId,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
                $ids[$menuSlug.':'.$slug] = (int) DB::table('submenu_panels')
                    ->where('menu_id', $menuIds[$menuSlug])
                    ->where('slug', $slug)
                    ->value('id');
            }
        }

        return $ids;
    }
}
