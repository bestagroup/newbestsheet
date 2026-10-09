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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('finances as f')
                ->leftJoin('projects as p', 'p.id', '=', 'f.project_id')
                ->select(
                    'f.serial',
                    'f.id',
                    'f.amount',
                    'f.docserial',
                    'f.date',
                    'p.id as project_id',
                    'p.company_name',
                    'p.title',
                    'p.amount_request_accept',
                    'p.start_date'
                )
                ->where('f.amount', '>', 0)
                ->orderBy('f.serial');

            return DataTables::of($query)
                ->addColumn('contract_amount', static fn ($row): string => number_format((float) ($row->amount_request_accept ?? 0)))
                ->editColumn('amount', static fn ($row): string => \App\Support\Monetary::format($row->amount))
                ->addColumn('contract_date', static fn ($row) => $row->start_date)
                ->addColumn('action', function ($row): string {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';
                    $buttons = '';

                    if (Gate::allows('can-access', ['finance', 'edit'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-primary edit-btn" data-id="'.$row->id.'" data-url="'.route('finance.edit', $row->id).'"><i class="mdi mdi-pencil-outline"></i></button>';
                    }
                    if (Gate::allows('can-access', ['finance', 'delete'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-danger delete-btn" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $buttons;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $thispage = [
            'title' => 'مدیریت پرداخت‌های سرمایه‌گذاری',
            'list' => 'لیست پرداخت‌ها',
            'add' => 'افزودن پرداخت',
            'create' => 'ایجاد پرداخت',
            'enter' => 'ورود پرداخت',
            'edit' => 'ویرایش پرداخت',
            'delete' => 'حذف پرداخت',
        ];
        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();
        $projects = Project::query()->where('invest_step', '<>', 0)->orderBy('title')->get(['id', 'title', 'company_name']);

        return view('panel.finance', compact('menupanels', 'submenupanels', 'thispage', 'projects'));
    }

    public function edit(int $id)
    {
        $finance = Finance::query()->findOrFail($id);
        $projects = Project::query()->where('invest_step', '<>', 0)->orderBy('title')->get(['id', 'title', 'company_name']);

        return view('panel.partials.edit-form-finance', compact('finance', 'projects'));
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
        return response()->json(['success' => true, 'subject' => 'عملیات موفق', 'flag' => 'success', 'message' => $message]);
    }

    private function failure(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'subject' => 'خطا در ارتباط با سرور', 'flag' => 'error', 'message' => $message], 500);
    }
}
