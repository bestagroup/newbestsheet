<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuPanel;
use App\Models\Submenu;
use App\Models\SubmenuPanel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class SubmenusiteController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Submenu::query()
                ->leftJoin('menus', 'menus.id', '=', 'submenus.menu_id')
                ->select('submenus.id', 'submenus.title', 'submenus.slug', 'submenus.status', 'submenus.class', 'submenus.controller', 'menus.title as menu');

            return DataTables::of($data)
                ->editColumn('status', static fn ($row): string => (int) $row->status === 4 ? 'در حال نمایش' : 'عدم نمایش')
                ->addColumn('action', static fn ($row): string => '<button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-btn mx-1" data-id="'.$row->id.'"><i class="mdi mdi-pencil-outline"></i></button>'.
                    '<button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-btn mx-1" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>'
                )
                ->rawColumns(['action'])
                ->make(true);
        }

        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'menu_id', 'slug', 'status', 'class', 'controller')->get();
        $menupanels = MenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $menus = Menu::query()->select('id', 'title')->orderBy('title')->get();
        $thispage = [
            'title' => 'مدیریت زیر صفحه سایت',
            'list' => 'لیست زیر صفحه سایت',
            'add' => 'افزودن زیر صفحه سایت',
            'create' => 'ایجاد زیر صفحه سایت',
            'enter' => 'ورود زیر صفحه سایت',
            'edit' => 'ویرایش زیر صفحه سایت',
            'delete' => 'حذف زیر صفحه سایت',
        ];

        return view('panel.submenusite', compact('thispage', 'submenupanels', 'menupanels', 'menus'));
    }

    public function edit(int $id): JsonResponse
    {
        return response()->json(['data' => Submenu::query()->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['user_id'] = Auth::id();

        try {
            Submenu::query()->create($validated);

            return $this->success('زیرصفحه سایت با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ثبت زیرصفحه سایت انجام نشد.');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $validated['user_id'] = Auth::id();

        try {
            Submenu::query()->findOrFail($id)->update($validated);

            return $this->success('زیرصفحه سایت با موفقیت ویرایش شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ویرایش زیرصفحه سایت انجام نشد.');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            Submenu::query()->findOrFail($id)->delete();

            return $this->success('زیرصفحه سایت با موفقیت حذف شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('حذف زیرصفحه سایت انجام نشد.');
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tab_title' => ['nullable', 'string', 'max:255'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'class' => ['nullable', 'string', 'max:255'],
            'controller' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:0,4'],
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
