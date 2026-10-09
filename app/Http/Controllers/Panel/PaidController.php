<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\FinanceRequest;
use App\Models\Finance;
use App\Models\MenuPanel;
use App\Models\Project;
use App\Models\SubmenuPanel;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class PaidController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Finance::query()
                ->leftJoin('projects', 'projects.id', '=', 'finances.project_id')
                ->select([
                    'finances.id',
                    'projects.title',
                    'finances.amount',
                    'finances.date',
                    'finances.serial',
                    'finances.description',
                ]);

            return DataTables::of($data)
                ->editColumn('amount', static fn ($row): string => \App\Support\Monetary::format($row->amount))
                ->addColumn('action', function ($row): string {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';
                    $buttons = '';

                    if (Gate::allows('can-access', ['finance', 'edit'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-primary edit-btn" data-id="'.$row->id.'"><i class="mdi mdi-pencil-outline"></i></button>';
                    }

                    if (Gate::allows('can-access', ['finance', 'delete'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-danger delete-btn" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $buttons;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'menu_id', 'slug', 'status', 'class', 'controller')->get();
        $menupanels = MenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $projects = Project::query()->select('id', 'title')->orderBy('title')->get();
        $thispage = [
            'title' => 'مدیریت پرداخت ها',
            'list' => 'لیست پرداخت ها',
            'add' => 'افزودن پرداخت ها',
            'create' => 'ایجاد پرداخت ها',
            'enter' => 'ورود پرداخت ها',
            'edit' => 'ویرایش پرداخت ها',
            'delete' => 'حذف پرداخت ها',
        ];

        return view('panel.paidmanage', compact('thispage', 'submenupanels', 'menupanels', 'projects'));
    }

    public function edit(int $id): JsonResponse
    {
        $finance = Finance::query()->findOrFail($id);

        return response()->json(['data' => $finance]);
    }

    public function store(FinanceRequest $request, FinanceService $service): JsonResponse
    {
        $finance = $service->create($request->validated());

        return response()->json(['success' => true, 'flag' => 'success', 'subject' => 'عملیات موفق', 'message' => 'پرداخت ثبت شد.', 'id' => $finance->id, 'next_idempotency_key' => (string) Str::uuid()]);
    }

    public function update(FinanceRequest $request, int $id, FinanceService $service): JsonResponse
    {
        $service->update($id, $request->validated());

        return $this->success('پرداخت ویرایش شد.');
    }

    public function destroy(int $id, FinanceService $service): JsonResponse
    {
        $service->delete($id);

        return $this->success('پرداخت حذف و سابقه آن نگهداری شد.');
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

    private function failure(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'subject' => 'خطا در ارتباط با سرور',
            'flag' => 'error',
            'message' => $message,
        ], 500);
    }
}
