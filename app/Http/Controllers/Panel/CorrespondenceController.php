<?php

namespace App\Http\Controllers\Panel;

use App\Events\CorrespondenceRefresh;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRecipient;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\InternalNotificationService;
use App\Services\InvestmentWorkflowAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class CorrespondenceController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('panel.correspondence', [
            'conversations' => $this->conversationQuery((int) $user->id)->get(),
            'thispage' => [
                'title' => 'مدیریت مکاتبات',
                'list' => 'لیست مکاتبات',
                'add' => 'افزودن مکاتبات',
                'create' => 'ایجاد مکاتبات',
                'enter' => 'ورود مکاتبات',
                'edit' => 'ویرایش مکاتبات',
                'delete' => 'حذف مکاتبات',
            ],
            'users' => User::query()
                ->select('id', 'name')
                ->where('status', 4)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function data(): JsonResponse
    {
        $userId = (int) auth()->id();
        $users = User::query()->select('id', 'name')->where('status', 4)->orderBy('name')->get();
        $conversations = $this->conversationQuery($userId)->get();

        return response()->json([
            'authUserId' => $userId,
            'users' => $users->mapWithKeys(fn (User $user) => [
                $user->id => ['id' => $user->id, 'name' => $user->name],
            ]),
            'conversations' => $conversations->map(fn (Conversation $conversation) => $this->mapConversation($conversation))->values(),
        ]);
    }

    public function store(
        Request $request,
        InternalNotificationService $notifications,
        ActivityLogService $activity
    ): JsonResponse {
        $data = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'subject' => ['required_without:conversation_id', 'nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'recipients' => ['required_without:conversation_id', 'nullable', 'array', 'min:1'],
            'recipients.*' => ['integer', 'distinct', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'exists:messages,id'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'max:20480',
                'mimetypes:'.implode(',', config('investment.documents.allowed_mimes', [])),
            ],
        ]);

        $result = DB::transaction(function () use ($data, $request): array {
            if (empty($data['conversation_id'])) {
                $conversation = Conversation::query()->create([
                    'subject' => $data['subject'],
                    'type' => 'internal',
                ]);

                $participantIds = collect($data['recipients'] ?? [])
                    ->push(auth()->id())
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $conversation->users()->attach(
                    $participantIds->mapWithKeys(fn (int $id) => [$id => ['unread_count' => 0]])->all()
                );
            } else {
                $conversation = Conversation::query()
                    ->whereHas('users', fn (Builder $query) => $query->whereKey(auth()->id()))
                    ->findOrFail($data['conversation_id']);
            }

            if (! empty($data['parent_id'])) {
                $validParent = Message::query()
                    ->whereKey($data['parent_id'])
                    ->where('conversation_id', $conversation->id)
                    ->exists();

                if (! $validParent) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'پیام مرجع متعلق به این مکاتبه نیست.',
                    ]);
                }
            }

            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => auth()->id(),
                'parent_id' => $data['parent_id'] ?? null,
                'body' => $data['body'],
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $disk = (string) config('investment.documents.disk', 'investment_documents');
                    $extension = strtolower((string) ($file->guessExtension() ?: 'bin'));
                    $name = Str::uuid().'.'.preg_replace('/[^a-z0-9]/', '', $extension);
                    $path = $file->storeAs("correspondence/{$conversation->id}/{$message->id}", $name, $disk);
                    $scanStatus = config('investment.documents.antivirus_enabled') ? 'pending' : 'clean';

                    MessageAttachment::query()->create([
                        'message_id' => $message->id,
                        'path' => $path,
                        'disk' => $disk,
                        'original_name' => preg_replace(
                            '/[\x00-\x1F\x7F]/u',
                            '',
                            basename($file->getClientOriginalName())
                        ) ?: 'attachment',
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'sha256' => hash_file('sha256', $file->getRealPath()) ?: null,
                        'scan_status' => $scanStatus,
                        'scanned_at' => $scanStatus === 'clean' ? now() : null,
                    ]);
                }
            }

            $recipientIds = $conversation->users()
                ->where('users.id', '!=', auth()->id())
                ->pluck('users.id')
                ->map(fn ($id) => (int) $id)
                ->values();

            foreach ($recipientIds as $recipientId) {
                MessageRecipient::query()->firstOrCreate([
                    'message_id' => $message->id,
                    'user_id' => $recipientId,
                ], [
                    'read_at' => null,
                ]);
            }

            DB::table('conversation_user')
                ->where('conversation_id', $conversation->id)
                ->whereIn('user_id', $recipientIds->all())
                ->increment('unread_count');

            $conversation->touch();

            return [
                'conversation' => $conversation,
                'message' => $message->fresh(['sender:id,name', 'attachments']),
                'recipient_ids' => $recipientIds,
            ];
        });

        $recipients = User::query()->whereIn('id', $result['recipient_ids'])->get();
        $notifications->send($recipients, [
            'title' => 'مکاتبه جدید',
            'message' => $result['conversation']->subject,
            'url' => route('correspondence.index'),
            'icon' => 'mdi-email-outline',
            'category' => 'correspondence',
            'actor_id' => auth()->id(),
        ], auth()->id());

        $activity->record('correspondence.message_sent', "پیام در مکاتبه «{$result['conversation']->subject}» ارسال شد.");

        broadcast(new MessageSent($result['message']))->toOthers();
        broadcast(new CorrespondenceRefresh((int) $result['conversation']->id));

        return response()->json([
            'conversation_id' => $result['conversation']->id,
            'message_id' => $result['message']->id,
        ], 201);
    }

    public function markRead(int $conversation): JsonResponse
    {
        $conversationModel = Conversation::query()
            ->whereHas('users', fn (Builder $query) => $query->whereKey(auth()->id()))
            ->findOrFail($conversation);

        DB::transaction(function () use ($conversationModel): void {
            DB::table('conversation_user')
                ->where('conversation_id', $conversationModel->id)
                ->where('user_id', auth()->id())
                ->update([
                    'unread_count' => 0,
                    'last_read_at' => now(),
                    'updated_at' => now(),
                ]);

            MessageRecipient::query()
                ->where('user_id', auth()->id())
                ->whereNull('read_at')
                ->whereHas('message', fn (Builder $query) => $query->where('conversation_id', $conversationModel->id))
                ->update(['read_at' => now()]);
        });

        return response()->json(['success' => true]);
    }

    public function show(Request $request, int $id)
    {
        abort_unless(app(InvestmentWorkflowAccessService::class)->canViewProject($request->user(), $id), 403);
        $project = Project::query()->findOrFail($id);
        abort_unless($request->ajax(), 404);

        $firstAttachment = DB::table('message_attachments')
            ->select('message_id', DB::raw('MIN(id) as attachment_id'))
            ->groupBy('message_id');

        $data = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->whereExists(function ($query) use ($request): void {
                $query->selectRaw('1')->from('conversation_user as viewer')
                    ->whereColumn('viewer.conversation_id', 'c.id')
                    ->where('viewer.user_id', $request->user()->getKey());
            })
            ->join('conversation_user as cu', function ($join) use ($project) {
                $join->on('cu.conversation_id', '=', 'c.id')
                    ->where('cu.user_id', '=', $project->user_id);
            })
            ->leftJoin('users as u', 'u.id', '=', 'm.sender_id')
            ->leftJoinSub($firstAttachment, 'fa', fn ($join) => $join->on('fa.message_id', '=', 'm.id'))
            ->leftJoin('message_attachments as a', 'a.id', '=', 'fa.attachment_id')
            ->select([
                'm.id', 'c.subject', 'm.body', 'a.id as attachment_id', 'a.path as file_path', 'a.mime_type as type',
                'u.name as user', 'm.created_at as date',
            ]);

        return DataTables::of($data)
            ->editColumn('file_path', static function ($row): string {
                if (empty($row->file_path)) {
                    return '';
                }

                $fileUrl = route('message-attachments.download', ['attachment' => $row->attachment_id, 'inline' => 1]);
                $mime = (string) ($row->type ?? '');

                if (str_starts_with($mime, 'image/')) {
                    return '<img src="'.$fileUrl.'" width="80" alt="پیوست">';
                }
                if (str_starts_with($mime, 'audio/')) {
                    return '<audio controls><source src="'.$fileUrl.'" type="'.$mime.'"></audio>';
                }
                if (str_starts_with($mime, 'video/')) {
                    return '<video width="160" controls><source src="'.$fileUrl.'" type="'.$mime.'"></video>';
                }

                return '<a href="'.$fileUrl.'" target="_blank" rel="noopener">دانلود فایل</a>';
            })
            ->editColumn('subject', static fn ($row): string => e((string) $row->subject))
            ->editColumn('body', static fn ($row): string => e((string) $row->body))
            ->editColumn('user', static fn ($row): string => e((string) ($row->user ?? '')))
            ->editColumn('date', static fn ($row): string => $row->date ? jdate($row->date)->format('Y/m/d') : '')
            ->rawColumns(['file_path'])
            ->make(true);
    }

    private function conversationQuery(int $userId): Builder
    {
        return Conversation::query()
            ->select('conversations.*')
            ->selectSub(function ($query) use ($userId) {
                $query->from('conversation_user')
                    ->select('unread_count')
                    ->whereColumn('conversation_user.conversation_id', 'conversations.id')
                    ->where('conversation_user.user_id', $userId)
                    ->limit(1);
            }, 'current_unread_count')
            ->with([
                'users:id,name',
                'lastMessage.sender:id,name',
                'lastMessage.attachments',
                'messages' => fn ($query) => $query->with(['sender:id,name', 'attachments'])->orderBy('created_at'),
            ])
            ->whereHas('users', fn (Builder $query) => $query->whereKey($userId))
            ->latest('updated_at');
    }

    private function mapConversation(Conversation $conversation): array
    {
        $messages = $conversation->messages->sortBy('created_at')->values();
        $root = $messages->firstWhere('parent_id', null) ?? $messages->first();
        $replies = $root ? $messages->where('id', '!=', $root->id)->values() : collect();
        $last = $messages->last();

        $mapMessage = static fn (Message $message): array => [
            'id' => 'm'.$message->id,
            'senderId' => $message->sender_id,
            'body' => (string) $message->body,
            'time' => $message->created_at,
            'attachments' => $message->attachments->map(fn (MessageAttachment $attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'url' => $attachment->url,
            ])->all(),
        ];

        return [
            'id' => $conversation->id,
            'subject' => (string) $conversation->subject,
            'participants' => $conversation->users->pluck('id')->all(),
            'unread' => (int) ($conversation->current_unread_count ?? 0),
            'readUrl' => route('correspondence.read', $conversation->id),
            'lastActivity' => optional($last)->created_at ?? optional($conversation->lastMessage)->created_at,
            'messages' => [
                'root' => $root ? $mapMessage($root) : null,
                'replies' => $replies->map($mapMessage)->values()->all(),
            ],
        ];
    }
}
