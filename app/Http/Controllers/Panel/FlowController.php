<?php

namespace App\Http\Controllers\Panel;

use App\Enums\InvestmentRole;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Commitment;
use App\Models\CompanyMembers;
use App\Models\Finance;
use App\Models\Financial_statement;
use App\Models\Investstep;
use App\Models\KPI;
use App\Models\MediaFile;
use App\Models\MenuPanel;
use App\Models\Message;
use App\Models\Project;
use App\Models\Project_step;
use App\Models\ProjectAssignment;
use App\Models\ProjectCommitment;
use App\Models\Role;
use App\Models\State;
use App\Models\subject_file;
use App\Models\SubmenuPanel;
use App\Models\User;
use App\Services\FinancialStatementMetricsService;
use App\Services\InvestmentWorkflowAccessService;
use App\Services\InvestmentWorkflowService;
use App\Services\ProjectDeletionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class FlowController extends Controller
{
    public function index(Request $request)
    {
        $submenupanels = SubmenuPanel::select('id', 'priority', 'title', 'label', 'menu_id', 'slug', 'status', 'class', 'controller')->get();
        $menupanels = MenuPanel::select('id', 'priority', 'title', 'label', 'slug', 'status', 'class', 'controller')->get();
        $states = State::all();
        $cities = City::all();

        $thispage = [
            'title' => 'مدیریت طرح / شرکت  ',
            'list' => 'لیست طرح ها و شرکت ها  ',
            'add' => 'افزودن طرح / شرکت  ',
            'create' => 'ایجاد طرح / شرکت  ',
            'enter' => 'ورود طرح / شرکت  ',
            'edit' => 'ویرایش اطلاعات طرح / شرکت  ',
            'upload' => 'بارگزاری فایل طرح / شرکت  ',
            'delete' => 'حذف طرح / شرکت  ',
        ];

        if ($request->ajax()) {
            $data = DB::table('projects as p')
                ->leftJoin('investsteps as current_step', 'current_step.id', '=', 'p.invest_step')
                ->select(
                    'p.id',
                    'p.title',
                    'p.company_name',
                    'p.CEO',
                    'current_step.title as flow_level',
                    'p.start_date',
                    'p.invest_step',
                    'p.progress_percentage',
                    'p.percentageshare',
                    'p.amount_request_accept',
                    'p.is_rejected',
                    'p.reject_step',
                    'p.created_at',
                    DB::raw('(SELECT COALESCE(SUM(f.amount),0) FROM finances f WHERE f.project_id = p.id) as total_payment')
                );

            app(InvestmentWorkflowAccessService::class)
                ->scopeWorkflowProjects($data, $request->user(), 'p');

            return DataTables::of($data)
                ->addColumn('title', function ($data) {
                    return $data->title;
                })
                ->addColumn('CEO', function ($data) {
                    return $data->CEO;
                })
                ->addColumn('company_name', function ($data) {
                    return $data->company_name;
                })
                ->addColumn('flow_level', function ($data) {
                    $flow = $data->flow_level;

                    if ($data->reject_step == 1) {
                        $flow .= ' <span style="color:red;">&#9940;</span>'; // علامت عبور ممنوع ❌
                    }

                    return $flow;
                })
                ->addColumn('percentageshare', function ($data) {
                    return $data->percentageshare.'%';
                })
                ->addColumn('invest_step', function ($data) {
                    $percent = max(0, min(100, (int) ($data->progress_percentage ?? 0)));

                    return '
                        <div class="d-flex align-items-center" style="min-width:120px;">
                            <div class="progress flex-grow-1" style="height: 8px; background:#e7e7e7; border-radius:4px;">
                                <div class="progress-bar bg-primary"
                                    role="progressbar"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuenow="'.$percent.'"
                                    style="width: '.$percent.'%; border-radius:4px;">
                                </div>
                            </div>
                            <span class="ms-2" style="font-size: 0.85rem;">'.$percent.'%</span>
                        </div>
                    ';
                })
                ->addColumn('start_date', function ($data) {
                    return $data->start_date;
                })
                ->addColumn('amount_request_accept', function ($data) {
                    return number_format($data->amount_request_accept);
                })
                ->addColumn('amount_deposited', function ($data) {
                    return number_format($data->total_payment);
                })
                ->addColumn('commitment_balance', function ($data) {
                    return number_format($data->amount_request_accept - $data->total_payment);
                })
                ->addColumn('created_at', function ($data) {
                    return jdate($data->created_at)->format('Y-m') ?? 0;
                })
                ->editColumn('action', function ($data) {
                    $base = 'btn btn-sm btn-icon rounded-pill waves-effect mx-1';

                    $actionBtn = '';
                    if (auth()->user()->can('can-access', ['project', 'edit'])) {
                        $actionBtn .= '<button type="button" class="'.$base.' btn btn-sm btn-outline-primary edit-btn" data-id="'.$data->id.'" data-url="'.route('flow.edit', $data->id).'"><i class="mdi mdi-pencil-outline"></i></button>';

                    }
                    if (auth()->user()->can('can-access', ['flow', 'delete'])) {
                        $actionBtn .= '<button type="button" class="'.$base.' btn btn-sm btn-icon btn-outline-danger mx-1 delete-btn" data-id="'.$data->id.'"><i class="mdi mdi-delete-outline"></i></button>';
                    }
                    $actionBtn .= '<button type="button" class="'.$base.' btn btn-sm btn-outline-primary show-btn" data-id="'.$data->id.'" data-url="'.route('flow.show', $data->id).'"><i class="mdi mdi-eye"></i></button>';

                    $actionBtn .= '<button class="'.$base.' btn btn-sm btn-icon btn-image mx-1 upload-btn" data-id="'.$data->id.'"><i class="mdi mdi-file-document-multiple-outline"></i></button>';

                    return $actionBtn;
                })
                ->rawColumns(['action', 'invest_step', 'flow_level'])
                ->make(true);
        }

        return view('panel.flow')->with(compact(['thispage', 'submenupanels', 'menupanels', 'states', 'cities']));
    }

    public function store(Request $request, InvestmentWorkflowService $workflowService)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'step_id' => ['required', 'integer', 'exists:investsteps,id'],
            'status' => ['required', 'in:approved,rejected'],
            'description' => ['nullable', 'required_if:status,rejected', 'string', 'max:5000'],
        ]);

        try {
            $projectStep = $workflowService->transition(
                (int) $validated['project_id'],
                (int) $validated['step_id'],
                $validated['status'],
                $validated['description'] ?? null,
                $request->user()
            );

            $project = Project::query()->findOrFail($validated['project_id']);

            return response()->json([
                'success' => true,
                'flag' => 'success',
                'subject' => 'عملیات موفق',
                'message' => 'نتیجه مرحله سرمایه‌گذاری با موفقیت ثبت شد.',
                'data' => [
                    'project_step_id' => $projectStep->getKey(),
                    'invest_step' => (int) $project->invest_step,
                    'is_rejected' => (bool) $project->is_rejected,
                    'reject_step' => $project->reject_step,
                ],
            ]);
        } catch (ValidationException|AuthorizationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Investment workflow transition failed.', [
                'project_id' => $validated['project_id'],
                'step_id' => $validated['step_id'],
                'user_id' => $request->user()?->getKey(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'flag' => 'error',
                'subject' => 'خطای سرور',
                'message' => 'ثبت نتیجه مرحله انجام نشد. لطفاً مجدداً تلاش نمایید.',
            ], 500);
        }
    }

    public function edit($id)
    {
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject(auth()->user(), (int) $id), 403);
        abort_unless(auth()->user()->can('can-access', ['project', 'edit']), 403);
        $project = Project::findOrFail($id);
        $states = State::all();
        $cities = City::all();

        return view('panel.partials.edit-form', compact('project', 'states', 'cities'));
    }

    public function show(Request $request, int $id, FinancialStatementMetricsService $financialMetrics)
    {
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject($request->user(), $id), 403);
        $project = Project::query()
            ->with(['company', 'currentStep'])
            ->findOrFail($id);

        $finances = Finance::query()->where('project_id', $id)->orderBy('serial')->get();
        $states = State::all();
        $cities = City::all();
        $kpis = KPI::query()
            ->with('reviewedBy:id,name')
            ->where('project_id', $id)
            ->orderBy('kpi_number')
            ->orderByDesc('revision_number')
            ->orderByDesc('id')
            ->get();
        $investsteps = Investstep::query()
            ->whereStatus(4)
            ->with(['documentRequirements.subject'])
            ->orderBy('id')
            ->get();
        $files = MediaFile::query()
            ->with('subject:id,title')
            ->where('project_id', $id)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 5);
            })
            ->orderByDesc('id')
            ->get();
        $commitmentTemplates = Commitment::query()->whereStatus(4)->orderBy('id')->get();
        $projectCommitments = ProjectCommitment::query()
            ->with('commitment')
            ->where('project_id', $id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->get();
        $members = CompanyMembers::query()
            ->where('project_id', $id)
            ->orderByDesc('is_active')
            ->orderBy('full_name')
            ->get();

        $currentInvestStep = $investsteps->firstWhere('id', (int) $project->invest_step);
        $currentStepRequirements = $currentInvestStep?->documentRequirements ?? collect();
        $requirementSubjectIds = $currentStepRequirements->pluck('subject_file_id');
        $availableSubjectFiles = subject_file::query()
            ->when($requirementSubjectIds->isNotEmpty(), static fn ($query) => $query->whereNotIn('id', $requirementSubjectIds))
            ->orderBy('title')
            ->get(['id', 'title']);
        $requirementFileCounts = MediaFile::query()
            ->where('project_id', $id)
            ->whereIn('subject_id', $requirementSubjectIds)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 5);
            })
            ->selectRaw('subject_id, COUNT(*) as aggregate')
            ->groupBy('subject_id')
            ->pluck('aggregate', 'subject_id');
        $project_steps = Project_step::leftJoin('users', 'project_steps.user_id', '=', 'users.id')
            ->leftJoin('roles as actor_roles', 'project_steps.actor_role_id', '=', 'actor_roles.id')
            ->where('project_steps.project_id', $id)
            ->whereIn('project_steps.id', Project_step::query()
                ->selectRaw('MAX(id)')
                ->where('project_id', $id)
                ->groupBy('step_number'))
            ->select('project_steps.*', 'users.name as username', 'actor_roles.title_fa as actor_role_title')
            ->orderBy('project_steps.step_number')
            ->get();
        $contracts = $project->contracts()
            ->withCount(['currentKpis as kpis_count'])
            ->orderByDesc('starts_at')
            ->get();
        $quarterlyReports = $project->quarterlyPerformanceReports()
            ->where('is_current', true)
            ->withCount('measurements')
            ->orderByDesc('year')
            ->orderByDesc('quarter')
            ->get();

        $workflowAccess = app(InvestmentWorkflowAccessService::class);
        $canManageAssignments = $workflowAccess->canManageStage(
            auth()->user(),
            (int) $project->invest_step
        );
        $canManageInvestmentRecords = $workflowAccess->canManageInvestmentDepartment(auth()->user())
            && ($workflowAccess->canManageAssignments(auth()->user())
                || (int) $project->invest_step < Project::PORTFOLIO_MINIMUM_STEP);
        $canManagePortfolioRecords = $workflowAccess->canManagePortfolioDepartment(auth()->user())
            && ($workflowAccess->canManageAssignments(auth()->user())
                || (int) $project->invest_step >= Project::PORTFOLIO_MINIMUM_STEP);
        $canReviewKpis = $workflowAccess->canReviewKpis($request->user(), $project->getKey());
        $canTransitionCurrentStep = $workflowAccess->canTransition(
            auth()->user(),
            $project->id,
            (int) $project->invest_step
        );
        $projectAssignments = ProjectAssignment::query()
            ->with(['user:id,name,email', 'role:id,title_fa,title', 'investStep:id,title', 'assignedBy:id,name'])
            ->where('project_id', $id)
            ->orderByDesc('is_active')
            ->orderByDesc('assigned_at')
            ->get();

        $assignableRoles = collect();
        $assignableUsers = collect();
        if ($canManageAssignments) {
            $assignableRoles = Role::query()
                ->whereIn('title', InvestmentRole::assignableReviewRoleSlugs())
                ->where('status', 4)
                ->orderBy('title_fa')
                ->get(['id', 'title_fa', 'title']);

            $assignableUsers = User::query()
                ->where('level', 'admin')
                ->where('status', 4)
                ->whereHas('roles', static function ($query) {
                    $query->whereIn('roles.title', InvestmentRole::assignableReviewRoleSlugs())
                        ->where(function ($statusQuery) {
                            $statusQuery->whereNull('roles.status')->orWhere('roles.status', 4);
                        });
                })
                ->with(['roles' => static function ($query) {
                    $query->whereIn('roles.title', InvestmentRole::assignableReviewRoleSlugs())
                        ->select('roles.id', 'roles.title_fa', 'roles.title');
                }])
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        $financialStatements = $project->financialStatements()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();
        $financialReport = $financialMetrics->build($financialStatements);
        $financialFieldGroups = Financial_statement::fieldGroups();
        $recentMessages = collect();
        if ($project->user_id) {
            $recentMessages = Message::query()
                ->whereHas('conversation.users', static fn ($query) => $query->whereKey($project->user_id))
                ->with(['conversation:id,subject', 'sender:id,name'])
                ->latest('id')
                ->limit(10)
                ->get();
        }

        $viewData = compact(
            'project', 'states', 'cities', 'project_steps',
            'kpis', 'investsteps', 'files', 'commitmentTemplates', 'projectCommitments', 'finances', 'members', 'projectAssignments',
            'assignableRoles', 'assignableUsers', 'canManageAssignments', 'canTransitionCurrentStep',
            'canManageInvestmentRecords', 'canManagePortfolioRecords', 'canReviewKpis',
            'currentInvestStep', 'currentStepRequirements', 'availableSubjectFiles', 'requirementFileCounts',
            'contracts', 'quarterlyReports', 'financialStatements', 'financialReport', 'financialFieldGroups', 'recentMessages'
        );

        if ($request->ajax()) {
            return view('panel.partials.show-profile', $viewData);
        }

        return view('panel.project-overview', [
            ...$viewData,
            'thispage' => [
                'title' => 'نمای جامع شرکت',
                'list' => $project->company?->company_name ?: ($project->company_name ?: $project->title),
            ],
        ]);
    }

    public function destroy(int $id, ProjectDeletionService $deletion)
    {
        abort_unless(auth()->user()->can('can-access', ['project', 'delete']), 403);
        abort_unless(app(InvestmentWorkflowAccessService::class)->canManageAssignments(auth()->user()), 403);
        $deletion->delete($id);

        return response()->json(['success' => true, 'message' => 'پرونده حذف شد.']);
    }
}
