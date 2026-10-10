@extends('layouts.base')

@section('title')
    <title>{{ $thispage['title'] }}</title>
@endsection
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
@endsection
@section('content')
@php
    $fundingChartRows = collect($portfolioRows ?? [])
        ->sort(fn ($a, $b) => \App\Support\Monetary::value($b['contract_amount'])
            ->compareTo(\App\Support\Monetary::value($a['contract_amount'])))
        ->take(10)->values()->all();
@endphp
@include('panel.partials.governance-report')
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

        <div class="kpi-row">
            @foreach(['projects' => 'کل طرح‌های ثبت‌شده', 'active' => 'کل طرح‌های جاری', 'rejected' => 'کل طرح‌های ردشده', 'portfolio' => 'کل طرح‌های فعال پورتفو'] as $key => $label)
                <div class="kpi-col"><div class="card kpi-card"><div class="card-content text-center"><p class="kpi-value">{{ number_format($investmentSummary['counts'][$key]) }}</p><p class="kpi-label">{{ $label }}</p></div></div></div>
            @endforeach
        </div>
        <p class="text-muted small">شمارنده‌ها شامل تمام طرح‌های سامانه و مستقل از فیلترها هستند؛ طرح جاری: ردنشده قبل از مرحله ۶؛ پورتفوی فعال: مراحل ۱۴ تا ۱۹ و ردنشده. این گروه‌ها جمع‌پذیر نیستند.</p>
        <div class="kpi-row">
            @foreach(['contract' => 'کل مبلغ قراردادها', 'paid' => 'کل مبلغ پرداخت‌شده', 'remaining' => 'کل مبلغ مانده تعهدات'] as $key => $label)
                <div class="kpi-col"><div class="card kpi-card"><div class="card-content text-center"><p class="kpi-value">{{ \App\Support\Monetary::format($investmentSummary[$key]) }}</p><p class="kpi-label">{{ $label }}</p><small class="text-muted">ریال</small></div></div></div>
            @endforeach
            <div class="kpi-col"><div class="card kpi-card"><div class="card-content text-center">
                <p class="kpi-value">{{ $investmentSummary['profit'] === null ? '—' : \App\Support\Monetary::format($investmentSummary['profit']) }}</p>
                <p class="kpi-label">سود و زیان آخرین دوره کل شرکت‌ها</p>
                <small class="text-muted">ریال؛ {{ $investmentSummary['reported_companies'] }} شرکت دارای صورت مالی و {{ $investmentSummary['missing_companies'] }} شرکت فاقد صورت مالی از نوع انتخاب‌شده</small>
                @if($investmentSummary['mixed_periods'])<small class="text-warning d-block">آخرین دوره شرکت‌ها یکسان نیست.</small>@endif
            </div></div></div>
        </div>
        <p class="text-muted small">مبالغ مربوط به پورتفوی فعال در محدوده دسترسی شما و تجمعی تا اکنون هستند؛ مستقل از فیلتر شرکت و تاریخ. سود و زیان جمع آخرین صورت مالی هر شرکت از نوع دوره انتخاب‌شده است. مانده منفی نشان‌دهنده پرداخت بیش از مجموع قراردادهاست.</p>

        @include('panel.partials.operational-report')
        @include('panel.partials.board-financial-charts')

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
                            <th title="سود خالص تقسیم بر دارایی پایان دوره؛ مبتنی بر متوسط دارایی نیست">سود/دارایی پایان دوره</th>
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
                                <td>{{ \App\Support\Monetary::format($row['contract_amount']) }}</td>
                                <td>{{ \App\Support\Monetary::format($row['paid_amount']) }}</td>
                                <td>{{ \App\Support\Monetary::format($row['remaining_amount']) }}</td>
                                <td>{{ number_format($row['funding_percent'], 1) }}٪</td>
                                <td>{{ $row['latest_period'] ?: '—' }}</td>
                                <td>{{ \App\Support\Monetary::format($row['net_sales']) }}</td>
                                <td class="{{ $row['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Support\Monetary::format($row['net_profit']) }}</td>
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

    <script src="{{ asset('assets/js/pages/investment-financial-charts.js') }}"></script>
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

            const fundingCanvas = document.getElementById('portfolioFundingChart');
            if (fundingCanvas) {
                const rows = {{ Illuminate\Support\Js::from($fundingChartRows) }};
                const formatRial = value => String(value || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '٬').replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]) + ' ریال';
                new Chart(fundingCanvas, {
                    type: 'bar',
                    data: {
                        labels: rows.map(row => row.project_title),
                        datasets: [
                            {label: 'مبلغ قرارداد', data: rows.map(row => Number(row.contract_amount) / 1e9), backgroundColor: '#183153', borderRadius: 4},
                            {label: 'پرداخت انجام‌شده', data: rows.map(row => Number(row.paid_amount) / 1e9), backgroundColor: '#16866b', borderRadius: 4}
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        interaction: {mode: 'index', intersect: false},
                        plugins: {
                            legend: {position: 'bottom', rtl: true},
                            tooltip: {
                                rtl: true,
                                callbacks: {
                                    title: items => items.length ? rows[items[0].dataIndex].project_title : '',
                                    label: context => context.dataset.label + ': ' + formatRial(rows[context.dataIndex][context.datasetIndex === 0 ? 'contract_amount' : 'paid_amount'])
                                }
                            }
                        },
                        scales: {
                            x: {beginAtZero: true, title: {display: true, text: 'میلیارد ریال'}, ticks: {callback: value => Number(value).toLocaleString('fa-IR')}},
                            y: {grid: {display: false}, ticks: {autoSkip: false, callback: function(value) {
                                const label = this.getLabelForValue(value);
                                return label.length > 30 ? label.slice(0, 30) + '…' : label;
                            }}}
                        }
                    }
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

            window.BestsheetFinancialCharts.init({{ \Illuminate\Support\Js::from($boardCharts['charts']) }});

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
