<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MenuPanel;
use App\Models\Owner;
use App\Models\SubmenuPanel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Owner::query())
                ->addColumn('action', static fn ($row): string => '<button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-btn mx-1" data-id="'.$row->id.'"><i class="mdi mdi-pencil-outline"></i></button>'.
                    '<button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-btn mx-1" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>'
                )
                ->rawColumns(['action'])
                ->make(true);
        }

        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();
        $thispage = [
            'title' => 'مدیریت اطلاعات کارفرما',
            'list' => 'لیست اطلاعات کارفرما',
            'add' => 'افزودن اطلاعات کارفرما',
            'create' => 'ایجاد اطلاعات کارفرما',
            'enter' => 'ورود اطلاعات کارفرما',
            'edit' => 'ویرایش اطلاعات کارفرما',
            'delete' => 'حذف اطلاعات کارفرما',
        ];

        return view('panel.owner', compact('thispage', 'menupanels', 'submenupanels'));
    }

    public function edit(int $id): JsonResponse
    {
        return response()->json(['data' => Owner::query()->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['user_id'] = Auth::id();
        $validated['image'] = $request->input('image', '');
        $validated['social'] = $this->normalizeSocial($validated['social'] ?? null);

        try {
            Owner::query()->create($validated);

            return $this->success('اطلاعات کارفرما با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ثبت اطلاعات کارفرما انجام نشد.');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['social'] = $this->normalizeSocial($validated['social'] ?? null);

        try {
            Owner::query()->findOrFail($id)->update($validated);

            return $this->success('اطلاعات کارفرما با موفقیت ویرایش شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ویرایش اطلاعات کارفرما انجام نشد.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            Owner::query()->findOrFail($id)->delete();

            return $this->success('اطلاعات کارفرما با موفقیت حذف شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('حذف اطلاعات کارفرما انجام نشد.');
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tel' => ['nullable', 'string'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:191'],
            'ceo' => ['nullable', 'string', 'max:191'],
            'meli_code' => ['nullable', 'string', 'max:20'],
            'eghtesadi_code' => ['nullable', 'string', 'max:26'],
            'date_sabt' => ['nullable', 'string', 'max:12'],
            'address' => ['nullable', 'string'],
            'social' => ['nullable', 'string'],
            'summery' => ['nullable', 'string'],
        ]);
    }

    private function normalizeSocial(?string $social): ?string
    {
        if ($social === null || trim($social) === '') {
            return null;
        }

        $decoded = json_decode($social, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        return json_encode(array_values(array_filter(array_map('trim', explode(',', $social)))), JSON_UNESCAPED_UNICODE);
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
