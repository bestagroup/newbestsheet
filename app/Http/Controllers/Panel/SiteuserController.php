<?php

namespace App\Http\Controllers\Panel;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MenuPanel;
use App\Models\SubmenuPanel;
use App\Models\User;
use App\Services\InvestmentRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class SiteuserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::query()->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->select('users.id', 'users.name', 'users.email', 'users.phone', 'roles.title_fa', 'users.status')
                ->where('users.level', 'applicant');

            return DataTables::of($data)
                ->addColumn('title', static fn ($row) => $row->title_fa)
                ->editColumn('status', static function ($row): string {
                    $active = (int) $row->status === 4;

                    return '<span class="badge '.($active ? 'bg-label-success' : 'bg-label-danger').' px-3 py-2 rounded-pill">'.($active ? 'فعال' : 'غیرفعال').'</span>';
                })
                ->addColumn('action', function ($row): string {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';
                    $buttons = '';
                    if (Gate::allows('can-access', ['siteuser', 'edit'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-primary edit-btn" data-id="'.$row->id.'"><i class="mdi mdi-pencil-outline"></i></button>';
                    }
                    if (Gate::allows('can-access', ['siteuser', 'delete'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-danger delete-btn" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $buttons;
                })->rawColumns(['action', 'status'])->make(true);
        }

        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'submenu', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();
        $companies = Company::query()->select('id', 'company_name', 'commercial_name', 'user_id')->orderBy('company_name')->get();
        $thispage = ['title' => 'مدیریت کاربران شرکت ها', 'list' => 'لیست کاربران شرکت ها', 'add' => 'افزودن کاربر شرکت', 'create' => 'ایجاد کاربر شرکت', 'enter' => 'ورود کاربر شرکت', 'edit' => 'ویرایش کاربر شرکت', 'delete' => 'حذف کاربر شرکت'];

        return view('panel.siteuser', compact('thispage', 'menupanels', 'submenupanels', 'companies'));
    }

    public function edit(int $id): JsonResponse
    {
        $user = User::query()->where('level', 'applicant')->findOrFail($id);
        $companyId = Company::query()->where('user_id', $user->id)->value('id');

        return response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'phone' => $user->phone, 'email' => $user->email, 'gender' => $user->gender, 'status' => $user->status, 'company_id' => $companyId]]);
    }

    public function store(Request $request, InvestmentRoleService $roleService): JsonResponse
    {
        $validated = $this->validatePayload($request);
        try {
            $representativeRole = $roleService->resolve(InvestmentRole::InvesteeRepresentative);
            DB::transaction(function () use ($validated, $representativeRole) {
                $user = User::query()->create(['name' => $validated['name'], 'phone' => $validated['phone'] ?? null, 'email' => $validated['email'], 'role_id' => $representativeRole->id, 'level' => 'applicant', 'status' => 4, 'password' => Hash::make($validated['password'])]);
                $user->gender = $validated['gender'] ?? null;
                $user->save();
                $user->roles()->sync([$representativeRole->id]);
                Company::query()->whereKey($validated['company_id'])->update(['user_id' => $user->id]);
            });

            return $this->success('نماینده شرکت با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ثبت نماینده شرکت انجام نشد.');
        }
    }

    public function update(Request $request, int $id, InvestmentRoleService $roleService): JsonResponse
    {
        $validated = $this->validatePayload($request, $id, false);
        try {
            $representativeRole = $roleService->resolve(InvestmentRole::InvesteeRepresentative);
            DB::transaction(function () use ($validated, $id, $representativeRole) {
                $user = User::query()->where('level', 'applicant')->findOrFail($id);
                $user->name = $validated['name'];
                $user->phone = $validated['phone'] ?? null;
                $user->email = $validated['email'];
                $user->gender = $validated['gender'] ?? null;
                $user->status = (int) $validated['status'];
                if (! empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                $user->role_id = $representativeRole->id;
                $user->save();
                $user->roles()->sync([$representativeRole->id]);
                Company::query()->where('user_id', $user->id)->where('id', '!=', $validated['company_id'])->update(['user_id' => null]);
                Company::query()->whereKey($validated['company_id'])->update(['user_id' => $user->id]);
            });

            return $this->success('نماینده شرکت با موفقیت ویرایش شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ویرایش نماینده شرکت انجام نشد.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            DB::transaction(function () use ($id) {
                $user = User::query()->where('level', 'applicant')->findOrFail($id);
                Company::query()->where('user_id', $user->id)->update(['user_id' => null]);
                $user->delete();
            });

            return $this->success('نماینده شرکت با موفقیت حذف شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('حذف نماینده شرکت انجام نشد.');
        }
    }

    private function validatePayload(Request $request, ?int $userId = null, bool $passwordRequired = true): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:32'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)], 'gender' => ['nullable', 'integer', 'in:1,2'], 'company_id' => ['required', 'integer', 'exists:companies,id'], 'status' => [$userId ? 'required' : 'nullable', 'integer', 'in:0,4'], 'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'min:8', 'confirmed']]);
    }

    private function success(string $message): JsonResponse
    {
        return response()->json(['success' => true, 'subject' => 'عملیات موفق', 'flag' => 'success', 'message' => $message]);
    }

    private function failure(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'subject' => 'خطا در ارتباط با سرور', 'flag' => 'error', 'message' => $message],500);
    }
}
