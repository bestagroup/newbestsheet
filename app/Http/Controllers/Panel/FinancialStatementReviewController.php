<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Financial_statement;
use App\Models\Project;
use App\Services\FinancialStatementReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FinancialStatementReviewController extends Controller
{
    public function update(Request $request, int $id, string $action, FinancialStatementReviewService $service)
    {
        $data = $request->validate(['lock_version' => ['required', 'integer', 'min:1'], 'note' => ['nullable', 'string', 'max:2000']]);
        $row = $service->transition($id, $action, $request->user(), $data['lock_version'], $data['note'] ?? null);

        return response()->json(['success' => true, 'message' => 'وضعیت سند به‌روزرسانی شد.', 'status' => $row->workflow_status]);
    }

    public function show(int $id)
    {
        Gate::authorize('can-access', ['financialstatement', 'view']);
        $row = Financial_statement::whereHas('project', fn ($q) => $q->where('invest_step', '>=', Project::PORTFOLIO_MINIMUM_STEP))->findOrFail($id);

        $events = DB::table('business_audits')->where('subject_type', Financial_statement::class)->where('subject_id', $row->id)
            ->whereIn('action', ['review_submit', 'review_approve', 'review_return', 'review_revise'])->orderByDesc('id')->get()
            ->map(function ($event): array {
                $after = json_decode($event->after, true, flags: JSON_THROW_ON_ERROR);

                return ['action' => $event->action, 'actor_id' => $event->actor_id, 'created_at' => $event->created_at, 'revision' => $after['revision'] ?? null, 'note' => $after['review_note'] ?? null];
            });

        return response()->json(['revisions' => $row->approvedRevisions()->orderByDesc('revision')->get(), 'events' => $events]);
    }
}
