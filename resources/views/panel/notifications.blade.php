@extends('layouts.base')

@section('title', 'اعلان‌ها و هشدارهای سامانه')

@section('style')
<style>
    .notification-page .summary-card { border: 0; border-radius: 1rem; box-shadow: 0 .35rem 1.25rem rgba(31, 45, 61, .07); }
    .notification-page .summary-icon { width: 46px; height: 46px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.35rem; }
    .notification-page .live-alert { border: 1px solid var(--bs-border-color); border-radius: .9rem; transition: transform .15s ease, box-shadow .15s ease; }
    .notification-page .live-alert:hover { transform: translateY(-1px); box-shadow: 0 .35rem 1rem rgba(31, 45, 61, .08); }
    .notification-page .live-alert.is-overdue { border-inline-start: 4px solid var(--bs-danger); }
    .notification-page .live-alert.is-critical { border-inline-start: 4px solid var(--bs-warning); }
    .notification-page .live-alert.is-warning { border-inline-start: 4px solid var(--bs-info); }
    .notification-page .notification-row { display: flex; gap: .85rem; align-items: flex-start; padding: .25rem 0; }
    .notification-page .notification-row__icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .notification-page .notification-row__message { color: var(--bs-secondary-color); line-height: 1.8; white-space: normal; }
    .notification-page tr.notification-unread > td { background: rgba(var(--bs-primary-rgb), .035) !important; }
    .notification-page .filter-bar { background: rgba(var(--bs-primary-rgb), .035); border: 1px solid rgba(var(--bs-primary-rgb), .1); border-radius: .9rem; padding: .9rem; }
    @media (max-width: 767.98px) {
        .notification-page .dataTables_wrapper .row { gap: .75rem; }
        .notification-page table.dataTable td { white-space: normal; }
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4 notification-page">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">مرکز اعلان‌ها و هشدارها</h4>
            <p class="text-muted mb-0">هشدارهای SLA، KPI، تعهدات، فرایند سرمایه‌گذاری، تقویم و مکاتبات در یک نقطه.</p>
        </div>
        <button type="button" id="markAllNotificationsRead" class="btn btn-outline-primary">
            <i class="mdi mdi-check-all me-1"></i> خواندن همه اعلان‌ها
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card summary-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="summary-icon bg-label-primary"><i class="mdi mdi-bell-badge-outline"></i></span>
                <div><div class="text-muted small">اعلان خوانده‌نشده</div><div class="fs-4 fw-bold">{{ number_format($notificationSummary['unread'] ?? 0) }}</div></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="summary-icon bg-label-warning"><i class="mdi mdi-clock-alert-outline"></i></span>
                <div><div class="text-muted small">هشدار عملیاتی فعال</div><div class="fs-4 fw-bold">{{ number_format($notificationSummary['active_alerts'] ?? 0) }}</div></div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card summary-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="summary-icon bg-label-danger"><i class="mdi mdi-alert-octagon-outline"></i></span>
                <div><div class="text-muted small">بحرانی / معوق</div><div class="fs-4 fw-bold">{{ number_format($notificationSummary['critical'] ?? 0) }}</div></div>
            </div></div>
        </div>
    </div>

    @if(($liveAlerts ?? collect())->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">هشدارهای فعال پرونده‌ها</h5>
                    <small class="text-muted">این بخش وضعیت لحظه‌ای KPI و تعهدات را حتی پیش از اجرای چرخه بعدی Scheduler نمایش می‌دهد.</small>
                </div>
                <span class="badge bg-label-danger">{{ $liveAlerts->count() }} مورد فعال</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($liveAlerts as $alert)
                        @php
                            $days = (int) $alert['days_remaining'];
                            $severity = $alert['severity'] ?? 'warning';
                            $severityClass = $severity === 'overdue' ? 'danger' : ($severity === 'critical' ? 'warning' : 'info');
                            $statusLabel = $days < 0 ? abs($days).' روز معوق' : ($days === 0 ? 'سررسید امروز' : $days.' روز تا سررسید');
                            $targetUrl = auth()->user()->level === 'applicant' ? route('profile').'#navs-investment-card' : route('flow.show', $alert['project_id']);
                        @endphp
                        <div class="col-xl-6">
                            <a href="{{ $targetUrl }}" class="live-alert is-{{ $severity }} d-flex align-items-start gap-3 p-3 text-reset text-decoration-none h-100">
                                <span class="summary-icon bg-label-{{ $severityClass }}">
                                    <i class="mdi {{ $severity === 'overdue' ? 'mdi-alert-octagon-outline' : 'mdi-clock-alert-outline' }}"></i>
                                </span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                                        <strong>{{ $alert['type'] === 'kpi' ? 'KPI' : 'تعهد' }} — {{ \Illuminate\Support\Str::limit($alert['title'], 100) }}</strong>
                                        <span class="badge bg-label-{{ $severityClass }}">{{ $statusLabel }}</span>
                                    </div>
                                    <div class="text-muted small">{{ $alert['project_title'] }} · سررسید {{ $alert['due_date'] }}</div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-bottom">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><h5 class="mb-1 fw-bold">تاریخچه اعلان‌ها</h5><small class="text-muted">اعلان‌های ارسال‌شده و وضعیت خواندن آن‌ها</small></div>
            </div>
            <div class="filter-bar row g-2 mt-3">
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small mb-1">وضعیت خواندن</label>
                    <select id="notificationReadFilter" class="form-select form-select-sm">
                        <option value="all">همه</option><option value="unread">خوانده‌نشده</option><option value="read">خوانده‌شده</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small mb-1">دسته</label>
                    <select id="notificationCategoryFilter" class="form-select form-select-sm">
                        <option value="all">همه دسته‌ها</option><option value="sla">سررسید و SLA</option><option value="workflow">فرایند سرمایه‌گذاری</option><option value="calendar">تقویم</option><option value="correspondence">مکاتبات</option><option value="minute">صورتجلسات</option><option value="system">سیستم</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4">
                    <label class="form-label small mb-1">شدت</label>
                    <select id="notificationSeverityFilter" class="form-select form-select-sm">
                        <option value="all">همه سطوح</option><option value="overdue">معوق</option><option value="critical">بحرانی</option><option value="warning">هشدار</option><option value="success">موفق</option><option value="info">اطلاع</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="notificationsTable" class="table align-middle w-100">
                    <thead><tr><th>اعلان</th><th>دسته</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const categoryLabels = {sla:'سررسید و SLA', workflow:'فرایند', calendar:'تقویم', correspondence:'مکاتبات', minute:'صورتجلسه', system:'سیستم'};
    const severityMeta = {
        overdue:{label:'معوق', badge:'danger', icon:'mdi-alert-octagon-outline'},
        critical:{label:'بحرانی', badge:'warning', icon:'mdi-alert-circle-outline'},
        warning:{label:'هشدار', badge:'warning', icon:'mdi-clock-alert-outline'},
        success:{label:'موفق', badge:'success', icon:'mdi-check-circle-outline'},
        info:{label:'اطلاع', badge:'info', icon:'mdi-information-outline'}
    };

    const table = $('#notificationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('notifications.data') }}',
            data: function (payload) {
                payload.read_state = document.getElementById('notificationReadFilter')?.value || 'all';
                payload.category = document.getElementById('notificationCategoryFilter')?.value || 'all';
                payload.severity = document.getElementById('notificationSeverityFilter')?.value || 'all';
            }
        },
        columns: [
            {
                data: null, orderable: false, searchable: false,
                render: function (row) {
                    const severity = severityMeta[row.severity] || severityMeta.info;
                    const icon = /^mdi-[a-z0-9-]+$/i.test(row.icon || '') ? row.icon : severity.icon;
                    return `<div class="notification-row"><span class="notification-row__icon bg-label-${severity.badge}"><i class="mdi ${icon}"></i></span><div class="min-w-0"><div class="fw-semibold mb-1">${escapeHtml(row.title)}</div><div class="notification-row__message">${escapeHtml(row.message)}</div><div class="mt-1"><span class="badge bg-label-${severity.badge}">${severity.label}</span></div></div></div>`;
                }
            },
            {data: 'category', name: 'data', orderable: false, render: value => `<span class="badge bg-label-secondary">${escapeHtml(categoryLabels[value] || 'سیستم')}</span>`},
            {data: 'read_at', name: 'read_at', render: (value, type, row) => row.is_unread ? '<span class="badge bg-label-primary">جدید</span>' : '<span class="badge bg-label-secondary">خوانده‌شده</span>'},
            {data: 'created_at', name: 'created_at'},
            {
                data: null, orderable: false, searchable: false,
                render: function (row) {
                    if (!row.url) return row.is_unread ? `<button class="btn btn-sm btn-outline-secondary notification-mark-read" data-id="${row.id}">خواندم</button>` : '';
                    const safeUrl = encodeURIComponent(row.url);
                    return `<button class="btn btn-sm btn-primary notification-open" data-id="${row.id}" data-url="${safeUrl}"><i class="mdi mdi-open-in-new me-1"></i>مشاهده</button>`;
                }
            }
        ],
        createdRow: function (row, data) { if (data.is_unread) row.classList.add('notification-unread'); },
        order: [[3, 'desc']],
        pageLength: 15,
        language: {url: '{{ asset('assets/vendor/js/fa.json') }}'}
    });

    ['notificationReadFilter','notificationCategoryFilter','notificationSeverityFilter'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => table.ajax.reload());
    });

    async function markRead(id) {
        await fetch(`{{ url('panel/notifications') }}/${id}/read`, {method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}});
    }

    document.addEventListener('click', async function (event) {
        const openButton = event.target.closest('.notification-open');
        if (openButton) {
            await markRead(openButton.dataset.id);
            window.location.href = decodeURIComponent(openButton.dataset.url);
            return;
        }
        const readButton = event.target.closest('.notification-mark-read');
        if (readButton) {
            await markRead(readButton.dataset.id);
            table.ajax.reload(null, false);
        }
    });

    document.getElementById('markAllNotificationsRead')?.addEventListener('click', async function () {
        await fetch('{{ route('notifications.read-all') }}', {method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}});
        table.ajax.reload(null, false);
    });
});
</script>
@endsection
