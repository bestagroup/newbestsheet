<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuPanel;
use App\Models\SubmenuPanel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class MenusiteController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Menu::query()->select('id', 'priority', 'title', 'slug', 'status', 'class', 'controller');

            return DataTables::of($data)
                ->editColumn('status', static fn ($row): string => (int) $row->status === 4 ? 'در حال نمایش' : 'عدم نمایش')
                ->addColumn('action', static fn ($row): string => '<button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-btn mx-1" data-id="'.$row->id.'"><i class="mdi mdi-pencil-outline"></i></button>'.
                    '<button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-btn mx-1" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>'
                )
                ->rawColumns(['action'])
                ->make(true);
        }

        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();
        $thispage = [
            'title' => 'مدیریت منو سایت',
            'list' => 'لیست منو سایت',
            'add' => 'افزودن منو سایت',
            'create' => 'ایجاد منو سایت',
            'enter' => 'ورود منو سایت',
            'edit' => 'ویرایش منو سایت',
            'delete' => 'حذف منو سایت',
        ];

        return view('panel.menusite', compact('thispage', 'menupanels', 'submenupanels'));
    }

    public function edit(int $id): JsonResponse
    {
        return response()->json(['data' => Menu::query()->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['user_id'] = Auth::id();

        try {
            Menu::query()->create($validated);

            return $this->success('منوی سایت با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ثبت منوی سایت انجام نشد.');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['user_id'] = Auth::id();

        try {
            Menu::query()->findOrFail($id)->update($validated);

            return $this->success('منوی سایت با موفقیت ویرایش شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ویرایش منوی سایت انجام نشد.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            Menu::query()->findOrFail($id)->delete();

            return $this->success('منوی سایت با موفقیت حذف شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('حذف منوی سایت انجام نشد.');
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tab_title' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'submenu' => ['nullable', 'integer', 'in:0,1'],
            'class' => ['nullable', 'string', 'max:255'],
            'controller' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:0,4'],
            'home_show' => ['nullable', 'integer', 'in:0,1,4'],
            'keyword' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
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
