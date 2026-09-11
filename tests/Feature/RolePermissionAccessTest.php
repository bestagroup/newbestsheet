<?php

namespace Tests\Feature;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Panel\RoleuserController;
use App\Http\ViewComposers\MenuComposer;
use App\Models\MenuPanel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\InvestmentRoleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Mockery;
use Tests\TestCase;

class RolePermissionAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['permission_role', 'role_user', 'permissions', 'submenu_panels', 'menu_panels', 'roles', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('level')->nullable();
            $table->integer('status')->default(4);
            $table->integer('change_password')->nullable()->default(1);
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->unique();
            $table->string('title_fa')->nullable();
            $table->integer('status')->default(4);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->unique(['role_id', 'user_id']);
        });
        Schema::create('menu_panels', function (Blueprint $table): void {
            $table->id();
            $table->integer('priority')->default(1);
            $table->string('label');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->boolean('submenu')->default(true);
            $table->string('class')->nullable();
            $table->string('level')->nullable();
            $table->string('controller')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('status')->default(4);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('submenu_panels', function (Blueprint $table): void {
            $table->id();
            $table->integer('priority')->default(1);
            $table->string('label');
            $table->string('title');
            $table->string('slug');
            $table->integer('status')->default(4);
            $table->string('class')->nullable();
            $table->string('controller')->nullable();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('label');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('menu_panel_id')->nullable();
            $table->unsignedBigInteger('submenu_panel_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('permission_role', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_insert')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function test_gate_uses_exact_role_actions_and_superadmin_always_bypasses_them(): void
    {
        $employees = $this->permission('employees', 'کارکنان');
        $assets = $this->permission('assets', 'اموال');
        $regularRole = $this->role('custom_role');
        $regularRole->permissions()->attach($employees->id, [
            'can_view' => true,
            'can_insert' => false,
            'can_edit' => true,
            'can_delete' => false,
        ]);
        $regular = $this->userWithRole($regularRole, 'regular@example.test');

        $this->assertTrue(Gate::forUser($regular)->allows('can-access', ['employees', 'view']));
        $this->assertTrue(Gate::forUser($regular)->allows('can-access', ['employees', 'edit']));
        $this->assertFalse(Gate::forUser($regular)->allows('can-access', ['employees', 'insert']));
        $this->assertFalse(Gate::forUser($regular)->allows('can-access', ['assets', 'view']));

        $superAdmin = $this->userWithRole($this->role(InvestmentRole::SuperAdmin->value), 'root@example.test');
        $this->assertTrue(Gate::forUser($superAdmin)->allows('can-access', [$assets->slug, 'delete']));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('can-access', ['future-page', 'edit']));
    }

    public function test_menu_is_built_from_view_permissions_and_superadmin_sees_every_department(): void
    {
        $adminMenu = $this->menu('administrative-support', 14);
        $employeeSubmenuId = $this->submenu($adminMenu, 'employees', 1);
        $this->submenu($adminMenu, 'assets', 2);

        $employees = $this->permission('employees', 'کارکنان', $adminMenu->id, $employeeSubmenuId);
        $this->permission('assets', 'اموال', $adminMenu->id);

        $regularRole = $this->role('custom_role');
        $regularRole->permissions()->attach($employees->id, ['can_view' => true]);
        $regularMenus = $this->composedMenus($this->userWithRole($regularRole, 'menu@example.test'));
        $regularAdminMenu = $regularMenus->firstWhere('slug', 'administrative-support');

        $this->assertNotNull($regularAdminMenu);
        $this->assertSame(['employees'], $regularAdminMenu->accessible_submenus->pluck('slug')->values()->all());

        $superAdmin = $this->userWithRole($this->role(InvestmentRole::SuperAdmin->value), 'super-menu@example.test');
        $superAdminMenu = $this->composedMenus($superAdmin)->firstWhere('slug', 'administrative-support');
        $this->assertSame(['employees', 'assets'], $superAdminMenu->accessible_submenus->pluck('slug')->values()->all());
    }

    public function test_role_screen_lists_all_permissions_and_new_assignment_gets_view_access(): void
    {
        $menuPermission = $this->permission('dashboard-manage', 'مدیریت داشبورد');
        $employees = $this->permission('employees', 'کارکنان');
        $role = $this->role('new_role');
        $controller = app(RoleuserController::class);

        $view = $controller->index(Request::create('/panel/roleuser', 'GET'));
        $this->assertEqualsCanonicalizing(
            [$menuPermission->id, $employees->id],
            $view->getData()['permissions']->pluck('id')->all()
        );

        $response = $controller->update(Request::create('/panel/roleuser/'.$role->id, 'PATCH', [
            'title_fa' => 'نقش جدید',
            'title' => $role->title,
            'status' => 4,
            'permission_id' => [$employees->id],
        ]), $role->id, app(InvestmentRoleService::class));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $employees->id,
            'can_view' => true,
            'can_insert' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
    }

    public function test_resource_routes_enforce_the_action_flags_while_superadmin_bypasses_them(): void
    {
        Route::get('/_permission-probe/employees', [PermissionProbeController::class, 'index'])
            ->middleware('resource.permission:employees');
        Route::post('/_permission-probe/employees', [PermissionProbeController::class, 'store'])
            ->middleware('resource.permission:employees');
        Route::delete('/_permission-probe/assets', [PermissionProbeController::class, 'destroy'])
            ->middleware('resource.permission:assets');

        $employees = $this->permission('employees', 'کارکنان');
        $regularRole = $this->role('viewer_role');
        $regularRole->permissions()->attach($employees->id, ['can_view' => true]);
        $regular = $this->userWithRole($regularRole, 'route-viewer@example.test');

        $this->actingAs($regular)->get('/_permission-probe/employees')->assertOk();
        $this->actingAs($regular)->post('/_permission-probe/employees')->assertForbidden();

        $superAdmin = $this->userWithRole($this->role(InvestmentRole::SuperAdmin->value), 'route-root@example.test');
        $this->actingAs($superAdmin)->delete('/_permission-probe/assets')->assertOk();
    }

    private function composedMenus(User $user): Collection
    {
        $this->actingAs($user);
        $captured = null;
        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')
            ->once()
            ->with('menupanels', Mockery::type(Collection::class))
            ->andReturnUsing(function (string $key, Collection $menus) use (&$captured, $view) {
                $captured = $menus;

                return $view;
            });

        app(MenuComposer::class)->compose($view);

        return $captured;
    }

    private function role(string $slug): Role
    {
        return Role::query()->create(['title' => $slug, 'title_fa' => $slug, 'status' => 4]);
    }

    private function userWithRole(Role $role, string $email): User
    {
        $user = User::query()->create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'status' => 4,
            'change_password' => 1,
        ]);
        $user->roles()->attach($role->id);

        return $user->fresh();
    }

    private function permission(
        string $slug,
        string $label,
        ?int $menuId = null,
        ?int $submenuId = null
    ): Permission {
        return Permission::query()->create([
            'title' => $slug,
            'label' => $label,
            'slug' => $slug,
            'menu_panel_id' => $menuId,
            'submenu_panel_id' => $submenuId,
        ]);
    }

    private function menu(string $slug, int $priority): MenuPanel
    {
        return MenuPanel::query()->forceCreate([
            'priority' => $priority,
            'label' => $slug,
            'title' => $slug,
            'slug' => $slug,
            'submenu' => true,
            'status' => 4,
        ]);
    }

    private function submenu(MenuPanel $menu, string $slug, int $priority): int
    {
        return (int) $menu->submenus()->create([
            'priority' => $priority,
            'label' => $slug,
            'title' => $slug,
            'slug' => $slug,
            'status' => 4,
        ])->getKey();
    }
}

class PermissionProbeController
{
    public function index()
    {
        return response('ok');
    }

    public function store()
    {
        return response('ok');
    }

    public function destroy()
    {
        return response('ok');
    }
}
