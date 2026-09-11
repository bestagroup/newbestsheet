<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProjectRequest;
use App\Models\Company;
use App\Models\MenuPanel;
use App\Models\Project;
use App\Models\State;
use App\Models\SubmenuPanel;
use App\Services\InvestmentWorkflowAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $financeSub = DB::table('finances')
                ->select(
                    'project_id',
                    DB::raw('MAX(CASE WHEN serial = 1 THEN amount END) as first_stage_payment'),
                    DB::raw('MAX(CASE WHEN serial = 2 THEN amount END) as second_stage_payment'),
                    DB::raw('MAX(CASE WHEN serial = 3 THEN amount END) as third_stage_payment'),
                    DB::raw('MAX(CASE WHEN serial = 4 THEN amount END) as fourth_stage_payment'),
                    DB::raw('MAX(CASE WHEN serial = 5 THEN amount END) as fifth_stage_payment')
                )
                ->groupBy('project_id');

            $data = DB::table('projects as p')
                ->leftJoinSub($financeSub, 'f', function ($join) {
                    $join->on('p.id', '=', 'f.project_id');
                })
                ->leftJoin('investsteps as current_step', 'current_step.id', '=', 'p.invest_step')
                ->select(
                    'p.id',
                    'p.title as commercial_name',
                    'p.CEO',
                    'p.portfo_status',
                    'current_step.title as flow_level',
                    'p.progress_percentage',
                    'p.activity_status',
                    'p.start_date',
                    'p.amount_request_accept',
                    'p.amount_commitment_first_stage',
                    'p.amount_commitment_second_stage',
                    'p.amount_commitment_third_stage',
                    'p.amount_commitment_fourth_stage',
                    'p.amount_commitment_fifth_stage',
                    'f.first_stage_payment',
                    'f.second_stage_payment',
                    'f.third_stage_payment',
                    'f.fourth_stage_payment',
                    'f.fifth_stage_payment',
                    'p.invest_step'
                );
            app(InvestmentWorkflowAccessService::class)
                ->scopeWorkflowProjects($data, $request->user(), 'p');

            return DataTables::of($data)
                ->editColumn('progress_percentage', static fn ($row): string => ((int) ($row->progress_percentage ?? 0)).'%')
                ->editColumn('amount_request_accept', static fn ($row): string => number_format((float) ($row->amount_request_accept ?? 0)))
                ->editColumn('amount_commitment_first_stage', static fn ($row): string => number_format((float) ($row->amount_commitment_first_stage ?? 0)))
                ->editColumn('amount_commitment_second_stage', static fn ($row): string => number_format((float) ($row->amount_commitment_second_stage ?? 0)))
                ->editColumn('amount_commitment_third_stage', static fn ($row): string => number_format((float) ($row->amount_commitment_third_stage ?? 0)))
                ->editColumn('amount_commitment_fourth_stage', static fn ($row): string => number_format((float) ($row->amount_commitment_fourth_stage ?? 0)))
                ->editColumn('amount_commitment_fifth_stage', static fn ($row): string => number_format((float) ($row->amount_commitment_fifth_stage ?? 0)))
                ->editColumn('first_stage_payment', static fn ($row): string => number_format((float) ($row->first_stage_payment ?? 0)))
                ->editColumn('second_stage_payment', static fn ($row): string => number_format((float) ($row->second_stage_payment ?? 0)))
                ->editColumn('third_stage_payment', static fn ($row): string => number_format((float) ($row->third_stage_payment ?? 0)))
                ->editColumn('fourth_stage_payment', static fn ($row): string => number_format((float) ($row->fourth_stage_payment ?? 0)))
                ->editColumn('fifth_stage_payment', static fn ($row): string => number_format((float) ($row->fifth_stage_payment ?? 0)))
                ->addColumn('amount_deposited', static function ($row): string {
                    return number_format(
                        (float) ($row->first_stage_payment ?? 0)
                        + (float) ($row->second_stage_payment ?? 0)
                        + (float) ($row->third_stage_payment ?? 0)
                        + (float) ($row->fourth_stage_payment ?? 0)
                        + (float) ($row->fifth_stage_payment ?? 0)
                    );
                })
                ->addColumn('commitment_balance', static function ($row): string {
                    $paid = (float) ($row->first_stage_payment ?? 0)
                        + (float) ($row->second_stage_payment ?? 0)
                        + (float) ($row->third_stage_payment ?? 0)
                        + (float) ($row->fourth_stage_payment ?? 0)
                        + (float) ($row->fifth_stage_payment ?? 0);

                    return number_format((float) ($row->amount_request_accept ?? 0) - $paid);
                })
                ->addColumn('action', function ($row): string {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';
                    $action = '';

                    if (auth()->user()->can('can-access', ['project', 'edit'])) {
                        $action .= '<button type="button" class="btn btn-sm btn-icon btn-outline-primary mx-1 edit-btn" data-id="'.$row->id.'"><i class="mdi mdi-pencil-outline"></i></button>';
                    }

                    if (auth()->user()->can('can-access', ['project', 'delete'])) {
                        $action .= '<button class="'.$base.' btn btn-sm btn-icon btn-outline-danger mx-1 delete-btn" data-id="'.$row->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }

                    $action .= '<button type="button" class="'.$base.' btn-eye mx-1 show-btn" data-id="'.$row->id.'"><i class="mdi mdi-eye"></i></button>';

                    return $action;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'menu_id', 'slug', 'status', 'class', 'controller')->get();
        $menupanels = MenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $companies = Company::query()->select('id', 'company_name', 'commercial_name')->orderBy('company_name')->get();
        $states = State::query()->where('status', 4)->select('id', 'title')->orderBy('title')->get();

        $thispage = [
            'title' => 'مدیریت پروژه ها',
            'list' => 'لیست پروژه ها',
            'add' => 'افزودن پروژه ها',
            'create' => 'ایجاد پروژه ها',
            'enter' => 'ورود پروژه ها',
            'edit' => 'ویرایش پروژه ها',
            'delete' => 'حذف پروژه ها',
        ];

        return view('panel.project', compact('thispage', 'submenupanels', 'menupanels', 'companies', 'states'));
    }

    public function show(int $id): JsonResponse
    {
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject(auth()->user(), $id), 403);
        $project = Project::query()
            ->with([
                'company:id,company_name,commercial_name',
                'finances' => static fn ($query) => $query->select('id', 'project_id', 'amount', 'serial', 'date', 'description')->orderBy('serial'),
                'projectSteps' => static fn ($query) => $query->select('id', 'project_id', 'title', 'step_number', 'status', 'description', 'created_at')->orderBy('id'),
            ])
            ->findOrFail($id);

        return response()->json(['data' => $project]);
    }

    public function edit(int $id): JsonResponse
    {
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject(auth()->user(), $id), 403);
        $project = Project::query()->findOrFail($id);

        return response()->json(['data' => $project]);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        try {
            $data = $this->synchronizeCompanySnapshot($request->validated());
            Project::query()->create($data);

            return $this->successResponse('اطلاعات پروژه با موفقیت ثبت شد.');
        } catch (Throwable $exception) {
            Log::error('Project store failed.', ['exception' => $exception]);

            return $this->errorResponse('اطلاعات پروژه ثبت نشد، لطفاً بعداً مجدداً تلاش نمایید.');
        }
    }

    public function update(ProjectRequest $request, int $id): JsonResponse
    {
        try {
            $project = Project::query()->findOrFail($id);
            $project->fill($this->synchronizeCompanySnapshot($request->validated()))->save();
            $project->members()->update(['company_id' => $project->company_id]);

            return $this->successResponse('اطلاعات پروژه با موفقیت به‌روزرسانی شد.');
        } catch (Throwable $exception) {
            Log::error('Project update failed.', [
                'exception' => $exception,
                'project_id' => $id,
            ]);

            return $this->errorResponse('اطلاعات پروژه به‌روزرسانی نشد، لطفاً بعداً مجدداً تلاش نمایید.');
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        try {
            Project::query()->findOrFail($validated['id'])->delete();

            return $this->successResponse('اطلاعات پروژه با موفقیت حذف شد.');
        } catch (Throwable $exception) {
            Log::error('Project delete failed.', [
                'exception' => $exception,
                'project_id' => $validated['id'],
            ]);

            return $this->errorResponse('اطلاعات پروژه حذف نشد، لطفاً بعداً مجدداً تلاش نمایید.');
        }
    }

    private function synchronizeCompanySnapshot(array $data): array
    {
        if (empty($data['company_id'])) {
            return $data;
        }

        $company = Company::query()->findOrFail($data['company_id']);

        $data['company_name'] = $company->company_name;

        if (empty($data['CEO']) && $company->ceo_name) {
            $data['CEO'] = $company->ceo_name;
        }

        return $data;
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
