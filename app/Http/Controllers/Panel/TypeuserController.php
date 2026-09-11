<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MenuPanel;
use App\Models\SubmenuPanel;
use App\Models\TypeUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TypeuserController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {
            return DataTables::eloquent(TypeUser::query()->select(['id', 'title_fa', 'title', 'status']))
                ->editColumn('status', static fn (TypeUser $typeUser): string => (int) $typeUser->status === 4 ? 'فعال' : 'غیرفعال')
                ->addColumn('action', static fn (TypeUser $typeUser): string => '<button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-btn" data-id="'.$typeUser->id.'"><i class="mdi mdi-pencil-outline"></i></button>
                     <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-btn" data-id="'.$typeUser->id.'"><i class="mdi mdi-delete-outline"></i></button>')
                ->rawColumns(['action'])
                ->toJson();
        }

        return view('panel.typeuser', $this->viewData('مدیریت نوع کاربران'));
    }

    public function edit(int $id): JsonResponse
    {
        return response()->json(['data' => TypeUser::query()->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTypeUser($request);
        $typeUser = new TypeUser;
        $typeUser->fill(['title_fa' => $validated['title_fa'], 'title' => $validated['title']]);
        $typeUser->status = $validated['status'];
        $typeUser->save();

        return $this->success('نوع کاربر با موفقیت ثبت شد.');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $this->validateTypeUser($request);
        $typeUser = TypeUser::query()->findOrFail($id);
        $typeUser->fill(['title_fa' => $validated['title_fa'], 'title' => $validated['title']]);
        $typeUser->status = $validated['status'];
        $typeUser->save();

        return $this->success('نوع کاربر با موفقیت ویرایش شد.');
    }

    public function destroy(int $id): JsonResponse
    {
        TypeUser::query()->findOrFail($id)->delete();

        return $this->success('نوع کاربر با موفقیت حذف شد.');
    }

    protected function viewData(string $title): array
    {
        return [
            'menupanels' => MenuPanel::query()->select(['id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'class', 'controller'])->get(),
            'submenupanels' => SubmenuPanel::query()->select(['id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id'])->get(),
            'thispage' => [
                'title' => $title,
                'list' => 'لیست انواع کاربران',
                'add' => 'افزودن نوع کاربر',
                'edit' => 'ویرایش نوع کاربر',
                'delete' => 'حذف نوع کاربر',
            ],
        ];
    }

    protected function validateTypeUser(Request $request): array
    {
        return $request->validate([
            'title_fa' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'integer', 'in:0,4'],
        ]);
    }

    protected function success(string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'subject' => 'عملیات موفق',
            'flag' => 'success',
            'message' => $message,
        ]);
    }
}
