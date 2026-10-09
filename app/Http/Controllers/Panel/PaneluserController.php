<?php

namespace App\Http\Controllers\Panel;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\MenuPanel;
use App\Models\Role;
use App\Models\SubmenuPanel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class PaneluserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::query()
                ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
                ->select('users.id', 'users.name', 'users.email', 'users.phone', 'roles.title_fa', 'users.status')
                ->where('users.level', 'admin');

            return DataTables::of($data)
                ->addColumn('title', static fn ($row) => $row->title_fa)
                ->editColumn('status', static function ($row): string {
                    $active = (int) $row->status === 4;

                    return '<span class="badge '.($active ? 'bg-label-success' : 'bg-label-danger').' px-3 py-2 rounded-pill">'.($active ? 'فعال' : 'غیرفعال').'</span>';
                })
                ->addColumn('action', function ($row): string {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';
                    $buttons = '';
                    if (Gate::allows('can-access', ['paneluser', 'edit'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-primary edit-btn" data-id="'.$row->id.'"><i class="mdi mdi-pencil-outline"></i></button>';
                    }
                    if (Gate::allows('can-access', ['paneluser', 'delete'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-danger delete-btn" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $buttons;
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'submenu', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();
        $roles = Role::query()
            ->whereNotIn('title', [InvestmentRole::InvesteeRepresentative->value, 'CapitalCapable'])
            ->select('id', 'title_fa', 'title', 'status')
            ->orderBy('title_fa')
            ->get();
        $thispage = [
            'title' => 'مدیریت کاربران داشبورد', 'list' => 'لیست کاربران داشبورد', 'add' => 'افزودن کاربر داشبورد',
            'create' => 'ایجاد کاربر داشبورد', 'enter' => 'ورود کاربر داشبورد', 'edit' => 'ویرایش کاربر داشبورد', 'delete' => 'حذف کاربر داشبورد',
        ];

        return view('panel.paneluser', compact('thispage', 'menupanels', 'submenupanels', 'roles'));
    }

    public function edit(int $id): JsonResponse
    {
        $user = User::query()->where('level', 'admin')->findOrFail($id);

        return response()->json(['data' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        try {
            DB::transaction(function () use ($validated) {
                $user = User::query()->create([
                    'name' => $validated['name'], 'phone' => $validated['phone'] ?? null, 'email' => $validated['email'],
                    'role_id' => (int) $validated['typeuser_id'], 'level' => 'admin', 'status' => 4,
                    'password' => Hash::make($validated['password']),
                ]);
                $user->national_id = $validated['national_id'] ?? null;
                $user->birthday = $validated['birthday'] ?? null;
                $user->gender = $validated['gender'] ?? null;
                $user->save();
                $user->roles()->sync([(int) $validated['typeuser_id']]);
            });

            return $this->success('کاربر داشبورد با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ثبت کاربر داشبورد انجام نشد.');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $this->validatePayload($request, $id, false);

        try {
            DB::transaction(function () use ($validated, $id) {
                $user = User::query()->where('level', 'admin')->findOrFail($id);
                $user->name = $validated['name'];
                $user->phone = $validated['phone'] ?? null;
                $user->email = $validated['email'];
                $user->national_id = $validated['national_id'] ?? null;
                $user->birthday = $validated['birthday'] ?? null;
                $user->gender = $validated['gender'] ?? null;
                $user->role_id = (int) $validated['typeuser_id'];
                $user->status = (int) $validated['status'];
                if (! empty($validated['password'])) {
                    $user->password = Hash::make($validated['password']);
                }
                $user->save();
                $user->roles()->sync([(int) $validated['typeuser_id']]);
            });

            return $this->success('کاربر داشبورد با موفقیت ویرایش شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ویرایش کاربر داشبورد انجام نشد.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $target = User::query()->findOrFail($id);
        abort_if($target->hasRole(['superadmin', 'manager']) && ! auth()->user()->hasRole('superadmin'), 403);
        try {
            $user = User::query()->where('level', 'admin')->findOrFail($id);
            if ($user->id === auth()->id()) {
                return response()->json(['success' => false, 'subject' => 'عملیات غیرمجاز', 'flag' => 'error', 'message' => 'کاربر جاری نمی‌تواند حساب خود را از این بخش حذف کند.'], 422);
            }
            $user->delete();

            return $this->success('کاربر داشبورد با موفقیت حذف شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('حذف کاربر داشبورد انجام نشد.');
        }
    }

    private function validatePayload(Request $request, ?int $userId = null, bool $passwordRequired = true): array
    {
        if (! $request->user()->hasRole('superadmin')) {
            $role = Role::query()->find($request->input('typeuser_id'));
            abort_if($role && in_array($role->title, ['superadmin', 'manager'], true), 403);
            if ($userId) {
                abort_if(User::query()->findOrFail($userId)->hasRole(['superadmin', 'manager']), 403);
            }
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'national_id' => ['nullable', 'string', 'max:32'],
            'typeuser_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(static function ($query) {
                    $query->where('status', 4)
                        ->whereNotIn('title', [InvestmentRole::InvesteeRepresentative->value, 'CapitalCapable']);
                }),
            ],
            'birthday' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', 'integer', 'in:1,2'],
            'status' => [$userId ? 'required' : 'nullable', 'integer', 'in:0,4'],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ]);
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
