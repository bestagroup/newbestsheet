@extends('layouts.base')

@section('title')
    <title>{{ $thispage['title'] }}</title>
@endsection
@section('style')
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
                <h4 class="report-title">{{ $thispage['title'] }}</h4>
                <p class="report-subtitle">نمای کلی از قیف پذیرش، وضعیت پورتفو، عملکرد و بازدهی سرمایه‌گذاری.</p>
            </div>
        </div>

        {{-- KPI --}}
        <div class="kpi-row">
            <div class="kpi-col">
                <div class="card kpi-card" style="background:#0ea5e9;color:#fff;">
                    <div class="card-content center">
                        <p class="kpi-value">{{number_format(DB::table('projects')->count())}}</p>
                        <p class="kpi-label">کل طرح های ثبت شده</p>
                    </div>
                </div>
            </div>

            <div class="kpi-col">
                <div class="card kpi-card" style="background:#10b981;color:#fff;">
                    <div class="card-content center">
                        <p class="kpi-value">{{DB::table('projects')->where('is_rejected' , 1)->count()}}</p>
                        <p class="kpi-label"> کل طرح های رد شده</p>
                    </div>
                </div>
            </div>

            <div class="kpi-col">
                <div class="card kpi-card" style="background:#6366f1;color:#fff;">
                    <div class="card-content center">
                        <p class="kpi-value">{{DB::table('projects')->where('is_rejected' , 0)->count()}}</p>
                        <p class="kpi-label">کل طرح های جاری</p>
                    </div>
                </div>
            </div>

            <div class="kpi-col">
                <div class="card kpi-card" style="background:#f97316;color:#fff;">
                    <div class="card-content center">
                        <p class="kpi-value">{{DB::table('projects')->where('invest_step' , 20)->count()}}</p>
                        <p class="kpi-label">کل طرح های خاتمه یافته</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card" style="margin:15px;padding: 40px;">
            <div class="card-content">
                <form method="GET" action="{{ route('report.index') }}">
                    <div class="row">

                        {{-- شرکت --}}
                        <div class="input-field col s12 m4">
                            <select name="company_id" class="form-control">
                                <option value="">همه شرکت‌ها</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ request('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->title }}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        {{-- از تاریخ --}}
                        <div class="input-field col s12 m3">
                            <input type="text" data-jdp class="form-control" autocomplete="off" id="from_date" name="from_date" placeholder="از تاریخ " >
                        </div>

                        {{-- تا تاریخ --}}
                        <div class="input-field col s12 m3">
                            <input type="text" data-jdp class="form-control" autocomplete="off" id="to_date" name="to_date" placeholder="تا تاریخ " >
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
                        <div class="card-head">
                            <h6>نمودار موقعیت طرح های رد شده</h6>
                        </div>
                        <div class="chart-box">
                            <canvas id="dealFunnelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>نمودار وضعیت طرح ها</h6>
                        </div>
                        <div class="chart-box">
                            <canvas id="strategicFitChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>توزیع سرمایه بر اساس طرح</h6>
                        </div>
                        <div class="chart-box">
                            <canvas id="sectorAllocationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>سرمایه‌گذاری بر اساس مرحله</h6>
                        </div>
                        <div class="chart-box">
                            <canvas id="stageAllocationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>روند KPIهای پورتفو</h6>
                            <span class="card-hint">MRR / Burn / Runway</span>
                        </div>
                        <div class="chart-box tall">
                            <canvas id="kpiTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>سلامت پورتفو</h6>
                            <span class="card-hint">ریسک و آمادگی خروج</span>
                        </div>
                        <div class="chart-box">
                            <canvas id="portfolioHealthChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>تایم‌لاین خروج‌ها</h6>
                            <span class="card-hint">تعداد + ارزش خروج</span>
                        </div>
                        <div class="chart-box">
                            <canvas id="exitTimelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>عملکرد شرکت‌ها</h6>
                        </div>
                        <div class="chart-box">
                            <canvas id="companyPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col" style="flex:1 1 100%;">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>بازدهی سرمایه (TVPI / DPI / RVPI)</h6>
                            <span class="card-hint">روند تجمیعی صندوق</span>
                        </div>
                        <div class="chart-box full">
                            <canvas id="fundMetricsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="report-col" style="flex:1 1 100%;">
                <div class="card report-card hoverable">
                    <div class="card-content">
                        <div class="card-head">
                            <h6>وضعیت نقدینگی شرکت</h6>
                        </div>
                        <div class="chart-box full">
                            <canvas id="liquidityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (!window.Chart) return;

            // ---------- Global minimal defaults ----------
            Chart.defaults.font.family = 'Vazirmatn, IRANSans, system-ui, -apple-system, Segoe UI, Roboto';
            Chart.defaults.color = '#374151';
            Chart.defaults.plugins.legend.labels.usePointStyle = true;
            Chart.defaults.plugins.legend.labels.boxWidth = 8;
            Chart.defaults.plugins.legend.labels.boxHeight = 8;

            const gridColor = 'rgba(17,24,39,.06)';
            const borderColor = 'rgba(17,24,39,.12)';

            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        backgroundColor: 'rgba(17,24,39,.92)',
                        padding: 10,
                        cornerRadius: 10,
                        titleColor: '#fff',
                        bodyColor: '#fff'
                    }
                }
            };

            // 1) Deal Funnel
            new Chart(document.getElementById('dealFunnelChart'), {
                type: 'bar',
                data: {
                    labels: @json($dealFunnel['labels']),
                    datasets: [{
                        label: 'تعداد',
                        data: @json($dealFunnel['data']),
                        borderRadius: 10,
                        backgroundColor: 'rgba(14,165,233,.85)'
                    }]
                },
                options: {
                    ...baseOptions,
                    indexAxis: 'y',
                    scales: {
                        x: { grid: { color: gridColor }, border: { color: borderColor } },
                        y: { grid: { display: false }, border: { display: false } }
                    },
                    plugins: { ...baseOptions.plugins, legend: { display: false } }
                }
            });

            // 2) Strategic Fit
            new Chart(document.getElementById('strategicFitChart'), {
                type: 'bar',
                data: {
                    labels: @json($strategicFit['labels']),
                    datasets: [{
                        label: 'تعداد',
                        data: @json($strategicFit['data']),
                        borderRadius: 10,
                        backgroundColor: 'rgba(99,102,241,.85)'
                    }]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: gridColor }, border: { color: borderColor } }
                    },
                    plugins: { ...baseOptions.plugins, legend: { display: false } }
                }
            });

            // 3) Sector Allocation
            new Chart(document.getElementById('sectorAllocationChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($sectorAllocation['labels']),
                    datasets: [{
                        data: @json($sectorAllocation['data']),
                        backgroundColor: [
                            'rgba(14,165,233,.85)',
                            'rgba(16,185,129,.85)',
                            'rgba(249,115,22,.85)',
                            'rgba(99,102,241,.85)',
                            'rgba(244,63,94,.75)',
                            'rgba(148,163,184,.85)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    ...baseOptions,
                    cutout: '70%',
                    plugins: { ...baseOptions.plugins, legend: { position: 'bottom' } }
                }
            });

            // 4) Stage Allocation
            new Chart(document.getElementById('stageAllocationChart'), {
                type: 'bar',
                data: {
                    labels: @json($stageAllocation['labels']),
                    datasets: [{
                        label: 'تعداد',
                        data: @json($stageAllocation['data']),
                        borderRadius: 10,
                        backgroundColor: 'rgba(16,185,129,.85)'
                    }]
                },
                options: {
                    ...baseOptions,
                    plugins: { ...baseOptions.plugins, legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: gridColor }, border: { color: borderColor } }
                    }
                }
            });

            // 5) KPI Trend
            new Chart(document.getElementById('kpiTrendChart'), {
                type: 'line',
                data: {
                    labels: @json($portfolioKpi['months']),
                    datasets: [
                        {
                            label: 'MRR',
                            data: @json($portfolioKpi['mrr']),
                            borderColor: 'rgba(14,165,233,1)',
                            backgroundColor: 'rgba(14,165,233,.12)',
                            fill: true,
                            tension: .35,
                            pointRadius: 2
                        },
                        {
                            label: 'Burn',
                            data: @json($portfolioKpi['burn']),
                            borderColor: 'rgba(244,63,94,1)',
                            backgroundColor: 'rgba(244,63,94,.10)',
                            fill: true,
                            tension: .35,
                            pointRadius: 2
                        },
                        {
                            label: 'Runway (ماه)',
                            data: @json($portfolioKpi['runway']),
                            borderColor: 'rgba(249,115,22,1)',
                            backgroundColor: 'rgba(249,115,22,.10)',
                            fill: true,
                            tension: .35,
                            pointRadius: 2
                        }
                    ]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: gridColor }, border: { color: borderColor } }
                    }
                }
            });

            // 6) Portfolio Health
            new Chart(document.getElementById('portfolioHealthChart'), {
                type: 'polarArea',
                data: {
                    labels: @json($portfolioHealth['labels']),
                    datasets: [{
                        data: @json($portfolioHealth['data']),
                        backgroundColor: [
                            'rgba(16,185,129,.70)',
                            'rgba(14,165,233,.70)',
                            'rgba(249,115,22,.65)',
                            'rgba(244,63,94,.60)',
                            'rgba(99,102,241,.70)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    ...baseOptions,
                    scales: { r: { grid: { color: gridColor }, ticks: { display: false } } }
                }
            });

            // 7) Exit Timeline (bar + line)
            new Chart(document.getElementById('exitTimelineChart'), {
                data: {
                    labels: @json($exitTimeline['labels']),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'تعداد خروج',
                            data: @json($exitTimeline['count']),
                            backgroundColor: 'rgba(99,102,241,.80)',
                            borderRadius: 10
                        },
                        {
                            type: 'line',
                            label: 'ارزش خروج',
                            data: @json($exitTimeline['value']),
                            borderColor: 'rgba(249,115,22,1)',
                            backgroundColor: 'rgba(249,115,22,.12)',
                            fill: true,
                            tension: .35,
                            pointRadius: 2,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: gridColor }, border: { color: borderColor }, beginAtZero: true },
                        y1: { position: 'right', grid: { display: false }, beginAtZero: true }
                    }
                }
            });

            // 8) Company Performance (IRR + MoM)
            new Chart(document.getElementById('companyPerformanceChart'), {
                data: {
                    labels: @json($companyPerformance['labels']),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'IRR (%)',
                            data: @json($companyPerformance['irr']),
                            backgroundColor: 'rgba(16,185,129,.80)',
                            borderRadius: 10
                        },
                        {
                            type: 'line',
                            label: 'رشد ماهانه (%)',
                            data: @json($companyPerformance['mom']),
                            borderColor: 'rgba(14,165,233,1)',
                            backgroundColor: 'rgba(14,165,233,.10)',
                            fill: true,
                            tension: .35,
                            pointRadius: 2,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: gridColor }, border: { color: borderColor }, beginAtZero: true },
                        y1: { position: 'right', grid: { display: false }, beginAtZero: true }
                    }
                }
            });

            // 9) Fund Metrics
            new Chart(document.getElementById('fundMetricsChart'), {
                type: 'line',
                data: {
                    labels: @json($fundMetrics['labels']),
                    datasets: [
                        { label: 'TVPI', data: @json($fundMetrics['tvpi']), borderColor: 'rgba(14,165,233,1)', backgroundColor:'rgba(14,165,233,.10)', fill:true, tension:.35, pointRadius:2 },
                        { label: 'DPI',  data: @json($fundMetrics['dpi']),  borderColor: 'rgba(16,185,129,1)', backgroundColor:'rgba(16,185,129,.10)', fill:true, tension:.35, pointRadius:2 },
                        { label: 'RVPI', data: @json($fundMetrics['rvpi']), borderColor: 'rgba(244,63,94,1)',  backgroundColor:'rgba(244,63,94,.10)',  fill:true, tension:.35, pointRadius:2 }
                    ]
                },
                options: {
                    ...baseOptions,
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { grid: { color: gridColor }, border: { color: borderColor }, beginAtZero: false }
                    }
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (!window.Chart) return;

            const liquidityCtx = document.getElementById('liquidityChart').getContext('2d');

            new Chart(liquidityCtx, {
                type: 'line',
                data: {
                    labels: @json($labels),
                    datasets: [
                        {
                            label: 'وجه نقد و معادل نقد',
                            data: @json($cashAndEquivalents),
                            borderWidth: 2,
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'دارایی‌های جاری',
                            data: @json($totalCurrentAssets),
                            borderWidth: 2,
                            tension: 0.35,
                            fill: false
                        },
                        {
                            label: 'بدهی‌های جاری',
                            data: @json($totalCurrentLiabilities),
                            borderWidth: 2,
                            tension: 0.35,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.dataset.label + ': ' +
                                        Number(context.raw).toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <script src="{{asset('assets/vendor/libs/block-ui/block-ui.js')}}"></script>
    <script src="{{ asset('assets/vendor/js/formhandler.js') }}"></script>

@endpush
