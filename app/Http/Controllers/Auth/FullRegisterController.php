<?php

namespace App\Http\Controllers\Auth;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\User_logs;
use App\Services\InvestmentRoleService;
use App\Support\IranianMobileNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FullRegisterController extends Controller
{
    public function register(Request $request, InvestmentRoleService $roleService)
    {
        $normalizedPhone = IranianMobileNormalizer::normalize(
            is_scalar($request->input('phone')) ? (string) $request->input('phone') : null
        );
        if ($normalizedPhone !== null) {
            $request->merge(['phone' => $normalizedPhone]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'CEO' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms_accepted' => ['accepted'],
        ]);

        try {
            $user = DB::transaction(function () use ($validated, $roleService, $request): User {
                $role = $roleService->resolve(InvestmentRole::InvesteeRepresentative);

                $user = User::query()->create([
                    'name' => $validated['CEO'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'level' => 'applicant',
                    'status' => 4,
                    'role_id' => $role->id,
                    'change_password' => 1,
                    'password' => Hash::make($validated['password']),
                ]);

                $company = Company::query()->create([
                    'user_id' => $user->getKey(),
                    'title' => $validated['title'],
                    'company_name' => $validated['title'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'],
                    'ceo_name' => $validated['CEO'],
                ]);

                Project::query()->create([
                    'title' => $validated['title'],
                    'company_name' => $validated['title'],
                    'CEO' => $validated['CEO'],
                    'ceo_phone' => $validated['phone'],
                    'email' => $validated['email'],
                    'user_id' => $user->getKey(),
                    'company_id' => $company->getKey(),
                ]);

                $user->roles()->syncWithoutDetaching([$role->id]);

                User_logs::query()->create([
                    'user_id' => $user->getKey(),
                    'action' => 'register',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => true,
                    'description' => 'ثبت نام نماینده سرمایه‌پذیر و ایجاد پرونده اولیه',
                ]);

                return $user;
            }, 3);

            Auth::login($user);

            return redirect()->route('profile')->with('success', 'ثبت‌نام با موفقیت انجام شد.');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['system' => 'خطا در ذخیره اطلاعات. لطفاً دوباره تلاش کنید.'])->withInput();
        }
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->to('/login');
    }
}
