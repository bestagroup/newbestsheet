@php
    $ops = $operationalAnalytics ?? [];
    $opsSummary = $ops['summary'] ?? [];
    $opsKpi = $ops['kpi'] ?? [];
    $opsCommitments = $ops['commitments'] ?? [];
    $riskProjects = $ops['risk_projects'] ?? collect();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 mb-3">
    <div>
        <h5 class="mb-1 fw-bold">داشبورد SLA و عملکرد عملیاتی</h5>
        <small class="text-muted">مبنای پیشرفت، وزن واقعی مراحل فعال است و SLA از سررسید KPIها و تعهدات محاسبه می‌شود.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary btn-sm" href="{{ route('report.export', array_merge(['type' => 'portfolio'], request()->only(['project_id','from_date','to_date']))) }}">
            <i class="mdi mdi-file-delimited-outline me-1"></i> خروجی پورتفو
        </a>
        <a class="btn btn-outline-warning btn-sm" href="{{ route('report.export', array_merge(['type' => 'sla'], request()->only(['project_id']))) }}">
            <i class="mdi mdi-clock-alert-outline me-1"></i> خروجی SLA
        </a>
        <a class="btn btn-outline-info btn-sm" href="{{ route('report.export', array_merge(['type' => 'experts'], request()->only(['project_id']))) }}">
            <i class="mdi mdi-account-star-outline me-1"></i> خروجی کارشناسان
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block mb-2">میانگین پیشرفت وزنی</small>
            <h4 class="mb-1">{{ number_format((float)($opsSummary['average_progress'] ?? 0), 1) }}٪</h4>
            <div class="progress" style="height:6px"><div class="progress-bar" style="width:{{ min(100, max(0, (float)($opsSummary['average_progress'] ?? 0))) }}%"></div></div>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block mb-2">انطباق SLA</small>
            <h4 class="mb-1">{{ is_null($opsSummary['sla_compliance'] ?? null) ? '—' : number_format((float)$opsSummary['sla_compliance'], 1).'٪' }}</h4>
            <small class="text-muted">{{ number_format((int)($opsSummary['sla_eligible'] ?? 0)) }} مورد قابل ارزیابی</small>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block mb-2">KPI معوق</small>
            <h4 class="mb-1 text-danger">{{ number_format((int)($opsKpi['overdue'] ?? 0)) }}</h4>
            <small class="text-muted">{{ number_format((int)($opsKpi['due_soon'] ?? 0)) }} نزدیک سررسید</small>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block mb-2">تعهد معوق</small>
            <h4 class="mb-1 text-danger">{{ number_format((int)($opsCommitments['overdue'] ?? 0)) }}</h4>
            <small class="text-muted">{{ number_format((int)($opsCommitments['due_soon'] ?? 0)) }} نزدیک سررسید</small>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block mb-2">پروژه پرریسک</small>
            <h4 class="mb-1 text-warning">{{ number_format((int)($opsSummary['at_risk_projects'] ?? 0)) }}</h4>
            <small class="text-muted">دارای KPI/تعهد معوق</small>
        </div></div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card h-100 border-0 shadow-sm"><div class="card-body">
            <small class="text-muted d-block mb-2">پروژه جاری بدون تخصیص</small>
            <h4 class="mb-1 {{ (int)($opsSummary['unassigned_active_projects'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((int)($opsSummary['unassigned_active_projects'] ?? 0)) }}</h4>
            <small class="text-muted">کارشناس/ناظر/ارزیاب فعال</small>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header"><h6 class="mb-0 fw-bold">توزیع پیشرفت وزنی پروژه‌ها</h6></div>
            <div class="card-body"><div class="chart-box"><canvas id="operationalProgressDistributionChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header"><h6 class="mb-0 fw-bold">بار جاری مراحل سرمایه‌گذاری</h6></div>
            <div class="card-body"><div class="chart-box"><canvas id="operationalStepWorkloadChart"></canvas></div></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><h6 class="mb-1 fw-bold">پروژه‌های نیازمند اقدام فوری</h6><small class="text-muted">مرتب‌شده بر اساس مجموع KPI و تعهدات معوق</small></div>
        <a href="{{ route('notifications.index') }}" class="btn btn-outline-danger btn-sm"><i class="mdi mdi-bell-alert-outline me-1"></i> مرکز اعلان‌ها</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>پروژه / شرکت</th><th>مرحله جاری</th><th>پیشرفت</th><th>KPI معوق</th><th>تعهد معوق</th><th>اقدام</th></tr></thead>
            <tbody>
            @forelse($riskProjects->take(10) as $row)
                <tr>
                    <td><strong>{{ $row['title'] }}</strong><div class="small text-muted">{{ $row['company_name'] ?: '—' }}</div></td>
                    <td>{{ $row['current_step'] ?: '—' }}</td>
                    <td style="min-width:130px"><div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:7px"><div class="progress-bar" style="width:{{ $row['progress_percentage'] }}%"></div></div><span>{{ $row['progress_percentage'] }}٪</span></div></td>
                    <td><span class="badge bg-label-danger">{{ number_format($row['overdue_kpis']) }}</span></td>
                    <td><span class="badge bg-label-warning">{{ number_format($row['overdue_commitments']) }}</span></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('flow.show', $row['id']) }}">پرونده</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">در حال حاضر پروژه دارای KPI یا تعهد معوق وجود ندارد.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header">
        <h6 class="mb-1 fw-bold">عملکرد کارشناسان، ناظران و ارزیابان</h6>
        <small class="text-muted">تخصیص‌ها و تصمیم‌های ثبت‌شده از Audit واقعی Workflow استخراج می‌شوند؛ امتیاز مصنوعی تولید نمی‌شود.</small>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="expertPerformanceTable" class="table table-striped table-bordered w-100">
                <thead><tr><th>نام</th><th>نقش</th><th>تخصیص کل</th><th>تخصیص فعال</th><th>پروژه‌ها</th><th>تصمیم‌ها</th><th>تأیید</th><th>رد</th><th>ریسک</th></tr></thead>
            </table>
        </div>
    </div>
</div>
