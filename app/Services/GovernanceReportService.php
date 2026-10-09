<?php

namespace App\Services;

use App\Models\MeetingResolution;
use App\Models\PortfolioMeeting;
use App\Models\ProjectContract;
use App\Models\User;

final class GovernanceReportService
{
    public function build(User $viewer, ?int $projectId = null): array
    {
        $ids = app(OperationalAnalyticsService::class)->visibleProjectIds($viewer, $projectId);
        $meetings = PortfolioMeeting::query()->whereIn('project_id', $ids);
        $resolutions = MeetingResolution::query()->whereHas('meeting', fn ($q) => $q->whereIn('project_id', $ids)->where('status', 'finalized'));
        $contracts = ProjectContract::query()->whereIn('project_id', $ids);

        return [
            'meetings_count' => (clone $meetings)->count(),
            'draft_meetings' => (clone $meetings)->whereIn('status', ['draft', 'held'])->count(),
            'open_resolutions' => (clone $resolutions)->where('status', 'pending')->count(),
            'overdue_resolutions' => (clone $resolutions)->where('status', 'pending')->whereDate('due_on', '<', today())->count(),
            'expired_active_contracts' => (clone $contracts)->where('status', 'active')->whereDate('ends_at', '<', today())->count(),
            'resolutions' => (clone $resolutions)->with(['meeting.project', 'assignee'])->where('status', 'pending')->orderBy('due_on')->limit(100)->get(),
            'contracts' => (clone $contracts)->with('project')->where('status', 'active')->whereDate('ends_at', '<=', today()->addDays(30))->orderBy('ends_at')->limit(100)->get(),
        ];
    }
}
