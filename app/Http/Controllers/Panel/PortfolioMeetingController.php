<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\PortfolioMeetingRequest;
use App\Models\PortfolioMeeting;
use App\Models\Project;
use App\Models\User;
use App\Services\EnterpriseRecordAccess;
use App\Services\GovernanceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PortfolioMeetingController extends Controller
{
    public function __construct(private readonly EnterpriseRecordAccess $access, private readonly GovernanceService $service) {}

    public function index(Request $request)
    {
        $projects = $this->access->projects($request->user())->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP)->get(['id', 'title']);
        $meetings = PortfolioMeeting::query()->with('project')->withCount(['resolutions', 'resolutions as pending_count' => fn ($q) => $q->where('status', 'pending')])
            ->whereIn('project_id', $projects->pluck('id'))->latest()->paginate(20);

        $attachments = $this->access->attachments($request->user());

        return view('panel.governance.index', compact('projects', 'meetings', 'attachments'));
    }

    public function store(PortfolioMeetingRequest $request)
    {
        $meeting = $this->service->save($request->validated(), $request->user());

        return redirect()->route('meetings.show', $meeting)->with('success', 'جلسه ثبت شد.');
    }

    public function show(Request $request, PortfolioMeeting $meeting)
    {
        $this->access->assertProject($request->user(), (int) $meeting->project_id);
        $meeting->load('project', 'resolutions.assignee', 'media');
        $users = User::query()->where('status', 4)->where('level', '!=', 'applicant')->get(['id', 'name']);

        $attachments = $this->access->attachments($request->user());

        return view('panel.governance.show', compact('meeting', 'users', 'attachments'));
    }

    public function update(PortfolioMeetingRequest $request, PortfolioMeeting $meeting)
    {
        $this->service->save($request->validated(), $request->user(), $meeting->id);

        return back()->with('success', 'جلسه ویرایش شد.');
    }

    public function transition(Request $request, PortfolioMeeting $meeting)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['held', 'finalized', 'cancelled'])], 'lock_version' => ['required', 'integer']]);
        $this->service->transition($meeting->id, $data['status'], $data['lock_version'], $request->user());

        return back()->with('success', 'وضعیت جلسه تغییر کرد.');
    }

    public function resolution(Request $request, PortfolioMeeting $meeting)
    {
        $data = $request->validate([
            'agenda_index' => ['required', 'integer', 'min:0'], 'body' => ['required', 'string', 'max:10000'],
            'assigned_to' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('status', 4)->where('level', '!=', 'applicant'))],
            'due_on' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $this->service->addResolution($meeting->id, $data, $request->user());

        return back()->with('success', 'مصوبه ثبت شد.');
    }

    public function complete(Request $request, PortfolioMeeting $meeting, int $resolution)
    {
        $data = $request->validate(['completion_note' => ['required', 'string', 'max:5000']]);
        $this->service->completeResolution($meeting->id, $resolution, $data['completion_note'], $request->user());

        return back()->with('success', 'اجرای مصوبه ثبت شد.');
    }
}
