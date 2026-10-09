@extends('layouts.base')

@section('title', 'داشبورد شخصی و عملکرد حوزه کاری')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/dashboard-professional.css') }}">
    <style>
        .dashboard-shell { --dash-border: rgba(15, 23, 42, .08); --dash-muted: #64748b; }
        .dashboard-hero {
            background: linear-gradient(135deg, #172554 0%, #1d4ed8 55%, #0ea5e9 100%);
            border-radius: 22px;
            color: #fff;
            overflow: hidden;
            position: relative;
        }
        .dashboard-hero::after {
            background: rgba(255, 255, 255, .08);
            border-radius: 50%;
            content: '';
            height: 240px;
            left: -70px;
            position: absolute;
            top: -100px;
            width: 240px;
        }
        .section-heading { display: flex; align-items: center; gap: 12px; }
        .section-number {
            align-items: center;
            background: #e0e7ff;
            border-radius: 12px;
            color: #4338ca;
            display: inline-flex;
            font-size: 1rem;
            font-weight: 800;
            height: 42px;
            justify-content: center;
            width: 42px;
        }
        .dashboard-card { border: 1px solid var(--dash-border); border-radius: 18px; box-shadow: 0 10px 28px rgba(15, 23, 42, .05); }
        .profile-avatar {
            align-items: center;
            background: linear-gradient(145deg, #4f46e5, #0ea5e9);
            border-radius: 18px;
            color: #fff;
            display: inline-flex;
            font-size: 1.45rem;
            font-weight: 800;
            height: 72px;
            justify-content: center;
            width: 72px;
        }
        .identity-row { border-bottom: 1px dashed var(--dash-border); padding: 10px 0; }
        .identity-row:last-child { border-bottom: 0; }
        .meeting-item, .activity-item, .domain-item, .assignment-item { border-bottom: 1px solid var(--dash-border); padding: 13px 0; }
        .meeting-item:last-child, .activity-item:last-child, .domain-item:last-child, .assignment-item:last-child { border-bottom: 0; }
        .meeting-date {
            background: #eff6ff;
            border-radius: 12px;
            color: #1d4ed8;
            flex: 0 0 118px;
            font-size: .78rem;
            padding: 8px;
            text-align: center;
        }
        .metric-card {
            border: 1px solid var(--dash-border);
            border-radius: 16px;
            height: 100%;
            padding: 16px;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .metric-card:hover { box-shadow: 0 12px 24px rgba(15, 23, 42, .08); transform: translateY(-2px); }
        .metric-icon, .domain-icon, .timeline-icon {
            align-items: center;
            border-radius: 13px;
            display: inline-flex;
            flex: 0 0 auto;
            height: 44px;
            justify-content: center;
            width: 44px;
        }
        .tone-primary { background: #eef2ff; color: #4f46e5; }
        .tone-success { background: #ecfdf5; color: #059669; }
        .tone-info { background: #eff6ff; color: #0284c7; }
        .tone-warning { background: #fff7ed; color: #ea580c; }
        .tone-danger { background: #fff1f2; color: #e11d48; }
        .tone-secondary { background: #f1f5f9; color: #64748b; }
        .metric-value { color: #0f172a; font-size: 1.32rem; font-weight: 800; line-height: 1.4; }
        .metric-hint, .dashboard-muted { color: var(--dash-muted); font-size: .82rem; }
        .domain-panel { border: 1px solid var(--dash-border); border-radius: 20px; overflow: hidden; }
        .domain-panel-header { background: linear-gradient(180deg, rgba(248, 250, 252, .95), #fff); border-bottom: 1px solid var(--dash-border); padding: 18px 20px; }
        .chart-wrap { height: 285px; position: relative; }
        .domain-items-toolbar { align-items: flex-start; display: flex; gap: 12px; justify-content: space-between; margin-bottom: 12px; }
        .domain-items-toolbar h6 { margin-bottom: 3px; }
        .domain-items-search { flex: 0 1 230px; }
        .domain-items-search .input-group { border: 1px solid var(--dash-border); border-radius: 12px; overflow: hidden; }
        .domain-items-search .input-group-text, .domain-items-search .form-control { background: transparent; border: 0; }
        .domain-items-search .form-control:focus { box-shadow: none; }
        .domain-items-scroll { max-height: 330px; overflow-y: auto; padding-inline-end: 5px; scrollbar-width: thin; }
        .domain-item { border-radius: 12px; padding: 12px !important; transition: background-color .15s ease; }
        .domain-item:hover { background: rgba(79, 70, 229, .045); }
        .domain-item__icon { align-items: center; border-radius: 10px; display: inline-flex; flex: 0 0 36px; height: 36px; justify-content: center; width: 36px; }
        .domain-item__kind { font-size: .68rem; font-weight: 700; }
        .domain-item__time { white-space: nowrap; }
        .domain-items-empty[hidden] { display: none !important; }
        .empty-state { color: var(--dash-muted); padding: 28px 12px; text-align: center; }
        .alert-work { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 14px; }
        [data-theme="dark"] .dashboard-shell { --dash-border: rgba(148, 163, 184, .18); --dash-muted: #94a3b8; }
        [data-theme="dark"] .dashboard-card, [data-theme="dark"] .domain-panel, [data-theme="dark"] .domain-panel-header { background: #111827; }
        [data-theme="dark"] .metric-value { color: #f8fafc; }
        @media (max-width: 767.98px) {
            .dashboard-hero { border-radius: 16px; }
            .meeting-date { flex-basis: 100px; }
            .chart-wrap { height: 230px; }
            .domain-items-toolbar { flex-direction: column; }
            .domain-items-search { flex-basis: auto; width: 100%; }
        }
    </style>
@endsection

@section('content')
    @php
        $initials = collect(preg_split('/\s+/u', trim($dashboardProfile['name'] ?? 'کاربر')))
            ->filter()
            ->take(2)
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->implode('');
    @endphp

    <div class="dashboard-shell" dir="rtl">
        <section class="dashboard-hero p-4 p-lg-5 mb-4">
            <div class="position-relative" style="z-index:1">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="small opacity-75 mb-2">داشبورد شخصی و حوزه کاری</div>
                        <h3 class="text-white mb-2">{{ $dashboardProfile['name'] }}، خوش آمدید</h3>
                        <p class="mb-0 opacity-75">جلسات، وظایف و گزارش‌های این صفحه دقیقاً براساس نقش و دسترسی‌های شما نمایش داده می‌شود.</p>
                    </div>
                    <div class="text-start">
                        <div class="small opacity-75">آخرین به‌روزرسانی</div>
                        <strong>{{ $generatedAt }}</strong><button type="button" class="btn btn-sm btn-light d-block mt-2 js-dashboard-refresh"><i class="mdi mdi-refresh me-1"></i>به‌روزرسانی اطلاعات</button>
                    </div>
                </div>
            </div>
        </section>

        <nav class="dashboard-nav mb-4" aria-label="دسترسی سریع داشبورد">
            <a href="#dashboard-personal">برنامه و اطلاعات من</a>
            <a href="#dashboard-work">وظایف و پیگیری‌ها</a>
            @foreach($domainSections as $section)
                <a href="#domain-{{ $section['key'] }}"><i class="mdi {{ $section['icon'] }}"></i> {{ str_replace('گزارش ', '', $section['title']) }}</a>
            @endforeach
        </nav>
        @if($errors->any())<div class="alert alert-danger" role="alert">عبارت جستجو باید متن و حداکثر ۱۲۰ نویسه باشد.</div>@endif
        <section class="mb-5" id="dashboard-personal">
            <div class="section-heading mb-3">
                <span class="section-number">۱</span>
                <div><h5 class="mb-1">اطلاعات شخصی و جلسات من</h5><div class="dashboard-muted">مشخصات هویتی، نقش سازمانی و برنامه جلسات پیش‌رو</div></div>
            </div>

            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="card dashboard-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="profile-avatar">{{ $initials ?: 'ک' }}</span>
                                <div class="min-w-0">
                                    <h5 class="mb-1">{{ $dashboardProfile['name'] }}</h5>
                                    <div class="dashboard-muted">{{ $dashboardProfile['job_title'] ?: 'عنوان شغلی ثبت نشده' }}</div>
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        @forelse($dashboardProfile['roles'] as $role)
                                            <span class="badge bg-label-primary">{{ $role }}</span>
                                        @empty
                                            <span class="badge bg-label-secondary">بدون نقش سازمانی</span>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="identity-row d-flex justify-content-between gap-3"><span class="dashboard-muted">ایمیل</span><span dir="ltr">{{ $dashboardProfile['email'] ?: '—' }}</span></div>
                            <div class="identity-row d-flex justify-content-between gap-3"><span class="dashboard-muted">تلفن همراه</span><span dir="ltr">{{ $dashboardProfile['phone'] ?: '—' }}</span></div>
                            <div class="identity-row d-flex justify-content-between gap-3"><span class="dashboard-muted">کد ملی</span><span>{{ $dashboardProfile['national_id'] ?: '—' }}</span></div>
                            <div class="identity-row d-flex justify-content-between gap-3"><span class="dashboard-muted">آخرین ورود</span><span>{{ $dashboardProfile['last_login']?->locale('fa')->diffForHumans() ?: 'ثبت نشده' }}</span></div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-2"><span class="dashboard-muted">تکمیل اطلاعات هویتی</span><strong>{{ $dashboardProfile['completion'] }}٪</strong></div>
                                <div class="progress" style="height:8px"><div class="progress-bar" style="width:{{ $dashboardProfile['completion'] }}%"></div></div>
                            </div>
                            <a class="btn btn-sm btn-label-primary mt-3" href="{{ route('profile') }}"><i class="mdi mdi-account-edit-outline me-1"></i>تکمیل یا ویرایش پروفایل</a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="card dashboard-card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div><h5 class="mb-1">جلسات و رویدادهای پیش‌رو</h5><div class="dashboard-muted">جلساتی که ایجاد کرده‌اید یا در آن‌ها دعوت شده‌اید</div></div>
                            @can('can-access', ['calendar', 'view'])<a class="btn btn-sm btn-label-info" href="{{ route('calendar.index') }}">مشاهده تقویم</a>@endcan
                        </div>
                        <div class="card-body pt-1" style="max-height: 400px;overflow: auto;">
                            @forelse($upcomingMeetings as $meeting)
                                <div class="meeting-item d-flex align-items-center gap-3">
                                    <div class="meeting-date"><i class="mdi mdi-calendar-clock-outline d-block mb-1"></i>{{ str_replace('-', '/', mb_substr($meeting->start, 0, 16)) }}</div>
                                    <div class="min-w-0">
                                        <strong class="d-block text-truncate">{{ $meeting->title }}</strong>
                                        <span class="dashboard-muted"><i class="mdi mdi-map-marker-outline me-1"></i>{{ $meeting->location ?: 'محل جلسه ثبت نشده' }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state"><i class="mdi mdi-calendar-blank-outline mdi-36px d-block mb-2"></i>جلسه‌ای برای روزهای آینده ثبت نشده است.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="dashboard-work">
            <div class="section-heading mb-3">
                <span class="section-number">۲</span>
                <div><h5 class="mb-1">عملکرد، وظایف و گزارش حوزه کاری من</h5><div class="dashboard-muted">کارهای انجام‌شده توسط شما و شاخص‌های متناسب با دسترسی‌های سازمانی</div></div>
            </div>

            <div class="row g-3 mb-4">
                @foreach($personalCards as $card)
                    <div class="col-sm-6 col-xl-3">
                        <div class="metric-card bg-card metric-tone-{{ $card['tone'] }}">
                            <div class="d-flex justify-content-between gap-3">
                                <div><div class="metric-value">{{ number_format((float) $card['value']) }}</div><strong class="d-block mt-1">{{ $card['label'] }}</strong><div class="metric-hint mt-1">{{ $card['hint'] }}</div></div>
                                <span class="metric-icon tone-{{ $card['tone'] }}"><i class="mdi {{ $card['icon'] }} mdi-24px"></i></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="card dashboard-card h-100">
                        <div class="card-header"><h5 class="mb-1">{{ $showsTeamActivity ? 'آخرین فعالیت‌های کارشناسان حوزه من' : 'آخرین فعالیت‌های انجام‌شده توسط من' }}</h5><div class="dashboard-muted">{{ $showsTeamActivity ? 'سوابق کارشناسان مرتبط با پرونده‌های قابل مشاهده شما' : 'سوابق موفق ثبت‌شده در سامانه' }}</div></div>
                        <div class="card-body pt-1" style="max-height: 400px;overflow: auto;">
                            @forelse($recentActivities as $activity)
                                <div class="activity-item d-flex gap-3">
                                    <span class="timeline-icon tone-success"><i class="mdi {{ $activity['icon'] }}"></i></span>
                                    <div class="min-w-0"><strong>{{ $activity['title'] }}</strong><div class="dashboard-muted mt-1">{{ $activity['description'] }}</div><small class="text-muted d-block mt-1"><i class="mdi mdi-account-outline"></i> {{ $activity['actor_name'] }}@if($activity['actor_role']) — {{ $activity['actor_role'] }}@endif</small><small class="text-muted">{{ $activity['created_at']?->locale('fa')->diffForHumans() }}</small></div>
                                </div>
                            @empty
                                <div class="empty-state"><i class="mdi mdi-history mdi-36px d-block mb-2"></i>هنوز فعالیت کاری مستقیمی برای شما ثبت نشده است.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="card dashboard-card h-100">
                        <div class="card-header"><h5 class="mb-1">وظایف و پرونده‌های تخصیص‌یافته به من</h5><div class="dashboard-muted">تخصیص‌های فعال در فرایند سرمایه‌گذاری</div></div>
                        <div class="card-body pt-1" style="max-height: 400px;overflow: auto;">
                            @forelse($myAssignments as $assignment)
                                <div class="assignment-item d-flex justify-content-between align-items-center gap-3">
                                    <div class="min-w-0"><strong class="d-block text-truncate">{{ $assignment->project?->title ?: 'پرونده حذف‌شده' }}</strong><span class="dashboard-muted">{{ $assignment->investStep?->title ?: 'مرحله نامشخص' }} — {{ $assignment->role?->title_fa ?: $assignment->role?->title }}</span></div>
                                    @can('can-access', ['flow', 'view'])
                                        @if($assignment->project)<a class="btn btn-sm btn-icon btn-label-primary" href="{{ route('flow.show', $assignment->project) }}"><i class="mdi mdi-arrow-left"></i></a>@endif
                                    @endcan
                                </div>
                            @empty
                                <div class="empty-state"><i class="mdi mdi-clipboard-text-off-outline mdi-36px d-block mb-2"></i>وظیفه فعال مستقیمی به شما تخصیص داده نشده است.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            @if($operationalAlerts->isNotEmpty())
                <div class="alert-work p-3 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2"><i class="mdi mdi-bell-alert-outline text-warning mdi-24px"></i><strong>هشدارها و سررسیدهای کاری من</strong></div>
                    <div class="row g-2">
                        @foreach($operationalAlerts->take(6) as $alert)
                            <div class="col-lg-6"><div class="bg-white rounded-3 p-2 h-100"><strong>{{ $alert['title'] }}</strong><div class="dashboard-muted">{{ $alert['project_title'] }} — {{ $alert['days_remaining'] < 0 ? abs($alert['days_remaining']).' روز معوق' : $alert['days_remaining'].' روز تا سررسید' }}</div></div></div>
                        @endforeach
                    </div>
                </div>
            @endif

            @forelse($domainSections as $section)
                <div class="domain-panel mb-4" id="domain-{{ $section['key'] }}">
                    <div class="domain-panel-header">
                        <div class="d-flex align-items-center gap-3">
                            <span class="domain-icon tone-{{ $section['tone'] }}"><i class="mdi {{ $section['icon'] }} mdi-24px"></i></span>
                            <div><h5 class="mb-1">{{ $section['title'] }}</h5><div class="dashboard-muted">{{ $section['description'] }}</div></div>
                        </div>
                    </div>
                    <div class="p-3 p-lg-4">
                        <div class="row g-3 mb-4">
                            @foreach($section['cards'] as $card)
                                <div class="col-sm-6 col-xl-{{ $section['cards']->count() > 4 ? '4' : '3' }}">
                                    @if($card['url'])<a class="text-reset d-block h-100" href="{{ $card['url'] }}">@endif
                                        <div class="metric-card metric-tone-{{ $card['tone'] }}">
                                            <div class="d-flex justify-content-between gap-2"><div><div class="metric-value">{{ is_numeric($card['value']) ? \App\Support\Monetary::format($card['value']) : $card['value'] }}</div><strong class="d-block mt-1">{{ $card['label'] }}</strong><div class="metric-hint mt-1">{{ $card['hint'] }}</div></div><span class="metric-icon tone-{{ $card['tone'] }}"><i class="mdi {{ $card['icon'] }} mdi-24px"></i></span></div>
                                        </div>
                                    @if($card['url'])</a>@endif
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-4">
                            <div class="col-xl-7">
                                <h6 class="mb-1">{{ $section['chart']['title'] }}</h6><p class="dashboard-muted mb-3">واحد: {{ $section['chart']['unit'] }} · نمودار و کارت‌ها مستقل از جستجوی فهرست هستند.</p>
                                @if(collect($section['chart']['data'])->sum() > 0)
                                    <div class="chart-wrap" style="height:{{ $section['chart']['type'] === 'bar' && $section['key'] !== 'finance' ? max(300, count($section['chart']['labels']) * 30) : 300 }}px"><canvas id="domain-chart-{{ $section['key'] }}" role="img" aria-label="{{ $section['chart']['title'] }}؛ جزئیات در جدول زیر"></canvas></div>
                                    <details class="chart-data mt-3"><summary>مشاهده جدول داده‌های نمودار</summary><div class="table-responsive"><table class="table table-sm"><thead><tr><th>عنوان</th><th>{{ $section['chart']['unit'] }}</th></tr></thead><tbody>@foreach($section['chart']['labels'] as $index => $label)<tr><td>{{ $label }}</td><td>{{ \App\Support\Monetary::format($section['chart']['data'][$index]) }}</td></tr>@endforeach</tbody></table></div></details>
                                @else
                                    <div class="empty-state border rounded-3">برای ترسیم نمودار هنوز داده کافی ثبت نشده است.</div>
                                @endif
                            </div>
                            <div class="col-xl-5">
                                <div class="domain-items-toolbar">
                                    <div>
                                        <h6>{{ $section['itemsTitle'] }}</h6>
                                        <div class="dashboard-muted">{{ $section['itemsDescription'] }} ({{ number_format($section['items']->count()) }} مورد)</div>
                                    </div>
                                </div>
                                <form method="GET" action="{{ route('dashboard') }}#domain-{{ $section['key'] }}" class="dashboard-search mb-3">
                                    @foreach($domainSections as $other)
                                        @if($other['key'] !== $section['key'] && is_string(request('dash_search.'.$other['key'])))
                                            <input type="hidden" name="dash_search[{{ $other['key'] }}]" value="{{ request('dash_search.'.$other['key']) }}">
                                        @endif
                                    @endforeach
                                    <label class="form-label small" for="search-{{ $section['key'] }}">جستجو در تمام رکوردهای مجاز این حوزه</label>
                                    <div class="input-group">
                                        <input id="search-{{ $section['key'] }}" type="search" name="dash_search[{{ $section['key'] }}]" value="{{ is_string(request('dash_search.'.$section['key'])) ? request('dash_search.'.$section['key']) : '' }}" class="form-control js-domain-search" maxlength="120" placeholder="{{ $section['searchPlaceholder'] }}">
                                        <button class="btn btn-primary" type="submit">جستجو</button>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2 gap-2"><small class="dashboard-muted">همه واژه‌ها؛ اعداد فارسی/انگلیسی و ی/ک عربی پشتیبانی می‌شوند. حداکثر ۸ واژه.</small><button class="btn btn-sm btn-link js-clear-search" type="button">پاک‌کردن</button></div>
                                </form>
                                <div class="d-flex gap-2 align-items-center mb-2"><label class="small" for="kind-{{ $section['key'] }}">نوع رکورد</label><select id="kind-{{ $section['key'] }}" class="form-select form-select-sm js-kind-filter" data-domain="{{ $section['key'] }}" style="max-width:160px"><option value="">همه موارد نمایش‌داده‌شده</option>@foreach($section['items']->pluck('kind')->unique() as $kind)<option>{{ $kind }}</option>@endforeach</select><small class="dashboard-muted js-result-count" data-domain="{{ $section['key'] }}" aria-live="polite"></small></div>
                                <p class="dashboard-muted">حداکثر ۳۰ نتیجه اخیر؛ در بخش‌های مالی و اداری حداکثر ۱۵ نتیجه از هر نوع.</p>
                                <div class="domain-items-scroll" data-domain-list="{{ $section['key'] }}">
                                    @forelse($section['items'] as $item)
                                        @if($item['url'])<a data-kind="{{ $item['kind'] ?? 'مورد' }}" class="domain-item js-domain-item d-block text-reset" href="{{ $item['url'] }}">@else<div data-kind="{{ $item['kind'] ?? 'مورد' }}" class="domain-item js-domain-item">@endif
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="domain-item__icon tone-{{ $item['tone'] ?? $section['tone'] }}"><i class="mdi {{ $item['icon'] ?? $section['icon'] }}"></i></span>
                                                <span class="min-w-0 flex-grow-1">
                                                    <span class="d-flex align-items-center justify-content-between gap-2">
                                                        <strong class="d-block text-truncate">{{ $item['title'] }}</strong>
                                                        <span class="badge bg-label-{{ $item['tone'] ?? $section['tone'] }} domain-item__kind">{{ $item['kind'] ?? 'مورد' }}</span>
                                                    </span>
                                                    <span class="dashboard-muted d-block text-truncate">{{ $item['subtitle'] }}</span>
                                                    <span class="d-flex flex-wrap justify-content-between gap-1 mt-1">
                                                        <small class="text-muted">{{ $item['meta'] }}</small>
                                                        @if($item['created_at'] ?? null)<small class="text-muted domain-item__time">{{ $item['created_at']->locale('fa')->diffForHumans() }}</small>@endif
                                                    </span>
                                                </span>
                                                @if($item['url'])<i class="mdi mdi-chevron-left text-muted mt-2"></i>@endif
                                            </div>
                                        @if($item['url'])</a>@else</div>@endif
                                    @empty
                                        <div class="empty-state border rounded-3">رکوردی در محدوده دسترسی و جستجوی فعلی پیدا نشد.</div>
                                    @endforelse
                                    <div class="empty-state domain-items-empty border rounded-3" data-domain-empty="{{ $section['key'] }}" hidden>موردی مطابق عبارت جست‌وجو پیدا نشد.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card dashboard-card"><div class="empty-state"><i class="mdi mdi-shield-key-outline mdi-36px d-block mb-2"></i>برای نمایش گزارش حوزه کاری، دسترسی مشاهده یکی از صفحات تخصصی باید به نقش شما داده شود.</div></div>
            @endforelse
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
    <script src="{{ asset('assets/js/pages/dashboard-professional.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.BestsheetDashboard.init({{ \Illuminate\Support\Js::from($domainSections->mapWithKeys(fn($section) => [$section['key'] => $section['chart']])) }});
        });
    </script>
@endpush
