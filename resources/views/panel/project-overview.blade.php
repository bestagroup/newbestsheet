@extends('layouts.base')

@section('title', 'نمای جامع '.$thispage['list'])

@section('style')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        html { scroll-behavior: smooth; }
        .overview-hero { background: linear-gradient(135deg, #3431a8 0%, #675fd4 55%, #2c8bba 100%); border-radius: 22px; color: #fff; overflow: hidden; position: relative; }
        .overview-hero::after { content: ''; position: absolute; width: 260px; height: 260px; border-radius: 50%; background: rgba(255,255,255,.08); left: -70px; bottom: -140px; }
        .overview-logo { width: 76px; height: 76px; object-fit: cover; border-radius: 20px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.32); }
        .overview-logo--placeholder { display: inline-flex; align-items: center; justify-content: center; font-size: 34px; }
        .overview-nav { position: sticky; top: 78px; z-index: 5; border-radius: 14px; }
        .overview-nav a { white-space: nowrap; }
        .overview-section { scroll-margin-top: 150px; border: 0; border-radius: 18px; box-shadow: 0 8px 24px rgba(30,41,59,.06); }
        .overview-section > .card-header { background: transparent; border-bottom: 1px solid rgba(99,102,241,.12); }
        .overview-metric { border: 1px solid rgba(99,102,241,.13); border-radius: 14px; padding: 15px; height: 100%; background: var(--bs-body-bg); }
        .overview-metric i { font-size: 24px; }
        .overview-step { border-right: 4px solid #d8dbe8; }
        .overview-step.is-done { border-right-color: #36a56f; }
        .overview-step.is-current { border-right-color: #696cff; background: rgba(105,108,255,.05); }
        .overview-section .table th { white-space: nowrap; }
        @media (max-width: 991.98px) { .overview-nav { position: static; overflow-x: auto; } }
    </style>
@endsection

@section('content')
    @php
        $companyName = $project->company?->company_name ?: ($project->company_name ?: $project->title);
        $totalPaid = (float) $finances->sum('amount');
        $commitmentBalance = max(0, (float) ($project->amount_request_accept ?? 0) - $totalPaid);
        $currentKpis = $kpis->where('is_current', true);
        $approvedKpis = $currentKpis->where('review_status', 'approved')->count();
        $pendingCommitments = $projectCommitments->where('status', 'pending')->whereNull('completed_at')->count();
        $latestStatement = $financialStatements->first();
        $decisionMeta = [
            'approved' => ['تأیید شده', 'success', 'mdi-check-circle-outline'],
            'rejected' => ['رد شده', 'danger', 'mdi-close-circle-outline'],
        ];
    @endphp

    <section class="overview-hero p-4 p-lg-5 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 position-relative" style="z-index:1">
            <div class="d-flex align-items-center gap-3">
                @if($project->logo)
                    <img class="overview-logo" src="{{ asset('storage/'.$project->logo) }}" alt="لوگوی {{ $companyName }}">
                @else
                    <span class="overview-logo overview-logo--placeholder"><i class="mdi mdi-domain"></i></span>
                @endif
                <div>
                    <div class="small opacity-75 mb-1">نمای یکپارچه پرونده سرمایه‌گذاری</div>
                    <h3 class="text-white mb-1">{{ $companyName }}</h3>
                    <div class="opacity-75">{{ $project->title }} — {{ $project->currentStep?->title ?: 'مرحله نامشخص' }}</div>
                </div>
            </div>
            <div class="text-start" style="min-width:230px">
                <div class="d-flex justify-content-between mb-2"><span>پیشرفت فرایند</span><strong>{{ number_format((float) ($project->progress_percentage ?? 0), 0) }}٪</strong></div>
                <div class="progress bg-white bg-opacity-25" style="height:10px"><div class="progress-bar bg-white" style="width:{{ min(100, max(0, (float) ($project->progress_percentage ?? 0))) }}%"></div></div>
                <div class="mt-3"><span class="badge bg-{{ $project->is_rejected ? 'danger' : 'success' }}">{{ $project->is_rejected ? 'پرونده رد شده' : 'پرونده فعال' }}</span></div>
            </div>
        </div>
    </section>

    <nav class="overview-nav card mb-4"><div class="card-body py-2 d-flex gap-2 overflow-auto">
        @foreach([
            ['snapshot', 'آخرین وضعیت'], ['identity', 'شرکت و طرح'], ['workflow', 'گردش‌کار'], ['investment', 'سرمایه‌گذاری'],
            ['kpis', 'KPI'], ['commitments', 'تعهدات'], ['performance', 'عملکرد فصلی'], ['financial', 'گزارش مالی'],
            ['team', 'تیم'], ['documents', 'فایل‌ها'], ['messages', 'پیام‌ها'],
        ] as [$anchor, $label])
            <a class="btn btn-sm btn-label-primary" href="#{{ $anchor }}">{{ $label }}</a>
        @endforeach
        <a class="btn btn-sm btn-outline-secondary ms-auto" href="{{ route('flow.index') }}"><i class="mdi mdi-arrow-right me-1"></i>بازگشت</a>
    </div></nav>

    <section class="card overview-section mb-4" id="snapshot">
        <div class="card-header"><h5 class="mb-1">آخرین وضعیت در یک نگاه</h5><small class="text-muted">مهم‌ترین اعداد و موارد نیازمند توجه</small></div>
        <div class="card-body"><div class="row g-3">
            @foreach([
                ['مرحله جاری', $project->currentStep?->title ?: 'نامشخص', 'mdi-source-branch', 'primary'],
                ['پرداخت‌شده', number_format($totalPaid).' ریال', 'mdi-cash-check', 'success'],
                ['مانده تعهد سرمایه‌گذاری', number_format($commitmentBalance).' ریال', 'mdi-wallet-outline', 'warning'],
                ['KPI جاری / تأییدشده', $currentKpis->count().' / '.$approvedKpis, 'mdi-target', 'info'],
                ['تعهد باز', $pendingCommitments, 'mdi-clipboard-clock-outline', $pendingCommitments ? 'danger' : 'success'],
                ['آخرین دوره مالی', $latestStatement ? sprintf('%04d/%02d', $latestStatement->year, $latestStatement->month) : 'ثبت نشده', 'mdi-file-chart-outline', 'primary'],
            ] as [$label, $value, $icon, $tone])
                <div class="col-6 col-xl-2"><div class="overview-metric"><i class="mdi {{ $icon }} text-{{ $tone }}"></i><small class="text-muted d-block mt-2">{{ $label }}</small><strong class="d-block mt-1">{{ $value }}</strong></div></div>
            @endforeach
        </div></div>
    </section>

    <section class="card overview-section mb-4" id="identity">
        <div class="card-header"><h5 class="mb-1">اطلاعات شرکت و طرح</h5><small class="text-muted">مشخصات ثبتی، تماس و چارچوب سرمایه‌گذاری</small></div>
        <div class="card-body"><div class="row g-4">
            <div class="col-lg-6"><h6 class="mb-3">شرکت</h6><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><tbody>
                <tr><th>نام شرکت</th><td>{{ $companyName }}</td></tr><tr><th>نام تجاری</th><td>{{ $project->company?->commercial_name ?: '—' }}</td></tr>
                <tr><th>مدیرعامل</th><td>{{ $project->company?->ceo_name ?? $project->CEO ?: '—' }}</td></tr><tr><th>تلفن</th><td dir="ltr">{{ $project->company?->phone ?? $project->tel ?: '—' }}</td></tr>
                <tr><th>شناسه ملی</th><td>{{ $project->company?->national_id ?? $project->national_id ?: '—' }}</td></tr><tr><th>شماره ثبت</th><td>{{ $project->company?->registration_number ?? $project->registration_number ?: '—' }}</td></tr>
                <tr><th>نوع حقوقی</th><td>{{ $project->company?->legal_type ?? $project->legal_type ?: '—' }}</td></tr><tr><th>وب‌سایت</th><td>{{ $project->company?->website ?? $project->website ?: '—' }}</td></tr>
                <tr><th>نشانی</th><td>{{ $project->company?->address ?? $project->address ?: '—' }}</td></tr>
            </tbody></table></div></div>
            <div class="col-lg-6"><h6 class="mb-3">طرح</h6><div class="table-responsive"><table class="table table-sm table-bordered mb-0"><tbody>
                <tr><th>عنوان طرح</th><td>{{ $project->title }}</td></tr><tr><th>وضعیت پورتفو</th><td>{{ $project->portfo_status ?: '—' }}</td></tr>
                <tr><th>وضعیت فعالیت</th><td>{{ $project->activity_status ?: '—' }}</td></tr><tr><th>درصد سهام</th><td>{{ $project->percentageshare !== null ? $project->percentageshare.'٪' : '—' }}</td></tr>
                <tr><th>شروع قرارداد</th><td>{{ $project->start_date ?: '—' }}</td></tr><tr><th>مبلغ مصوب</th><td>{{ number_format((float) ($project->amount_request_accept ?? 0)) }} ریال</td></tr>
                <tr><th>شرح طرح</th><td>{{ $project->description ?: '—' }}</td></tr>
            </tbody></table></div></div>
        </div></div>
    </section>

    <section class="card overview-section mb-4" id="workflow">
        <div class="card-header"><h5 class="mb-1">گردش‌کار و تاریخچه تصمیم‌ها</h5><small class="text-muted">هر مرحله فقط یک‌بار و بر اساس آخرین تصمیم ثبت‌شده نمایش داده می‌شود.</small></div>
        <div class="card-body">
            <div class="row g-2 mb-4">
                @foreach($investsteps as $step)
                    <div class="col-md-6 col-xl-4"><div class="overview-step p-3 rounded h-100 {{ $step->id < $project->invest_step ? 'is-done' : ($step->id === $project->invest_step ? 'is-current' : '') }}">
                        <div class="d-flex justify-content-between gap-2"><strong>مرحله {{ $step->sequence ?? $step->id }} — {{ $step->title }}</strong>@if($step->id === $project->invest_step)<span class="badge bg-primary">جاری</span>@elseif($step->id < $project->invest_step)<i class="mdi mdi-check-circle text-success"></i>@endif</div>
                        @if($step->description)<small class="text-muted d-block mt-1">{{ $step->description }}</small>@endif
                    </div></div>
                @endforeach
            </div>
            <h6 class="mb-3">آخرین تصمیم هر مرحله</h6>
            <div class="row g-3">
                @forelse($project_steps as $step)
                    @php([$decisionLabel, $decisionTone, $decisionIcon] = $decisionMeta[$step->status] ?? ['در انتظار', 'secondary', 'mdi-clock-outline'])
                    <div class="col-md-6 col-xl-4"><article class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between gap-2 mb-2"><strong>مرحله {{ $step->step_number }} — {{ $step->title }}</strong><span class="badge bg-label-{{ $decisionTone }}"><i class="mdi {{ $decisionIcon }} me-1"></i>{{ $decisionLabel }}</span></div>
                        <div class="small mb-2">{{ $step->description ?: 'بدون توضیح' }}</div>
                        <small class="text-muted">{{ $step->username ?: 'کارشناس' }}{{ $step->actor_role_title ? ' — '.$step->actor_role_title : '' }} | {{ $step->decided_at ? jdate($step->decided_at)->format('Y/m/d H:i') : jdate($step->created_at)->format('Y/m/d H:i') }}</small>
                    </article></div>
                @empty
                    <div class="col-12"><div class="alert alert-light border mb-0">هنوز تصمیمی ثبت نشده است.</div></div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="card overview-section mb-4" id="investment">
        <div class="card-header"><h5 class="mb-1">سرمایه‌گذاری، قراردادها و پرداخت‌ها</h5><small class="text-muted">جمع‌بندی قرارداد، تخصیص کارشناسان و جریان پرداخت</small></div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-xl-6"><h6>قراردادها</h6><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>شماره / عنوان</th><th>وضعیت</th><th>مبلغ</th><th>سهم</th><th>KPI</th></tr></thead><tbody>
                    @forelse($contracts as $contract)<tr><td>{{ $contract->contract_number }}<div class="small text-muted">{{ $contract->title }}</div></td><td>{{ $contract->status }}</td><td>{{ number_format((float) $contract->amount) }}</td><td>{{ $contract->equity_percentage !== null ? $contract->equity_percentage.'٪' : '—' }}</td><td>{{ $contract->kpis_count }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">قراردادی ثبت نشده است.</td></tr>@endforelse
                </tbody></table></div></div>
                <div class="col-xl-6"><h6>پرداخت‌ها</h6><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>قسط</th><th>مبلغ</th><th>تاریخ</th><th>شرح</th></tr></thead><tbody>
                    @forelse($finances as $payment)<tr><td>{{ $payment->serial ?: '—' }}</td><td>{{ number_format((float) $payment->amount) }} ریال</td><td>{{ $payment->date ?: '—' }}</td><td>{{ $payment->description ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">پرداختی ثبت نشده است.</td></tr>@endforelse
                    @if($finances->isNotEmpty())<tr class="table-light fw-bold"><td>جمع</td><td>{{ number_format($totalPaid) }} ریال</td><td colspan="2"></td></tr>@endif
                </tbody></table></div></div>
            </div>
            <h6 class="mt-3">کارشناسان و عوامل تخصیص‌یافته</h6><div class="d-flex flex-wrap gap-2">
                @forelse($projectAssignments->where('is_active', true) as $assignment)<span class="badge bg-label-primary p-2">{{ $assignment->user?->name ?: '—' }} — {{ $assignment->role?->title_fa ?: $assignment->role?->title }}{{ $assignment->investStep ? ' ('.$assignment->investStep->title.')' : '' }}</span>@empty<span class="text-muted">تخصیص فعالی وجود ندارد.</span>@endforelse
            </div>
        </div>
    </section>

    <section class="overview-section mb-4" id="kpis">@include('panel.partials.project-kpis')</section>

    <section class="card overview-section mb-4" id="commitments">
        <div class="card-header"><h5 class="mb-1">تعهدات و تضامین</h5><small class="text-muted">سررسید و وضعیت تعهدات شرکت</small></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead><tr><th>تعهد</th><th>سررسید</th><th>وضعیت</th><th>توضیحات</th></tr></thead><tbody>
            @forelse($projectCommitments as $item)<tr><td>{{ $item->commitment?->title ?: 'تعهد پروژه' }}</td><td>{{ $item->due_date ?: ($item->due_at ? jdate($item->due_at)->format('Y/m/d') : '—') }}</td><td><span class="badge bg-label-{{ $item->isCompleted() ? 'success' : 'warning' }}">{{ $item->isCompleted() ? 'انجام شده' : 'باز' }}</span></td><td>{{ $item->notes ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted py-4">تعهدی ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div></div>
    </section>

    <section class="card overview-section mb-4" id="performance">
        <div class="card-header"><h5 class="mb-1">گزارش‌های عملکرد فصلی</h5><small class="text-muted">نسخه جاری گزارش‌های عملکرد شرکت</small></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead><tr><th>دوره</th><th>نسخه</th><th>وضعیت</th><th>تعداد سنجه</th><th>خلاصه مدیریتی</th></tr></thead><tbody>
            @forelse($quarterlyReports as $report)<tr><td>{{ $report->year }} / فصل {{ $report->quarter }}</td><td>{{ $report->revision }}</td><td>{{ $report->status }}</td><td>{{ $report->measurements_count }}</td><td>{{ $report->executive_summary ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">گزارش فصلی ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div></div>
    </section>

    <section class="card overview-section mb-4" id="financial"><div class="card-body">@include('panel.partials.project-financial-report')</div></section>

    <section class="card overview-section mb-4" id="team">
        <div class="card-header"><h5 class="mb-1">اعضای تیم شرکت</h5><small class="text-muted">ترکیب مدیریتی و اجرایی ثبت‌شده</small></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead><tr><th>نام</th><th>سمت</th><th>کد ملی</th><th>شروع همکاری</th><th>وضعیت</th></tr></thead><tbody>
            @forelse($members as $member)<tr><td>{{ $member->full_name }}</td><td>{{ $member->position ?: '—' }}</td><td>{{ $member->national_code ?: '—' }}</td><td>{{ $member->start_date ?: '—' }}</td><td><span class="badge bg-label-{{ $member->is_active ? 'success' : 'secondary' }}">{{ $member->is_active ? 'فعال' : 'غیرفعال' }}</span></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">عضوی ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div></div>
    </section>

    <section class="card overview-section mb-4" id="documents">
        <div class="card-header"><h5 class="mb-1">فایل‌ها و مستندات</h5><small class="text-muted">آخرین اسناد بارگذاری‌شده پرونده</small></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead><tr><th>عنوان</th><th>نام فایل</th><th>نوع</th><th>تاریخ</th><th>دریافت</th></tr></thead><tbody>
            @forelse($files as $file)<tr><td>{{ $file->name ?: $file->subject?->title ?: 'سند پرونده' }}</td><td>{{ $file->original_name ?: '—' }}</td><td>{{ $file->type ?: $file->mime ?: '—' }}</td><td>{{ $file->created_at ? jdate($file->created_at)->format('Y/m/d') : '—' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ $file->url }}" target="_blank" rel="noopener">مشاهده</a></td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">فایلی ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div></div>
    </section>

    <section class="card overview-section mb-4" id="messages">
        <div class="card-header d-flex justify-content-between align-items-center"><div><h5 class="mb-1">پیام‌ها و مکاتبات</h5><small class="text-muted">آخرین پیام‌های مرتبط با نماینده این شرکت</small></div><a class="btn btn-sm btn-outline-primary" href="{{ route('correspondence.index') }}">ورود به مکاتبات</a></div>
        <div class="card-body"><div class="vstack gap-2">
            @forelse($recentMessages as $message)<div class="border rounded-3 p-3"><div class="d-flex justify-content-between gap-2"><strong>{{ $message->conversation?->subject ?: 'مکاتبه' }}</strong><small class="text-muted">{{ $message->created_at ? jdate($message->created_at)->format('Y/m/d H:i') : '' }}</small></div><div class="small mt-2">{{ \Illuminate\Support\Str::limit($message->body, 240) }}</div><small class="text-muted">فرستنده: {{ $message->sender?->name ?: 'کاربر سامانه' }}</small></div>@empty<div class="text-center text-muted py-4">پیامی برای این پرونده یافت نشد.</div>@endforelse
        </div></div>
    </section>
@endsection

@section('script')
    <script>
        (function ($) {
            'use strict';

            const errorMessage = xhr => Object.values(xhr.responseJSON?.errors || {}).flat()?.[0]
                || xhr.responseJSON?.message || 'عملیات انجام نشد. لطفاً دوباره تلاش کنید.';

            $(document).on('click', '.kpi-edit-btn', function () {
                const $panel = $('.kpi-edit-panel');
                const $form = $panel.find('.project-kpi-update-form');
                $form.attr('action', $(this).data('url'));
                $form.find('.kpi-number').val($(this).data('kpi-number') || '');
                $form.find('.kpi-title').val($(this).data('title') || '');
                $form.find('.kpi-type').val($(this).data('type') || '');
                $form.find('.kpi-type-value').val($(this).data('type-value') || '');
                $form.find('.kpi-value').val($(this).data('value') || '');
                $form.find('.kpi-unit').val($(this).data('unit') || '');
                $form.find('.kpi-deadline').val($(this).data('deadline') || '');
                $form.find('.kpi-period-time').val($(this).data('period-time') || '');
                $form.find('.kpi-completed').prop('checked', Number($(this).data('completed')) === 1);
                $panel.removeClass('d-none')[0]?.scrollIntoView({behavior: 'smooth', block: 'center'});
            });

            $(document).on('click', '.kpi-edit-cancel', function () { $('.kpi-edit-panel').addClass('d-none'); });
            $(document).on('change', '.project-kpi-review-form select[name="decision"]', function () {
                $(this).closest('form').find('[name="review_comment"]').prop('required', this.value === 'rejected');
            });

            $(document).on('submit', '.project-kpi-form, .project-kpi-update-form, .project-kpi-review-form', function (event) {
                event.preventDefault();
                const $form = $(this);
                const $button = $form.find('button[type="submit"]');
                $button.prop('disabled', true);
                $.ajax({url: $form.attr('action'), method: 'POST', data: $form.serialize()})
                    .done(response => {
                        if (window.toastr) toastr.success(response.message || 'اطلاعات ذخیره شد.');
                        window.setTimeout(() => window.location.reload(), 350);
                    })
                    .fail(xhr => {
                        $button.prop('disabled', false);
                        if (window.toastr) toastr.error(errorMessage(xhr)); else window.alert(errorMessage(xhr));
                    });
            });

            $(document).on('submit', '.project-kpi-delete-form', function (event) {
                event.preventDefault();
                const form = this;
                Swal.fire({title: 'حذف KPI؟', text: 'این عملیات فقط روی نسخه جاری انجام می‌شود.', icon: 'warning', showCancelButton: true, confirmButtonText: 'حذف', cancelButtonText: 'انصراف'})
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({url: form.action, method: 'POST', data: $(form).serialize()})
                            .done(() => window.location.reload())
                            .fail(xhr => toastr.error(errorMessage(xhr)));
                    });
            });
        })(jQuery);
    </script>
@endsection
