<?php

namespace App\Http\Controllers\Panel;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\MenuPanel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubmenuPanel;
use App\Services\InvestmentRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class RoleuserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Role::query()->with('permissions');

            return DataTables::of($data)
                ->addColumn('permission', static function (Role $role): string {
                    return $role->permissions->pluck('slug')->filter()->map(static fn (string $slug) => '| '.$slug)->implode(' ');
                })
                ->editColumn('status', static function (Role $role): string {
                    $active = (int) $role->status === 4;

                    return '<span class="badge '.($active ? 'bg-label-success' : 'bg-label-danger').' px-3 py-2 rounded-pill">'.($active ? 'فعال' : 'غیرفعال').'</span>';
                })
                ->addColumn('action', function (Role $role): string {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';
                    $buttons = '';
                    if (Gate::allows('can-access', ['roleuser', 'edit'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-primary edit-btn" data-id="'.$role->id.'"><i class="mdi mdi-pencil-outline"></i></button>';
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-info permission-btn" data-id="'.$role->id.'"><i class="mdi mdi-access-point"></i></button>';
                    }
                    if (Gate::allows('can-access', ['roleuser', 'delete']) && ! in_array($role->title, array_map(static fn (InvestmentRole $item): string => $item->value, InvestmentRole::cases()), true)) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-danger delete-btn" data-id="'.$role->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $buttons;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'submenu', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();
        $permissions = Permission::query()->select('id', 'label', 'slug')->orderBy('label')->get();
        $thispage = ['title' => 'مدیریت نقش', 'list' => 'لیست نقش', 'add' => 'افزودن نقش', 'create' => 'ایجاد نقش', 'enter' => 'ورود نقش', 'edit' => 'ویرایش نقش', 'delete' => 'حذف نقش'];

        return view('panel.roleuser', compact('thispage', 'menupanels', 'submenupanels', 'permissions'));
    }

    public function edit(int $id): JsonResponse
    {
        $role = Role::query()->with('permissions')->findOrFail($id);
        $rolePermissions = $role->title === InvestmentRole::SuperAdmin->value
            ? Permission::query()->get()
            : $role->permissions;
        $actions = $rolePermissions->mapWithKeys(static fn (Permission $permission) => [
            (string) $permission->id => [
                'can_view' => $permission->pivot ? (bool) $permission->pivot->can_view : true,
                'can_insert' => $permission->pivot ? (bool) $permission->pivot->can_insert : true,
                'can_edit' => $permission->pivot ? (bool) $permission->pivot->can_edit : true,
                'can_delete' => $permission->pivot ? (bool) $permission->pivot->can_delete : true,
            ],
        ]);

        return response()->json(['data' => [
            'id' => $role->id, 'title_fa' => $role->title_fa, 'title' => $role->title, 'status' => $role->status,
            'permission_ids' => $rolePermissions->pluck('id')->values(), 'actions' => $actions,
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['title_fa' => ['required', 'string', 'max:255'], 'title' => ['required', 'string', 'max:255', 'unique:roles,title'], 'status' => ['required', Rule::in([0, 4, '0', '4'])]]);
        try {
            Role::query()->create(['title_fa' => $validated['title_fa'], 'title' => $validated['title'], 'status' => (int) $validated['status'], 'user_id' => Auth::id()]);

            return $this->success('نقش با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ثبت نقش انجام نشد.');
        }
    }

    public function update(Request $request, int $id, InvestmentRoleService $roleService): JsonResponse
    {
        if ($request->input('type') === 'permission_update') {
            return $this->updatePermissionActions($request, $id);
        }

        $validated = $request->validate([
            'title_fa' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255', Rule::unique('roles', 'title')->ignore($id)],
            'status' => ['required', Rule::in([0, 4, '0', '4'])],
            'permission_id' => ['nullable', 'array'],
            'permission_id.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ]);

        $targetRole = Role::query()->findOrFail($id);
        if ($roleService->isCoreRole($targetRole) && ($validated['title'] !== $targetRole->title || (int) $validated['status'] !== 4)) {
            return response()->json([
                'success' => false,
                'subject' => 'عملیات غیرمجاز',
                'flag' => 'error',
                'message' => 'Slug و وضعیت فعال نقش‌های اصلی فرایند قابل تغییر نیست.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($validated, $id) {
                $role = Role::query()->with('permissions')->findOrFail($id);
                $role->update(['title_fa' => $validated['title_fa'], 'title' => $validated['title'], 'status' => (int) $validated['status']]);

                if ($role->title === InvestmentRole::SuperAdmin->value) {
                    $role->permissions()->sync($this->fullAccessPayload());

                    return;
                }

                $selected = collect($validated['permission_id'] ?? [])->map(static fn ($permissionId) => (int) $permissionId)->unique();
                $existing = $role->permissions->keyBy('id');
                $payload = [];
                foreach ($selected as $permissionId) {
                    $permission = $existing->get($permissionId);
                    $payload[$permissionId] = [
                        'can_view' => (bool) ($permission?->pivot?->can_view ?? true), 'can_insert' => (bool) ($permission?->pivot?->can_insert ?? false),
                        'can_edit' => (bool) ($permission?->pivot?->can_edit ?? false), 'can_delete' => (bool) ($permission?->pivot?->can_delete ?? false),
                    ];
                }
                $role->permissions()->sync($payload);
            });

            return $this->success('اطلاعات نقش با موفقیت ویرایش شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ویرایش نقش انجام نشد.');
        }
    }

    public function destroy(int $id, InvestmentRoleService $roleService): JsonResponse
    {
        $role = Role::query()->findOrFail($id);
        if ($roleService->isCoreRole($role)) {
            return response()->json(['success' => false, 'subject' => 'عملیات غیرمجاز', 'flag' => 'error', 'message' => 'نقش‌های اصلی فرایند سرمایه‌گذاری قابل حذف نیستند.'], 422);
        }
        try {
            $role->delete();

            return $this->success('نقش با موفقیت حذف شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('حذف نقش انجام نشد.');
        }
    }

    private function updatePermissionActions(Request $request, int $roleId): JsonResponse
    {
        $validated = $request->validate(['permissions' => ['nullable', 'array']]);
        $submitted = $validated['permissions'] ?? [];
        DB::transaction(function () use ($roleId, $submitted) {
            $role = Role::query()->with('permissions')->findOrFail($roleId);

            if ($role->title === InvestmentRole::SuperAdmin->value) {
                $role->permissions()->sync($this->fullAccessPayload());

                return;
            }

            foreach ($role->permissions as $permission) {
                $actions = $submitted[$permission->id] ?? [];
                $role->permissions()->updateExistingPivot($permission->id, [
                    'can_view' => isset($actions['can_view']), 'can_insert' => isset($actions['can_insert']), 'can_edit' => isset($actions['can_edit']), 'can_delete' => isset($actions['can_delete']),
                ]);
            }
        });

        return $this->success('دسترسی‌ها با موفقیت به‌روزرسانی شدند.');
    }

    /** @return array<int, array{can_view: bool, can_insert: bool, can_edit: bool, can_delete: bool}> */
    private function fullAccessPayload(): array
    {
        return Permission::query()->pluck('id')->mapWithKeys(static fn (int $permissionId): array => [
            $permissionId => [
                'can_view' => true,
                'can_insert' => true,
                'can_edit' => true,
                'can_delete' => true,
            ],
        ])->all();
    }

    private function success(string $message): JsonResponse
    {
        return response()->json(['success' => true, 'subject' => 'عملیات موفق', 'flag' => 'success', 'message' => $message]);
    }

    private function failure(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'subject' => 'خطا در ارتباط با سرور', 'flag' => 'error', 'message' => $message], 500);
    }
}
