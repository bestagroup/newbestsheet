<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\InvesteeCompanyProjectRequest;
use App\Http\Requests\Panel\ProfileUserRequest;
use App\Models\City;
use App\Models\Commitment;
use App\Models\Investstep;
use App\Models\Project_step;
use App\Models\State;
use App\Services\ActivityLogService;
use App\Services\InvesteePortalService;
use App\Services\InvestmentReminderService;
use App\Services\ProjectStageProvisioningService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function __construct(
        private readonly InvesteePortalService $portal,
        private readonly ActivityLogService $activityLog,
        private readonly InvestmentReminderService $reminders,
        private readonly ProjectStageProvisioningService $stageProvisioning,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $thispage = ['title' => 'حساب کاربری', 'list' => 'حساب کاربری'];
        $states = State::query()->select('id', 'title')->where('status', 4)->orderBy('title')->get();

        if ($user->level !== 'applicant') {
            return view('panel.profile', compact('thispage', 'states'));
        }

        try {
            $project = $this->portal->projectFor($user);
        } catch (ModelNotFoundException) {
            return view('panel.profile', compact('thispage', 'states'))
                ->with('portalWarning', 'برای این حساب هنوز پرونده سرمایه‌گذاری ایجاد نشده است.');
        }
        $this->stageProvisioning->ensureForProject($project);
        $project->refresh();
        $company = $project->company;

        $investsteps = Investstep::query()
            ->where('status', 4)
            ->with(['documentRequirements.subject'])
            ->orderByRaw('CASE WHEN sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $files = $project->mediaFiles()->with('subject')->latest('id')->get();
        $minutes = $project->minutes()->latest('id')->get();
        $members = $project->members()->orderByDesc('is_active')->orderBy('id')->get();
        $commitments = Commitment::query()->where('status', 4)->orderBy('id')->get();
        $projectCommitmentSchedules = $project->commitments()->with('commitment')->get()->keyBy('commitment_id');
        $operationalAlerts = $this->reminders->alertsForProject($project);
        $finances = $project->finances()->orderByDesc('date')->orderByDesc('id')->get();
        $financialStatements = $project->financialStatements()->orderByDesc('year')->orderByDesc('month')->get();
        $projectStepsByStep = Project_step::query()
            ->where('project_id', $project->getKey())
            ->latest('id')
            ->get()
            ->keyBy('step_number');
        $projectStageInstances = $project->stageInstances()
            ->with(['comments' => static function ($query) {
                $query->where('visibility', 'investee')->with('author:id,name');
            }])
            ->get()
            ->keyBy('invest_step_id');

        $contractFiles = $files->filter(static function ($file): bool {
            $title = (string) ($file->subject?->title ?? '');

            return Str::contains($title, ['قرارداد', 'توافق']);
        })->values();

        $cities = $project->state
            ? City::query()->select('id', 'title')->where('status', 4)->where('state_id', $project->state)->orderBy('title')->get()
            : collect();

        return view('panel.profile', compact(
            'thispage', 'states', 'cities', 'project', 'company', 'investsteps', 'files',
            'minutes', 'members', 'commitments', 'projectCommitmentSchedules', 'finances', 'financialStatements',
            'projectStepsByStep', 'projectStageInstances', 'contractFiles', 'operationalAlerts'
        ));
    }

    public function updateUser(ProfileUserRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->portal->assertInvestee($user);
        $user->fill($request->validated())->save();

        $this->activityLog->record(
            'investee.user_profile_updated',
            'اطلاعات حساب نماینده سرمایه‌پذیر به‌روزرسانی شد.',
            (int) $user->getKey()
        );

        return response()->json(['success' => true, 'message' => 'اطلاعات حساب با موفقیت به‌روزرسانی شد.']);
    }

    public function updateCompany(InvesteeCompanyProjectRequest $request): JsonResponse
    {
        $project = $this->portal->updateCompanyAndProject($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'اطلاعات شرکت و طرح با موفقیت به‌روزرسانی شد.',
            'project_id' => $project->getKey(),
            'company_id' => $project->company_id,
        ]);
    }
}
