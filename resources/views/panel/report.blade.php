@extends('layouts.base')

@section('title')
    <title>{{ $thispage['title'] }}</title>
@endsection
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
@endsection
@section('content')
    <style>
        .report-wrap { direction: rtl; }
        .report-title { margin-bottom: 6px; font-weight: 700; }
        .report-subtitle { margin-top: 0; opacity: .75; }

        .kpi-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 18px;
        }
        .kpi-col { flex: 1 1 220px; }

        .kpi-card {
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(17,24,39,.08) !important;
        }
        .kpi-card .card-content { padding: 16px 16px; }
        .kpi-value { font-size: 22px; font-weight: 800; margin: 0; }
        .kpi-label { margin: 6px 0 0; opacity: .9; }

        .report-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }
        .report-col { flex: 1 1 calc(33.333% - 14px); min-width: 340px; }

        .report-card {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(17,24,39,.08) !important;
            overflow: hidden;
        }
        .report-card .card-content { padding: 16px 16px 10px; }
        .card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .card-head h6 { margin:0; font-weight:800; font-size: 14px; color: #1f2937; }
        .card-hint { font-size: 12px; opacity: .65; }

        /* fixed height for chart areas */
        .chart-box { position: relative; height: 240px; }
        .chart-box.tall { height: 280px; }
        .chart-box.full { height: 320px; }

        /* make canvas fill */
        .chart-box canvas { width: 100% !important; height: 100% !important; }

        @media (max-width: 1100px) { .report-col { flex: 1 1 calc(50% - 14px); } }
        @media (max-width: 700px)  { .report-col { flex: 1 1 100%; min-width: unset; } }
    </style>

    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-email.css') }}" />

    <div class="report-wrap">

        <div class="row" style="margin-bottom:10px;">
            <div class="col s12">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h4 class="report-title">{{ $thispage['title'] }}</h4>
                        <p class="report-subtitle">نمای کلی از قیف پذیرش، وضعیت پورتفو، عملکرد و بازدهی سرمایه‌گذاری.</p>
                    </div>
                    <a href="{{ route('activitylog.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-history me-1"></i> ردیابی فعالیت‌ها
                    </a>
                </div>
            </div>
        </div>

        {{-- شاخص‌های کلان پورتفو --}}
        <div class="kpi-row">
            <div class="kpi-col"><div class="card kpi-card bg-label-primary"><div class="card-content text-center"><p class="kpi-value">{{ number_format($reportCounts['projects']) }}</p><p class="kpi-label">کل طرح‌های ثبت‌شده</p></div></div></div>
            <div class="kpi-col"><div class="card kpi-card bg-label-danger"><div class="card-content text-center"><p class="kpi-value">{{ number_format($reportCounts['rejected']) }}</p><p class="kpi-label">طرح‌های ردشده</p></div></div></div>
            <div class="kpi-col"><div class="card kpi-card bg-label-info"><div class="card-content text-center"><p class="kpi-value">{{ number_format($reportCounts['active']) }}</p><p class="kpi-label">طرح‌های جاری</p></div></div></div>
            <div class="kpi-col"><div class="card kpi-card bg-label-success"><div class="card-content text-center"><p class="kpi-value">{{ number_format($reportCounts['completed']) }}</p><p class="kpi-label">طرح‌های تکمیل‌شده</p></div></div></div>
        </div>

        {{-- شاخص‌های مالی --}}
        <div class="kpi-row">
            <div class="kpi-col"><div class="card kpi-card"><div class="card-content text-center"><p class="kpi-value">{{ number_format($totalContract) }}</p><p class="kpi-label">ارزش قراردادهای پورتفو</p></div></div></div>
            <div class="kpi-col"><div class="card kpi-card"><div class="card-content text-center"><p class="kpi-value">{{ number_format($totalPaid) }}</p><p class="kpi-label">سرمایه پرداخت‌شده</p></div></div></div>
            <div class="kpi-col"><div class="card kpi-card"><div class="card-content text-center"><p class="kpi-value">{{ number_format($remainingCommitment) }}</p><p class="kpi-label">مانده تعهد سرمایه‌گذاری</p></div></div></div>
            <div class="kpi-col"><div class="card kpi-card"><div class="card-content text-center"><p class="kpi-value">{{ number_format($financialSummary['net_profit']) }}</p><p class="kpi-label">سود/زیان خالص آخرین دوره{{ $financialSummary['period'] ? ' - '.$financialSummary['period'] : '' }}</p></div></div></div>
        </div>

        @include('panel.partials.operational-report')
        <div class="card" style="margin:15px;padding: 40px;">
            <div class="card-content">
                <form method="GET" action="{{ route('report.index') }}">
                    <div class="row">

                        {{-- شرکت --}}
                        {{-- شرکت --}}
                        <div class="input-field col s12 m4">
                            <select name="project_id" class="form-control">
                                <option value="">همه شرکت‌ها</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ request('project_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->company_name }} - {{ $company->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- از تاریخ --}}
                        <div class="input-field col s12 m3">
                            <input type="text" data-jdp class="form-control" autocomplete="off"
                                   id="from_date" name="from_date" placeholder="از تاریخ"
                                   value="{{ request('from_date') }}">
                        </div>

                        {{-- تا تاریخ --}}
                        <div class="input-field col s12 m3">
                            <input type="text" data-jdp class="form-control" autocomplete="off"
                                   id="to_date" name="to_date" placeholder="تا تاریخ"
                                   value="{{ request('to_date') }}">
                        </div>

                        <div class="input-field col s12 m2">
                            <button class="btn" type="submit">اعمال فیلتر</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        {{-- Charts --}}
        <div class="report-grid">

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>روند فروش خالص</h6></div>
                        <div class="chart-box">
                            <canvas id="netSalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>توزیع سرمایه‌گذاری در پورتفو</h6></div>
                        <div class="chart-box">
                            <canvas id="sectorAllocationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>نسبت بهای تمام‌شده به فروش</h6></div>
                        <div class="chart-box">
                            <canvas id="cogsRatioChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>حاشیه سود ناخالص</h6></div>
                        <div class="chart-box">
                            <canvas id="grossMarginChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>نسبت هزینه اداری و فروش</h6></div>
                        <div class="chart-box">
                            <canvas id="sgaRatioChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>ترکیب دارایی‌های جاری</h6></div>
                        <div class="chart-box">
                            <canvas id="currentAssetRatioChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>نقدینگی (Current Ratio)</h6></div>
                        <div class="chart-box">
                            <canvas id="currentRatioChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>ریسک مالی (بدهی به سرمایه)</h6></div>
                        <div class="chart-box">
                            <canvas id="debtToEquityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>بازده دارایی (ROA)</h6></div>
                        <div class="chart-box">
                            <canvas id="roaChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>کیفیت سود</h6></div>
                        <div class="chart-box">
                            <canvas id="profitQualityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head"><h6>کنترل ترازنامه</h6></div>
                        <div class="chart-box">
                            <canvas id="balanceCheckChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="card report-card mt-4">
            <div class="card-content">
                <div class="card-head">
                    <h6>صورت‌وضعیت مالی شرکت‌های پورتفو</h6>
                    <span class="card-hint">مبالغ بر اساس اطلاعات ثبت‌شده در پرونده سرمایه‌گذاری</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>شرکت / طرح</th>
                            <th>پیشرفت / مرحله</th>
                            <th>ریسک SLA</th>
                            <th>قرارداد</th>
                            <th>پرداخت‌شده</th>
                            <th>مانده</th>
                            <th>٪ تأمین</th>
                            <th>آخرین دوره</th>
                            <th>فروش خالص</th>
                            <th>سود خالص</th>
                            <th>نسبت جاری</th>
                            <th>بدهی/حقوق مالکانه</th>
                            <th>ROA</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($portfolioRows as $row)
                            <tr>
                                <td><strong>{{ $row['company_name'] ?: '—' }}</strong><div class="small text-muted">{{ $row['project_title'] }}</div></td>
                                <td style="min-width:150px">
                                    <div class="d-flex align-items-center gap-2"><div class="progress flex-grow-1" style="height:7px"><div class="progress-bar" style="width:{{ min(100, max(0, (int)$row['progress_percentage'])) }}%"></div></div><span>{{ (int)$row['progress_percentage'] }}٪</span></div>
                                    <div class="small text-muted mt-1">{{ $row['current_step'] ?: '—' }}</div>
                                </td>
                                <td>
                                    @if(($row['overdue_kpis'] + $row['overdue_commitments']) > 0)
                                        <span class="badge bg-label-danger">{{ $row['overdue_kpis'] }} KPI / {{ $row['overdue_commitments'] }} تعهد</span>
                                    @else
                                        <span class="badge bg-label-success">عادی</span>
                                    @endif
                                </td>
                                <td>{{ number_format($row['contract_amount']) }}</td>
                                <td>{{ number_format($row['paid_amount']) }}</td>
                                <td>{{ number_format($row['remaining_amount']) }}</td>
                                <td>{{ number_format($row['funding_percent'], 1) }}٪</td>
                                <td>{{ $row['latest_period'] ?: '—' }}</td>
                                <td>{{ number_format($row['net_sales']) }}</td>
                                <td class="{{ $row['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($row['net_profit']) }}</td>
                                <td>{{ number_format($row['current_ratio'], 2) }}</td>
                                <td>{{ number_format($row['debt_to_equity'], 2) }}</td>
                                <td>{{ number_format($row['roa'], 1) }}٪</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center text-muted py-4">اطلاعات مالی برای فیلتر انتخاب‌شده ثبت نشده است.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card report-card mt-4">
            <div class="card-content">
                <div class="card-head">
                    <div>
                        <h6>عملکرد فصلی و تحقق KPIهای قراردادی</h6>
                        <span class="card-hint">امتیاز تحقق به‌صورت میانگین موزون KPIهای دارای اندازه‌گیری محاسبه می‌شود.</span>
                    </div>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('report.export', ['type' => 'performance'] + request()->only(['project_id', 'year', 'quarter', 'status'])) }}">
                        خروجی CSV
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light"><tr><th>شرکت / طرح</th><th>دوره</th><th>نسخه</th><th>وضعیت</th><th>KPI</th><th>امتیاز تحقق</th><th>زیر هدف</th><th>ریسک‌ها</th></tr></thead>
                        <tbody>
                        @forelse($performanceRows as $row)
                            <tr>
                                <td><strong>{{ $row['company_name'] ?: '—' }}</strong><div class="small text-muted">{{ $row['project_title'] }}</div></td>
                                <td>{{ $row['year'] }} / فصل {{ $row['quarter'] }}</td>
                                <td>{{ $row['revision'] }}</td>
                                <td><span class="badge bg-label-{{ $row['status'] === 'approved' ? 'success' : ($row['status'] === 'rejected' ? 'danger' : 'warning') }}">{{ $row['status'] }}</span></td>
                                <td>{{ $row['measurements_count'] }}</td>
                                <td>{{ $row['achievement_score'] === null ? '—' : number_format($row['achievement_score'], 1).'٪' }}</td>
                                <td>{{ $row['below_target_count'] }}</td>
                                <td>{{ $row['risks_count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">گزارش عملکرد فصلی ثبت نشده است.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
    <script src="{{asset('assets/vendor/libs/block-ui/block-ui.js')}}"></script>
    <script src="{{ asset('assets/vendor/js/formhandler.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    {{-- ================================ --}}
    {{-- نکته اصلی: همه نمودارها از یک labels مشترک استفاده می‌کنند --}}
    {{-- labels = بازه زمانی (سال/ماه) --}}
    {{-- ================================ --}}

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (!window.Chart) return;

            Chart.defaults.font.family = 'Vazirmatn, IRANSans, system-ui';
            Chart.defaults.color = '#374151';

            const gridColor   = 'rgba(17,24,39,.06)';
            const borderColor = 'rgba(17,24,39,.12)';

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { grid: { color: gridColor }, border: { color: borderColor } }
                }
            };

            const progressDistributionCanvas = document.getElementById('operationalProgressDistributionChart');
            if (progressDistributionCanvas) {
                new Chart(progressDistributionCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: @json($operationalAnalytics['progress_distribution']['labels'] ?? []),
                        datasets: [{
                            data: @json($operationalAnalytics['progress_distribution']['data'] ?? []),
                            backgroundColor: ['rgba(14,165,233,.85)','rgba(99,102,241,.85)','rgba(16,185,129,.85)','rgba(249,115,22,.85)','rgba(34,197,94,.85)','rgba(244,63,94,.80)'],
                            borderWidth: 0
                        }]
                    },
                    options: {responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: {legend: {position: 'bottom'}}}
                });
            }

            const stepWorkloadCanvas = document.getElementById('operationalStepWorkloadChart');
            if (stepWorkloadCanvas) {
                new Chart(stepWorkloadCanvas, {
                    type: 'bar',
                    data: {
                        labels: @json($operationalAnalytics['step_workload']['labels'] ?? []),
                        datasets: [{
                            label: 'پروژه جاری',
                            data: @json($operationalAnalytics['step_workload']['data'] ?? []),
                            backgroundColor: 'rgba(99,102,241,.85)',
                            borderRadius: 8
                        }]
                    },
                    options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true}}}
                });
            }

            const labels = @json($netSales['labels']);

            const lineChart = (id, data, color, unit) =>
                new Chart(document.getElementById(id), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            borderColor: color,
                            backgroundColor: color.replace('1)', '.12)'),
                            fill: true,
                            tension: .35,
                            pointRadius: 2
                        }]
                    },
                    options: {
                        ...baseOptions,
                        scales: {
                            ...baseOptions.scales,
                            y: {
                                ...baseOptions.scales.y,
                                beginAtZero: false, // مهم برای نمایش منفی‌ها
                                title: { display: true, text: unit }
                            }
                        }
                    },
                    plugins: [{
                        id: 'force-ltr',
                        beforeInit: chart => {
                            chart.ctx.canvas.style.direction = 'ltr';
                        }
                    }]
                });

            const barChart = (id, data, color, unit) =>
                new Chart(document.getElementById(id), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: color,
                            borderRadius: 10
                        }]
                    },
                    options: {
                        ...baseOptions,
                        scales: {
                            ...baseOptions.scales,
                            y: {
                                ...baseOptions.scales.y,
                                beginAtZero: false, // مقادیر منفی را به درستی نمایش دهد
                                title: { display: true, text: unit }
                            }
                        }
                    },
                    plugins: [{
                        id: 'force-ltr',
                        beforeInit: chart => {
                            chart.ctx.canvas.style.direction = 'ltr';
                        }
                    }]
                });

            new Chart(document.getElementById('sectorAllocationChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($sectorAllocation['labels']),
                    datasets: [{
                        data: @json($sectorAllocation['data']),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: context => `${context.label}: ${context.raw}%` } }
                    }
                }
            });

            // ================================
            // KPI Charts با واحد اندازه‌گیری
            // ================================
            lineChart('netSalesChart',          @json($netSales['data']),          'rgba(14,165,233,1)', 'ریال');
            lineChart('cogsRatioChart',         @json($cogsRatio['data']),         'rgba(244,63,94,1)', 'درصد');
            lineChart('grossMarginChart',       @json($grossMargin['data']),       'rgba(16,185,129,1)', 'درصد');
            barChart ('sgaRatioChart',          @json($sgaRatio['data']),          'rgba(99,102,241,.85)', 'درصد');
            lineChart('currentAssetRatioChart', @json($currentAssetRatio['data']), 'rgba(14,165,233,1)', 'درصد');
            lineChart('currentRatioChart',      @json($currentRatio['data']),      'rgba(16,185,129,1)', 'نسبت');
            barChart ('debtToEquityChart',      @json($debtToEquity['data']),      'rgba(249,115,22,.85)', 'نسبت');
            lineChart('roaChart',               @json($roa['data']),               'rgba(99,102,241,1)', 'درصد');
            lineChart('profitQualityChart',     @json($profitQuality['data']),     'rgba(14,165,233,1)', 'درصد');
            barChart ('balanceCheckChart',      @json($balanceCheck['data']),      'rgba(244,63,94,.75)', 'ریال');

            if (window.jQuery && jQuery.fn.DataTable && document.getElementById('expertPerformanceTable')) {
                jQuery('#expertPerformanceTable').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: true,
                    pageLength: 10,
                    ajax: {
                        url: @json(route('report.experts.data')),
                        data: function (data) {
                            data.project_id = @json(request('project_id'));
                        }
                    },
                    columns: [
                        {data: 'name', name: 'u.name'},
                        {data: 'role_slugs', name: 'role_slugs', orderable: false, searchable: false},
                        {data: 'assignments_count', name: 'assignments_count', searchable: false},
                        {data: 'active_assignments_count', name: 'active_assignments_count', searchable: false},
                        {data: 'projects_count', name: 'projects_count', searchable: false},
                        {data: 'decisions_count', name: 'decisions_count', searchable: false},
                        {data: 'approved_count', name: 'approved_count', searchable: false},
                        {data: 'rejected_count', name: 'rejected_count', searchable: false},
                        {data: 'risk_status', name: 'risk_projects_count', searchable: false}
                    ],
                    order: [[5, 'desc']],
                    language: {
                        search: 'جستجو:',
                        lengthMenu: 'نمایش _MENU_ ردیف',
                        info: 'نمایش _START_ تا _END_ از _TOTAL_ ردیف',
                        infoEmpty: 'اطلاعاتی وجود ندارد',
                        zeroRecords: 'رکوردی یافت نشد',
                        processing: 'در حال پردازش...',
                        paginate: {first: 'ابتدا', last: 'انتها', next: 'بعدی', previous: 'قبلی'}
                    }
                });
            }
        });
    </script>
@endpush
