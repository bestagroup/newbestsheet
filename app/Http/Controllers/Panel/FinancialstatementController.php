<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\FinancialStatementRequest;
use App\Models\Financial_statement;
use App\Models\MenuPanel;
use App\Models\Project;
use App\Models\SubmenuPanel;
use App\Services\ActivityLogService;
use App\Services\BusinessAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class FinancialstatementController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Financial_statement::query()
                ->from('financial_statements as f')
                ->join('projects as p', 'p.id', '=', 'f.project_id')
                ->where('p.invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)
                ->when($request->filled('project_id'), static function ($builder) use ($request): void {
                    $builder->where('f.project_id', $request->integer('project_id'));
                })
                ->when($request->filled('year'), static function ($builder) use ($request): void {
                    $builder->where('f.year', $request->integer('year'));
                })
                ->select([
                    'f.*',
                    'p.company_name',
                    'p.title as project_title',
                ])
                ->selectRaw('((f.year * 100) + f.month) as period_sort');

            $dataTable = DataTables::of($query)
                ->addColumn('period', static fn ($row): string => sprintf('%04d/%02d', (int) $row->year, (int) $row->month).' — '.(['annual' => 'سالانه', 'quarterly' => 'فصلی', 'legacy' => 'قدیمی'][$row->period_type] ?? 'قدیمی'))
                ->editColumn('company_name', static fn ($row): string => (string) ($row->company_name ?: $row->project_title ?: '—'))
                ->filterColumn('company_name', static function ($builder, string $keyword): void {
                    $builder->where(static function ($companyQuery) use ($keyword): void {
                        $companyQuery->where('p.company_name', 'like', '%'.$keyword.'%')
                            ->orWhere('p.title', 'like', '%'.$keyword.'%');
                    });
                });

            return $dataTable
                ->addColumn('action', function ($row): string {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect';
                    $buttons = '<div class="d-inline-flex align-items-center gap-1">';

                    $buttons .= '<button type="button" class="'.$base.' btn-outline-secondary statement-view-btn" title="مشاهده جزئیات" aria-label="مشاهده جزئیات"><i class="mdi mdi-eye-outline"></i></button>';

                    if (Gate::allows('can-access', ['financialstatement', 'edit'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-primary edit-btn" data-id="'.$row->id.'" data-url="'.route('financialstatement.edit', $row->id).'" title="ویرایش صورت مالی" aria-label="ویرایش صورت مالی"><i class="mdi mdi-pencil-outline"></i></button>';
                    }

                    if (Gate::allows('can-access', ['financialstatement', 'delete'])) {
                        $buttons .= '<button type="button" class="'.$base.' btn-outline-danger delete-btn" data-id="'.$row->id.'" data-url="'.route('financialstatement.destroy', $row->id).'" title="حذف صورت مالی" aria-label="حذف صورت مالی"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    return $buttons.'</div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $thispage = [
            'title' => 'مدیریت صورت‌های مالی شرکت‌های پورتفو',
            'list' => 'صورت‌های مالی ثبت‌شده',
            'add' => 'ثبت صورت مالی',
            'create' => 'ثبت صورت مالی',
            'enter' => 'ورود صورت مالی',
            'edit' => 'ویرایش صورت مالی',
            'delete' => 'حذف صورت مالی',
        ];
        $menupanels = MenuPanel::select('id', 'priority', 'icon', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller', 'menu_id')->get();
        $projects = $this->portfolioProjects();
        $years = Financial_statement::query()
            ->whereIn('project_id', $projects->pluck('id'))
            ->whereNotNull('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');
        $fieldGroups = Financial_statement::fieldGroups();

        return view('panel.financialstatement', compact(
            'menupanels',
            'submenupanels',
            'thispage',
            'projects',
            'years',
            'fieldGroups'
        ));
    }

    public function store(FinancialStatementRequest $request, ActivityLogService $activity): JsonResponse
    {
        try {
            $statement = DB::transaction(function () use ($request) {
                $statement = Financial_statement::query()->create($request->validated());
                app(BusinessAudit::class)->record($statement, 'created');
                return $statement;
            });
            $activity->record('financial_statement.created', 'صورت مالی #'.$statement->id.' ثبت شد.');

            return $this->success('صورت مالی با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ثبت صورت مالی انجام نشد. لطفاً مجدداً تلاش نمایید.');
        }
    }

    public function edit(int $id)
    {
        $financialstatement = $this->findPortfolioStatementOrFail($id);
        $projects = $this->portfolioProjects();
        $fieldGroups = Financial_statement::fieldGroups();

        return view('panel.partials.edit-form-financialstatment', compact('financialstatement', 'projects', 'fieldGroups'));
    }

    public function update(FinancialStatementRequest $request, int $id, ActivityLogService $activity): JsonResponse
    {
        try {
            $statement = DB::transaction(function () use ($request, $id) {
                $statement = $this->findPortfolioStatementOrFail($id, true);
                $before = $statement->getAttributes();
                $statement->update($request->validated());
                app(BusinessAudit::class)->record($statement, 'updated', $before);
                return $statement;
            });
            $activity->record('financial_statement.updated', 'صورت مالی #'.$statement->id.' ویرایش شد.');

            return $this->success('صورت مالی با موفقیت ویرایش شد.');
        } catch (ModelNotFoundException) {
            return $this->failure('صورت مالی موردنظر یافت نشد.', 404);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('ویرایش صورت مالی انجام نشد. لطفاً مجدداً تلاش نمایید.');
        }
    }

    public function destroy(int $id, ActivityLogService $activity): JsonResponse
    {
        try {
            DB::transaction(function () use ($id) {
                $statement = $this->findPortfolioStatementOrFail($id, true);
                app(BusinessAudit::class)->record($statement, 'deleted', $statement->getAttributes());
                $statement->delete();
            });
            $activity->record('financial_statement.deleted', 'صورت مالی #'.$id.' حذف شد.');

            return $this->success('صورت مالی با موفقیت حذف شد.');
        } catch (ModelNotFoundException) {
            return $this->failure('صورت مالی موردنظر یافت نشد.', 404);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('حذف صورت مالی انجام نشد. لطفاً مجدداً تلاش نمایید.');
        }
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

    private function failure(string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'subject' => 'خطا در ارتباط با سرور',
            'flag' => 'error',
            'message' => $message,
        ], $status);
    }

    private function portfolioProjects(): Collection
    {
        return Project::query()
            ->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)
            ->orderByRaw('COALESCE(NULLIF(company_name, \'\'), title)')
            ->get(['id', 'title', 'company_name']);
    }

    private function findPortfolioStatementOrFail(int $id, bool $lock = false): Financial_statement
    {
        return Financial_statement::query()
            ->whereKey($id)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->whereHas('project', static fn ($query) => $query->where(
                'invest_step',
                '>=',
                Project::PORTFOLIO_MINIMUM_STEP
            ))
            ->firstOrFail();
    }
}
