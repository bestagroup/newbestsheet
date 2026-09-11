<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User_logs;
use App\Services\ActivityLogVisibilityService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogVisibilityService $activityLogVisibility,
    ) {}

    public function index(Request $request)
    {
        if (! $request->ajax()) {
            return view('panel.activitylog', [
                'thispage' => ['title' => 'ردیابی فعالیت‌های عملیاتی'],
            ]);
        }

        $viewer = $request->user();
        $query = User_logs::query()
            ->with(['user:id,name', 'user.roles:id,title,title_fa'])
            ->latest('id');
        $this->activityLogVisibility->scopeForViewer($query, $viewer);

        return DataTables::eloquent($query)
            ->addColumn('user', fn (User_logs $log) => e($log->user?->name ?? 'سیستم'))
            ->addColumn('user_role', fn (User_logs $log) => e($log->user?->roles
                ->map(fn ($role): string => $role->title_fa ?: $role->title)
                ->filter()
                ->implode('، ') ?: '—'))
            ->editColumn('action', fn (User_logs $log) => e((string) $log->action))
            ->editColumn('description', fn (User_logs $log) => e((string) ($log->description ?? '')))
            ->editColumn('ip_address', fn (User_logs $log) => e((string) ($log->ip_address ?? '')))
            ->editColumn('status', fn (User_logs $log) => $log->status ? 'موفق' : 'ناموفق')
            ->editColumn('created_at', fn (User_logs $log) => $log->created_at ? jdate($log->created_at)->format('Y/m/d H:i') : '')
            ->make(true);
    }
}
