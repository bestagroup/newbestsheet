<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CompanyRequest;
use App\Models\Company;
use App\Models\MenuPanel;
use App\Models\SubmenuPanel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Company::query()->select([
                'id',
                'commercial_name',
                'company_name',
                'ceo_name',
                'registration_number',
                'national_id',
                'registration_date',
                'economic_code',
                'legal_type',
                'website',
            ]);

            return DataTables::of($data)
                ->editColumn('registration_date', static function (Company $company): string {
                    return $company->registration_date
                        ? jdate($company->registration_date)->format('Y/m/d')
                        : '';
                })
                ->addColumn('action', function (Company $company): string {
                    $action = '';

                    if (auth()->user()->can('can-access', ['company', 'edit'])) {
                        $action .= '<button type="button" class="btn btn-sm btn-icon btn-outline-primary mx-1 edit-btn" data-id="'.$company->id.'"><i class="mdi mdi-pencil-outline"></i></button>';
                    }

                    if (auth()->user()->can('can-access', ['company', 'delete'])) {
                        $action .= '<button class="btn btn-sm btn-icon btn-outline-danger mx-1 delete-btn" data-id="'.$company->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $action;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'menu_id', 'slug', 'status', 'class', 'controller')->get();
        $menupanels = MenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $users = User::query()->whereLevel('applicant')->select('id', 'name')->orderBy('name')->get();

        $thispage = [
            'title' => 'مدیریت شرکت ها',
            'list' => 'لیست شرکت ها',
            'add' => 'افزودن شرکت ',
            'create' => 'ایجاد شرکت ',
            'enter' => 'ورود شرکت ',
            'edit' => 'ویرایش شرکت ',
            'delete' => 'حذف شرکت ',
        ];

        return view('panel.company', compact('thispage', 'submenupanels', 'menupanels', 'users'));
    }

    public function edit(int $id): JsonResponse
    {
        $company = Company::query()->findOrFail($id);

        return response()->json(['data' => $company]);
    }

    public function store(CompanyRequest $request): JsonResponse
    {
        try {
            Company::query()->create($request->validated());

            return $this->successResponse('اطلاعات شرکت با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            Log::error('Company store failed.', [
                'exception' => $exception,
                'user_id' => Auth::id(),
            ]);

            return $this->errorResponse('اطلاعات شرکت ثبت نشد، لطفاً بعداً مجدداً تلاش نمایید.');
        }
    }

    public function update(CompanyRequest $request, int $id): JsonResponse
    {
        try {
            $company = Auth::user()->level === 'applicant'
                ? Auth::user()->company()->firstOrFail()
                : Company::query()->findOrFail($id);

            $data = $request->validated();

            if (Auth::user()->level !== 'admin') {
                unset($data['user_id']);
            }

            $company->fill($data)->save();

            return response()->json([
                'success' => true,
                'subject' => 'عملیات موفق',
                'flag' => 'success',
                'message' => 'اطلاعات شرکت با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'registration_number' => $company->registration_number,
                    'national_id' => $company->national_id,
                    'phone' => $company->phone,
                    'email' => $company->email,
                    'address' => $company->address,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Company update failed.', [
                'exception' => $exception,
                'company_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return $this->errorResponse('اطلاعات شرکت به‌روزرسانی نشد، لطفاً بعداً مجدداً تلاش نمایید.');
        }
    }

    public function destroy(Request $request, int $company): JsonResponse
    {
        $record = Company::query()->findOrFail($company);
        abort_if($record->project()->exists() || $record->minute()->exists() || $record->MediaFile()->exists(), 409, 'شرکت دارای پرونده یا مستند است.');
        $record->delete();

        return $this->successResponse('شرکت حذف شد.');
    }

    private function successResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'subject' => 'عملیات موفق',
            'flag' => 'success',
            'message' => $message,
        ]);
    }

    private function errorResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'subject' => 'خطا در ارتباط با سرور',
            'flag' => 'error',
            'message' => $message,
        ], 500);
    }
}
