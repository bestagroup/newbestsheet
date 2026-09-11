<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\InvesteeSaleRequest;
use App\Models\Sale;
use App\Services\ActivityLogService;
use App\Services\InvesteePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InvesteeSaleController extends Controller
{
    public function __construct(
        private readonly InvesteePortalService $portal,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());

        $query = Sale::query()
            ->where('project_id', $project->getKey())
            ->select([
                'id', 'project_id', 'count_customers', 'count_sales', 'production_count',
                'amount_sales', 'monthly_income', 'current_cost', 'financial_cost',
                'date', 'description', 'created_at',
            ]);

        return DataTables::of($query)
            ->editColumn('amount_sales', static fn (Sale $sale): string => number_format((float) ($sale->amount_sales ?? 0)))
            ->editColumn('monthly_income', static fn (Sale $sale): string => number_format((float) ($sale->monthly_income ?? 0)))
            ->editColumn('current_cost', static fn (Sale $sale): string => number_format((float) ($sale->current_cost ?? 0)))
            ->editColumn('financial_cost', static fn (Sale $sale): string => number_format((float) ($sale->financial_cost ?? 0)))
            ->editColumn('date', static fn (Sale $sale): string => $sale->date ? jdate($sale->date)->format('Y/m/d') : '')
            ->addColumn('action', static fn (Sale $sale): string => '<button type="button" class="btn btn-sm btn-outline-primary investee-sale-edit" data-id="'.$sale->getKey().'"><i class="mdi mdi-pencil-outline"></i></button> '.
                '<button type="button" class="btn btn-sm btn-outline-danger investee-sale-delete" data-id="'.$sale->getKey().'"><i class="mdi mdi-delete-outline"></i></button>'
            )
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(InvesteeSaleRequest $request): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $sale = $project->sales()->create($request->validated());

        $this->activityLog->record(
            'investee.sale_created',
            sprintf('گزارش فروش #%d برای پروژه #%d ثبت شد.', $sale->getKey(), $project->getKey()),
            (int) $request->user()->getKey()
        );

        return response()->json(['success' => true, 'message' => 'اطلاعات فروش با موفقیت ثبت شد.']);
    }

    public function edit(Request $request, int $sale): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $record = Sale::query()->where('project_id', $project->getKey())->findOrFail($sale);

        return response()->json(['data' => $record]);
    }

    public function update(InvesteeSaleRequest $request, int $sale): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $record = Sale::query()->where('project_id', $project->getKey())->findOrFail($sale);
        $record->fill($request->validated())->save();

        $this->activityLog->record(
            'investee.sale_updated',
            sprintf('گزارش فروش #%d پروژه #%d ویرایش شد.', $record->getKey(), $project->getKey()),
            (int) $request->user()->getKey()
        );

        return response()->json(['success' => true, 'message' => 'اطلاعات فروش با موفقیت به‌روزرسانی شد.']);
    }

    public function destroy(Request $request, int $sale): JsonResponse
    {
        $project = $this->portal->projectFor($request->user());
        $record = Sale::query()->where('project_id', $project->getKey())->findOrFail($sale);
        $recordId = (int) $record->getKey();
        $record->delete();

        $this->activityLog->record(
            'investee.sale_deleted',
            sprintf('گزارش فروش #%d پروژه #%d حذف شد.', $recordId, $project->getKey()),
            (int) $request->user()->getKey()
        );

        return response()->json(['success' => true, 'message' => 'گزارش فروش حذف شد.']);
    }
}
