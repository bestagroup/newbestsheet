@php($currentStep = $investsteps->firstWhere('id', (int) $project->invest_step))
<div class="tab-pane fade" id="navs-project-profile-card" role="tabpanel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h5 class="mb-1 fw-bold">وضعیت پرونده</h5><small class="text-muted">نمای کلی از جایگاه فعلی طرح در چرخه سرمایه‌گذاری</small></div>
        <a href="#navs-investment-card" class="btn btn-outline-primary btn-sm" data-bs-toggle="tab" data-bs-target="#navs-investment-card"><i class="mdi mdi-source-branch me-1"></i>مشاهده مراحل</a>
    </div>
    <div class="row g-3">
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="portal-hero__icon bg-label-primary"><i class="mdi mdi-map-marker-path"></i></span><div><small class="text-muted">مرحله جاری</small><h6 class="mt-1 mb-0">{{ $currentStep?->title ?? 'نامشخص' }}</h6></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="portal-hero__icon bg-label-success"><i class="mdi mdi-chart-donut"></i></span><div><small class="text-muted">پیشرفت</small><h4 class="mt-1 mb-0">{{ (int)$project->progress_percentage }}٪</h4></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="portal-hero__icon bg-label-info"><i class="mdi mdi-briefcase-outline"></i></span><div><small class="text-muted">وضعیت پورتفو</small><h6 class="mt-1 mb-0">{{ $project->portfo_status ?: '—' }}</h6></div></div></div></div>
        <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="portal-hero__icon bg-label-{{ $project->is_rejected ? 'danger' : 'warning' }}"><i class="mdi {{ $project->is_rejected ? 'mdi-close-circle-outline' : 'mdi-progress-clock' }}"></i></span><div><small class="text-muted">وضعیت پرونده</small><h6 class="mt-1 mb-0">{{ $project->is_rejected ? 'رد شده' : ($project->activity_status ?: 'فعال') }}</h6></div></div></div></div>
    </div>
    <div class="card mt-3"><div class="card-body">
        <div class="d-flex justify-content-between small mb-2"><span class="text-muted">درصد پیشرفت چرخه</span><strong>{{ (int)$project->progress_percentage }}٪</strong></div>
        <div class="progress" style="height: 10px"><div class="progress-bar" style="width: {{ min(100, max(0, (int)$project->progress_percentage)) }}%"></div></div>
    </div></div>
</div>
