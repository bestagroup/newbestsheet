<?php

namespace App\Http\Controllers\Panel;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\MenuPanel;
use App\Models\Role;
use App\Models\SubmenuPanel;
use App\Services\InvestmentRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UseraccessController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {
            $roles = Role::query()
                ->select(['id', 'title_fa', 'title', 'status'])
                ->withCount('permissions');

            return DataTables::eloquent($roles)
                ->editColumn('status', static fn (Role $role): string => (int) $role->status === 4 ? 'فعال' : 'غیرفعال')
                ->addColumn('action', static function (Role $role): string {
                    $edit = '<button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-btn" data-id="'.$role->id.'"><i class="mdi mdi-pencil-outline"></i></button>';
                    $delete = in_array($role->title, array_map(static fn (InvestmentRole $item): string => $item->value, InvestmentRole::cases()), true)
                        ? ''
                        : ' <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-btn" data-id="'.$role->id.'"><i class="mdi mdi-delete-outline"></i></button>';

                    return $edit.$delete;
                })
                ->rawColumns(['action'])
                ->toJson();
        }

        $menupanels = MenuPanel::query()
            ->select(['id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'class', 'controller'])
            ->get();
        $submenupanels = SubmenuPanel::query()
            ->select(['id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id'])
            ->get();
        $thispage = [
            'title' => 'مدیریت دسترسی‌های داشبورد',
            'list' => 'لیست نقش‌های داشبورد',
            'add' => 'افزودن نقش',
            'edit' => 'ویرایش نقش',
            'delete' => 'حذف نقش',
        ];

        return view('panel.useraccess', compact('thispage', 'menupanels', 'submenupanels'));
    }

    public function edit(int $id): JsonResponse
    {
        return response()->json(['data' => Role::query()->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        Role::query()->create([
            'title_fa' => $validated['title_fa'],
            'title' => $validated['title'],
            'status' => $validated['status'],
            'user_id' => Auth::id(),
        ]);

        return $this->success('نقش با موفقیت ثبت شد.');
    }

    public function update(Request $request, int $id, InvestmentRoleService $roleService): JsonResponse
    {
        $role = Role::query()->findOrFail($id);
        $validated = $request->validate($this->rules($role));

        if ($roleService->isCoreRole($role) && ($validated['title'] !== $role->title || (int) $validated['status'] !== 4)) {
            return response()->json([
                'success' => false,
                'subject' => 'عملیات غیرمجاز',
                'flag' => 'error',
                'message' => 'Slug و وضعیت فعال نقش‌های اصلی فرایند قابل تغییر نیست.',
            ], 422);
        }

        $role->update($validated);

        return $this->success('نقش با موفقیت ویرایش شد.');
    }

    public function destroy(int $id, InvestmentRoleService $roleService): JsonResponse
    {
        $role = Role::query()->findOrFail($id);

        if ($roleService->isCoreRole($role)) {
            return response()->json([
                'success' => false,
                'subject' => 'عملیات غیرمجاز',
                'flag' => 'error',
                'message' => 'نقش‌های اصلی فرایند سرمایه‌گذاری قابل حذف نیستند.',
            ], 422);
        }

        $role->delete();

        return $this->success('نقش با موفقیت حذف شد.');
    }

    private function rules(?Role $role = null): array
    {
        return [
            'title_fa' => ['required', 'string', 'max:255'],
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'title')->ignore($role?->id),
            ],
            'status' => ['required', 'integer', 'in:0,4'],
        ];
    }

    private function success(string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'subject' => 'عملیات موفق',
            'flag' => 'success',
            'message' => $message,
        ]);
    }
}
