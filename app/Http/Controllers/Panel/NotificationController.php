<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\InvestmentReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Yajra\DataTables\Facades\DataTables;

class NotificationController extends Controller
{
    public function index(Request $request, InvestmentReminderService $reminders)
    {
        $user = $request->user();
        $liveAlerts = $reminders->alertsForUser($user, now(), 50);

        return view('panel.notifications', [
            'thispage' => ['title' => 'اعلان‌ها و هشدارهای سامانه', 'list' => 'اعلان‌ها'],
            'liveAlerts' => $liveAlerts,
            'notificationSummary' => [
                'unread' => $user->unreadNotifications()->count(),
                'active_alerts' => $liveAlerts->count(),
                'critical' => $liveAlerts->whereIn('severity', ['critical', 'overdue'])->count(),
            ],
        ]);
    }

    public function data(Request $request)
    {
        $query = $request->user()->notifications()->select('notifications.*');

        $readState = (string) $request->input('read_state', 'all');
        if ($readState === 'unread') {
            $query->whereNull('read_at');
        } elseif ($readState === 'read') {
            $query->whereNotNull('read_at');
        }

        $category = (string) $request->input('category', 'all');
        if (in_array($category, ['sla', 'workflow', 'calendar', 'correspondence', 'minute', 'system'], true)) {
            $query->where('data->category', $category);
        }

        $severity = (string) $request->input('severity', 'all');
        if (in_array($severity, ['info', 'success', 'warning', 'critical', 'overdue'], true)) {
            $query->where('data->severity', $severity);
        }

        return DataTables::of($query)
            ->editColumn('read_at', fn (DatabaseNotification $notification) => $notification->read_at ? 'خوانده‌شده' : 'جدید')
            ->editColumn('created_at', fn (DatabaseNotification $notification) => jdate($notification->created_at)->format('Y/m/d H:i'))
            ->addColumn('title', fn (DatabaseNotification $notification) => e((string) ($notification->data['title'] ?? 'اعلان')))
            ->addColumn('message', fn (DatabaseNotification $notification) => e((string) ($notification->data['message'] ?? '')))
            ->addColumn('url', fn (DatabaseNotification $notification) => $notification->data['url'] ?? null)
            ->addColumn('icon', fn (DatabaseNotification $notification) => (string) ($notification->data['icon'] ?? 'mdi-bell-outline'))
            ->addColumn('category', fn (DatabaseNotification $notification) => (string) ($notification->data['category'] ?? 'system'))
            ->addColumn('severity', fn (DatabaseNotification $notification) => (string) ($notification->data['severity'] ?? 'info'))
            ->addColumn('is_unread', fn (DatabaseNotification $notification) => $notification->read_at === null)
            ->make(true);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
