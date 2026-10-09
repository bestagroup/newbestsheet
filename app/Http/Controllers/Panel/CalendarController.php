<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CalendarEventRequest;
use App\Models\Calendar;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\GoogleCalendarSyncService;
use App\Services\OperationalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class CalendarController extends Controller
{
    public function index()
    {
        $thispage = [
            'title' => 'مدیریت رویداد تقویم',
            'list' => 'لیست رویداد تقویم',
            'add' => 'افزودن رویداد تقویم',
            'create' => 'ایجاد رویداد تقویم',
            'enter' => 'ورود رویداد تقویم',
            'edit' => 'ویرایش رویداد تقویم',
            'delete' => 'حذف رویداد تقویم',
        ];

        $users = User::query()->select('id', 'name', 'email', 'gender')->where('status', 4)->orderBy('name')->get();

        return view('panel.calendar', compact('thispage', 'users'));
    }

    public function getEvents(Request $request): JsonResponse
    {
        $request->validate(['start' => ['nullable', 'date'], 'end' => ['nullable', 'date', 'after:start'], 'q' => ['nullable', 'string', 'max:255']]);
        $userId = (int) Auth::id();

        $query = Calendar::query()
            ->with('creator:id,name')
            ->where(function ($query) use ($userId) {
                $query->where('created_by', $userId)
                    ->orWhereJsonContains('guests', (string) $userId)
                    ->orWhereJsonContains('guests', $userId);
            });

        // FullCalendar sends the visible range in Gregorian ISO format. Convert the
        // boundaries to Jalali before querying because this legacy table stores
        // its date-time values as Jalali strings.
        if ($request->filled('start')) {
            $rangeStart = $this->isoToJalali((string) $request->query('start'));
            $query->where(function ($builder) use ($rangeStart) {
                $builder->whereNull('end')
                    ->where('start', '>=', $rangeStart)
                    ->orWhere('end', '>=', $rangeStart);
            });
        }

        if ($request->filled('end')) {
            $query->where('start', '<', $this->isoToJalali((string) $request->query('end')));
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $calendars = $query
            ->orderBy('start')
            ->get();

        return response()->json($calendars->map(fn (Calendar $calendar) => $this->eventPayload($calendar))->values());
    }

    public function store(
        CalendarEventRequest $request,
        GoogleCalendarSyncService $googleSync,
        OperationalNotificationService $notifications,
        ActivityLogService $activity
    ): JsonResponse {
        $data = $request->validated();
        $guestIds = collect($data['eventGuests'] ?? [])->push((string) auth()->id())->unique()->values();

        $calendar = Calendar::query()->create([
            'created_by' => auth()->id(),
            'title' => $data['eventTitle'],
            'label' => $data['eventLabel'] ?? null,
            'start' => $data['eventStartDate'],
            'end' => $data['eventEndDate'] ?? $data['eventStartDate'],
            'all_day' => (bool) ($data['allDay'] ?? false),
            'url' => $data['eventURL'] ?? null,
            'location' => $data['eventLocation'] ?? null,
            'description' => $data['eventDescription'] ?? null,
            'guests' => $guestIds->all(),
        ]);

        $guests = User::query()->whereIn('id', $guestIds->all())->get();
        $googleSync->sync($calendar, $request->user(), $guests->pluck('email')->filter()->all());

        $invitees = $guests->reject(fn (User $user) => (int) $user->id === (int) auth()->id())->values();
        $inviteMessage = $this->calendarMessage($calendar, 'دعوت به جلسه');
        $notifications->deliver(
            'calendar.invited',
            'created',
            $calendar,
            $invitees,
            [
                'title' => 'دعوت به جلسه جدید',
                'message' => $inviteMessage,
                'url' => route('calendar.index'),
                'icon' => 'mdi-calendar-month-outline',
                'category' => 'calendar',
                'severity' => 'info',
                'actor_id' => auth()->id(),
            ],
            $inviteMessage
        );

        $activity->record('calendar.created', "رویداد «{$calendar->title}» ایجاد شد.");

        return response()->json([
            'success' => true,
            'subject' => 'عملیات موفق',
            'flag' => 'success',
            'message' => 'رویداد با موفقیت ثبت شد.',
            'event' => $this->eventPayload($calendar->fresh()),
        ]);
    }

    public function update(
        CalendarEventRequest $request,
        int $id,
        GoogleCalendarSyncService $googleSync,
        OperationalNotificationService $notifications,
        ActivityLogService $activity
    ): JsonResponse {
        $calendar = Calendar::query()->findOrFail($id);
        $this->authorizeCalendarMutation($calendar);
        $data = $request->validated();

        $guestIds = collect($data['eventGuests'] ?? $calendar->guests ?? [])
            ->push((string) ($calendar->created_by ?: auth()->id()))
            ->unique()->values();

        $calendar->fill([
            'title' => $data['eventTitle'],
            'label' => $data['eventLabel'] ?? null,
            'start' => $data['eventStartDate'],
            'end' => $data['eventEndDate'] ?? $data['eventStartDate'],
            'all_day' => (bool) ($data['allDay'] ?? false),
            'url' => $data['eventURL'] ?? null,
            'location' => $data['eventLocation'] ?? null,
            'description' => $data['eventDescription'] ?? null,
            'guests' => $guestIds->all(),
        ])->save();

        $guests = User::query()->whereIn('id', $guestIds->all())->get();
        $syncUser = $calendar->creator ?: $request->user();
        $googleSync->sync($calendar, $syncUser, $guests->pluck('email')->filter()->all());

        $invitees = $guests->reject(fn (User $user) => (int) $user->id === (int) auth()->id())->values();
        $updateMessage = $this->calendarMessage($calendar, 'تغییر برنامه جلسه');
        $notifications->deliver(
            'calendar.updated',
            'updated:'.sha1(json_encode([
                $calendar->title, $calendar->start, $calendar->end, $calendar->location,
                $calendar->description, $calendar->guests,
            ], JSON_UNESCAPED_UNICODE)),
            $calendar,
            $invitees,
            [
                'title' => 'برنامه جلسه به‌روزرسانی شد',
                'message' => $updateMessage,
                'url' => route('calendar.index'),
                'icon' => 'mdi-calendar-edit',
                'category' => 'calendar',
                'severity' => 'warning',
                'actor_id' => auth()->id(),
            ],
            $updateMessage
        );

        $activity->record('calendar.updated', "رویداد «{$calendar->title}» به‌روزرسانی شد.");

        return response()->json($this->eventPayload($calendar->fresh()));
    }

    public function destroy(
        int $id,
        GoogleCalendarSyncService $googleSync,
        OperationalNotificationService $notifications,
        ActivityLogService $activity
    ): JsonResponse {
        $calendar = Calendar::query()->findOrFail($id);
        $this->authorizeCalendarMutation($calendar);
        $title = $calendar->title;
        $guestIds = collect($calendar->guests ?? [])->map(fn ($id) => (int) $id)->unique();
        $invitees = User::query()->whereIn('id', $guestIds)->get()
            ->reject(fn (User $user) => (int) $user->id === (int) auth()->id())
            ->values();
        $cancelMessage = $this->calendarMessage($calendar, 'لغو جلسه');
        $notifications->deliver(
            'calendar.cancelled',
            'cancelled',
            $calendar,
            $invitees,
            [
                'title' => 'جلسه لغو شد',
                'message' => $cancelMessage,
                'url' => route('calendar.index'),
                'icon' => 'mdi-calendar-remove-outline',
                'category' => 'calendar',
                'severity' => 'critical',
                'actor_id' => auth()->id(),
            ],
            $cancelMessage
        );

        $googleSync->delete($calendar, $calendar->creator ?: auth()->user());
        $calendar->delete();
        $activity->record('calendar.deleted', "رویداد «{$title}» حذف شد.");

        return response()->json(['status' => 'deleted', 'id' => $id]);
    }

    private function authorizeCalendarMutation(Calendar $calendar): void
    {
        $user = auth()->user();
        if ($user->hasRole(['superadmin', 'manager'])) {
            return;
        }

        abort_unless($calendar->created_by !== null && (int) $calendar->created_by === (int) $user->id, 403);
    }

    private function calendarMessage(Calendar $calendar, string $prefix): string
    {
        $creatorName = $calendar->creator?->name ?? auth()->user()?->name ?? 'سامانه';
        $parts = [
            $prefix.' «'.$calendar->title.'»',
            'زمان: '.$calendar->start,
            'دعوت‌کننده: '.$creatorName,
        ];

        if (filled($calendar->location)) {
            $parts[] = 'محل: '.$calendar->location;
        }
        if (filled($calendar->description)) {
            $parts[] = 'شرح: '.mb_substr(trim((string) $calendar->description), 0, 220);
        }

        return implode(' | ', $parts);
    }

    private function eventPayload(Calendar $calendar): array
    {
        $start = Jalalian::fromFormat('Y-m-d H:i:s', $calendar->start)->toCarbon();
        $end = Jalalian::fromFormat('Y-m-d H:i:s', $calendar->end ?: $calendar->start)->toCarbon();
        $user = auth()->user();
        $canEdit = $user !== null && (
            $user->hasRole(['superadmin', 'manager'])
            || ($calendar->created_by !== null && (int) $calendar->created_by === (int) $user->id)
        );

        // FullCalendar treats the end of an all-day event as exclusive. The
        // database stores the inclusive Jalali end date, so add one day only in
        // the transport payload and retain the original values in extendedProps.
        $calendarEnd = $calendar->all_day ? $end->copy()->addDay() : $end;

        return [
            'id' => $calendar->id,
            'title' => $calendar->title,
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $calendarEnd->format('Y-m-d\TH:i:s'),
            'allDay' => (bool) $calendar->all_day,
            'url' => $calendar->url,
            'editable' => $canEdit,
            'extendedProps' => [
                'calendar' => $calendar->label ?: 'other',
                'location' => $calendar->location,
                'description' => $calendar->description,
                'guests' => collect($calendar->guests ?? [])->map(fn ($id) => (string) $id)->values()->all(),
                'guestCount' => count($calendar->guests ?? []),
                'creatorName' => $calendar->creator?->name ?? 'سامانه',
                'canEdit' => $canEdit,
                'canDelete' => $canEdit,
                'jalaliStart' => $calendar->start,
                'jalaliEnd' => $calendar->end ?: $calendar->start,
                'googleSyncStatus' => $calendar->google_sync_status,
            ],
        ];
    }

    private function isoToJalali(string $value): string
    {
        return Jalalian::fromCarbon(
            Carbon::parse($value)->setTimezone(config('app.timezone', 'Asia/Tehran'))
        )->format('Y-m-d H:i:s');
    }
}
