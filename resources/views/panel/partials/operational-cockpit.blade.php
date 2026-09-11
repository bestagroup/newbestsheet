@php
    $ops = $operationalAnalytics ?? [];
    $summary = $ops['summary'] ?? [];
    $kpi = $ops['kpi'] ?? [];
    $commitmentsOps = $ops['commitments'] ?? [];
@endphp
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><h5 class="mb-1 fw-bold">کنسول عملیاتی سرمایه‌گذاری</h5><small class="text-muted">Weighted Progress، SLA و موارد نیازمند اقدام</small></div>
        <a href="{{ route('report.index') }}" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-chart-box-outline me-1"></i> گزارش جامع</a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted">میانگین پیشرفت</small><h4 class="mb-0 mt-2">{{ number_format((float)($summary['average_progress'] ?? 0),1) }}٪</h4></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted">انطباق SLA</small><h4 class="mb-0 mt-2">{{ is_null($summary['sla_compliance'] ?? null) ? '—' : number_format((float)$summary['sla_compliance'],1).'٪' }}</h4></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted">KPI معوق</small><h4 class="mb-0 mt-2 text-danger">{{ number_format((int)($kpi['overdue'] ?? 0)) }}</h4></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted">تعهد معوق</small><h4 class="mb-0 mt-2 text-danger">{{ number_format((int)($commitmentsOps['overdue'] ?? 0)) }}</h4></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted">پروژه پرریسک</small><h4 class="mb-0 mt-2 text-warning">{{ number_format((int)($summary['at_risk_projects'] ?? 0)) }}</h4></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="border rounded-4 p-3 h-100"><small class="text-muted">بدون تخصیص فعال</small><h4 class="mb-0 mt-2 {{ (int)($summary['unassigned_active_projects'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((int)($summary['unassigned_active_projects'] ?? 0)) }}</h4></div></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-6"><div class="border rounded-4 p-3 h-100"><h6 class="fw-bold">توزیع پیشرفت وزنی</h6><div class="chart-box"><canvas id="dashboardProgressDistributionChart"></canvas></div></div></div>
            <div class="col-lg-6"><div class="border rounded-4 p-3 h-100"><h6 class="fw-bold">بار مراحل جاری</h6><div class="chart-box"><canvas id="dashboardStepWorkloadChart"></canvas></div></div></div>
        </div>
    </div>
</div>
