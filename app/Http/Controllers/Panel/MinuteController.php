<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\MinuteRequest;
use App\Models\Minute;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\InternalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class MinuteController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $canEdit = Gate::allows('can-access', ['flow', 'edit']);

        $data = Minute::query()
            ->with('creator:id,name')
            ->where('project_id', $validated['id']);

        return DataTables::eloquent($data)
            ->editColumn('file_path', static function (Minute $minute): string {
                if (! $minute->file_path) {
                    return '';
                }

                return '<a href="'.asset('storage/'.$minute->file_path).'" target="_blank" rel="noopener">دانلود فایل</a>';
            })
            ->addColumn('creator_name', static fn (Minute $minute): string => e($minute->creator?->name ?? '—'))
            ->addColumn('action', static function (Minute $minute) use ($canEdit): string {
                if (! $canEdit) {
                    return '';
                }

                return '<button type="button" class="btn btn-sm btn-outline-primary minute-edit" data-id="'.$minute->id.'">ویرایش</button> '
                    .'<button type="button" class="btn btn-sm btn-outline-danger minute-delete" data-id="'.$minute->id.'">حذف</button>';
            })
            ->rawColumns(['file_path', 'action'])
            ->make(true);
    }

    public function edit(int $id): JsonResponse
    {
        $minute = Minute::query()->findOrFail($id);

        return response()->json($minute->only([
            'id', 'project_id', 'title', 'date', 'type', 'file_path',
        ]));
    }

    public function store(
        MinuteRequest $request,
        InternalNotificationService $notifications,
        ActivityLogService $activity
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $project = Project::query()->findOrFail($validated['project_id']);

            $minute = Minute::query()->create([
                'project_id' => $project->getKey(),
                'company_id' => $project->company_id,
                'created_by' => auth()->id(),
                'title' => $validated['title'],
                'date' => $validated['date'] ?? null,
                'type' => $validated['type'] ?? null,
                'file_path' => $validated['file_path'] ?? null,
            ]);

            if ($project->user_id && (int) $project->user_id !== (int) auth()->id()) {
                $recipient = User::query()->find($project->user_id);
                if ($recipient) {
                    $notifications->send([$recipient], [
                        'title' => 'صورتجلسه جدید',
                        'message' => $minute->title,
                        'url' => route('profile'),
                        'icon' => 'mdi-file-document-outline',
                        'category' => 'minute',
                        'actor_id' => auth()->id(),
                    ]);
                }
            }

            $activity->record('minute.created', "صورتجلسه «{$minute->title}» برای پروژه #{$project->id} ثبت شد.");

            return response()->json([
                'success' => true,
                'subject' => 'عملیات موفق',
                'flag' => 'success',
                'message' => 'صورتجلسه با موفقیت ثبت شد.',
            ]);
        } catch (Throwable $exception) {
            Log::error('Minute store failed.', [
                'exception' => $exception,
                'project_id' => $validated['project_id'],
            ]);

            return response()->json([
                'success' => false,
                'subject' => 'خطای سرور',
                'flag' => 'error',
                'message' => 'صورتجلسه ثبت نشد، لطفاً بعداً مجدداً تلاش نمایید.',
            ], 500);
        }
    }

    public function update(MinuteRequest $request, int $id, ActivityLogService $activity): JsonResponse
    {
        $validated = $request->validated();
        $minute = Minute::query()->findOrFail($id);
        $project = Project::query()->findOrFail($validated['project_id']);

        abort_unless((int) $minute->project_id === (int) $project->id, 404);

        $minute->update([
            'company_id' => $project->company_id,
            'title' => $validated['title'],
            'date' => $validated['date'] ?? null,
            'type' => $validated['type'] ?? null,
            'file_path' => $validated['file_path'] ?? null,
        ]);

        $activity->record('minute.updated', "صورتجلسه «{$minute->title}» ویرایش شد.");

        return response()->json([
            'success' => true,
            'subject' => 'عملیات موفق',
            'flag' => 'success',
            'message' => 'صورتجلسه با موفقیت ویرایش شد.',
        ]);
    }

    public function destroy(int $id, ActivityLogService $activity): JsonResponse
    {
        $minute = Minute::query()->findOrFail($id);
        $title = $minute->title;
        $minute->delete();
        $activity->record('minute.deleted', "صورتجلسه «{$title}» حذف شد.");

        return response()->json(['success' => true]);
    }
}
