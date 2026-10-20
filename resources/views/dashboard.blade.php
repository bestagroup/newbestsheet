@extends('layouts.base')
@section('title', 'داشبورد مدیریتی | بست‌شیت')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/pages/dashboard-professional.css') }}?v=3.6.0">
@endsection
@section('content')
    @php
        $overdue = $operationalAlerts->where('days_remaining', '<', 0)->count();
        $initials = collect(preg_split('/\s+/u', trim($dashboardProfile['name'])))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
    @endphp
    <main class="dashboard-shell" dir="rtl" id="dashboard-top">
        <header class="dash-header">
            <div><p class="dash-eyebrow"><span class="dash-status-dot" aria-hidden="true"></span>بست‌شیت / فضای کاری شما</p><h1>نمای امروز کسب‌وکار</h1><p class="dash-muted">{{ $dashboardProfile['name'] }}، خوش آمدید. وضعیت کارها و حوزه‌های در دسترس شما در یک نگاه.</p></div>
            <div class="dash-header-actions"><span class="dash-updated">به‌روزرسانی داده‌ها<strong dir="ltr">{{ $generatedAt }}</strong></span><button type="button" class="dash-button js-dashboard-refresh"><i class="mdi mdi-refresh" aria-hidden="true"></i>تازه‌سازی</button></div>
        </header>
        <nav class="dash-shortcuts" aria-label="بخش‌های داشبورد">
            <a href="#dashboard-priorities">اولویت‌های امروز @if($overdue)<span class="dash-badge dash-badge-danger">{{ $overdue }} معوق</span>@endif</a><a href="#dashboard-domains">گزارش حوزه کاری</a><a href="#dashboard-work">وظایف و فعالیت‌ها</a><a href="#dashboard-personal">اطلاعات شخصی و جلسات من</a>
        </nav>
        <section class="dash-metrics" aria-label="خلاصه فعالیت شخصی">
            @foreach($personalCards as $card) @include('panel.dashboard.metric', ['card' => $card]) @endforeach
        </section>

        <div class="dash-priority-grid" id="dashboard-priorities">
            <section class="dash-panel" aria-labelledby="priorities-title">
                <header class="dash-panel-heading"><div><span class="dash-eyebrow">پیگیری و اقدام</span><h2 id="priorities-title">اولویت‌های امروز</h2></div><span class="dash-badge {{ $overdue ? 'dash-badge-danger' : '' }}">{{ $operationalAlerts->count() }} هشدار</span></header>
                <p class="dash-muted">سررسیدهای قابل مشاهده شما؛ موارد معوق در ابتدای فهرست قرار دارند.</p>
                <div class="dash-list" data-searchable-list="priorities">
                    @include('panel.dashboard.search', ['listKey' => 'priorities', 'searchLabel' => 'جستجو در هشدارهای نمایش‌داده‌شده'])
                    <div class="dash-list-rows">
                        @forelse($operationalAlerts->sortBy('days_remaining')->take(8) as $alert)
                            <article class="dash-priority-row" data-search-row>
                                <span class="dash-icon {{ $alert['days_remaining'] < 0 ? 'tone-danger' : 'tone-warning' }}"><i class="mdi {{ $alert['type'] === 'kpi' ? 'mdi-target' : 'mdi-file-document-outline' }}" aria-hidden="true"></i></span>
                                <div class="dash-row-content"><strong>{{ $alert['title'] }}</strong><span class="dash-muted">{{ $alert['project_title'] }}</span></div>
                                <div class="dash-row-end"><span class="dash-deadline {{ $alert['days_remaining'] < 0 ? 'is-overdue' : '' }}">{{ $alert['days_remaining'] < 0 ? abs($alert['days_remaining']).' روز معوق' : ($alert['days_remaining'] === 0 ? 'سررسید امروز' : $alert['days_remaining'].' روز تا سررسید') }}</span>@can('can-access', ['flow', 'view'])<a href="{{ route('flow.show', $alert['project_id']) }}" class="dash-text-link" aria-label="پیگیری {{ $alert['title'] }}">پیگیری ←</a>@endcan</div>
                            </article>
                        @empty
                            <div class="dash-empty"><i class="mdi mdi-check-circle-outline" aria-hidden="true"></i><strong>هشداری برای پیگیری نمایش داده نشده است.</strong><span>در محدوده دسترسی و بازه یادآوری شما موردی وجود ندارد.</span></div>
                        @endforelse
                    </div><p class="dash-search-empty" data-search-empty hidden>نتیجه‌ای پیدا نشد؛ عبارت جستجو را تغییر دهید.</p>
                </div>
                @if($operationalAlerts->count() > 8)<p class="dash-footnote">۸ مورد با نزدیک‌ترین سررسید از {{ $operationalAlerts->count() }} هشدار نمایش داده می‌شود.</p>@endif
            </section>
            <section class="dash-panel" aria-labelledby="meetings-title">
                <header class="dash-panel-heading"><div><span class="dash-eyebrow">برنامه پیش‌رو</span><h2 id="meetings-title">جلسات من</h2></div>@can('can-access', ['calendar', 'view'])<a class="dash-text-link" href="{{ route('calendar.index') }}">تقویم ←</a>@endcan</header>
                <p class="dash-muted">تا ۶ جلسه آینده که ایجاد کرده‌اید یا در آن دعوت شده‌اید.</p>
                <div class="dash-list" data-searchable-list="meetings">
                    @include('panel.dashboard.search', ['listKey' => 'meetings', 'searchLabel' => 'جستجو در جلسات نمایش‌داده‌شده'])
                    <div class="dash-list-rows">
                        @forelse($upcomingMeetings as $meeting)
                            <article class="dash-meeting-row" data-search-row><div class="dash-meeting-date"><strong>{{ mb_substr($meeting->start, 8, 2) }}</strong><span>{{ str_replace('-', '/', mb_substr($meeting->start, 0, 7)) }}</span></div><div class="dash-row-content"><strong>{{ $meeting->title }}</strong><span class="dash-muted">{{ $meeting->location ?: 'محل جلسه ثبت نشده' }}</span><span class="dash-meeting-time"><i class="mdi mdi-clock-outline" aria-hidden="true"></i> {{ $meeting->all_day ? 'تمام روز' : mb_substr($meeting->start, 11, 5) }}</span></div></article>
                        @empty
                            <div class="dash-empty"><i class="mdi mdi-calendar-blank-outline" aria-hidden="true"></i><strong>جلسه‌ای در برنامه پیش‌رو نیست.</strong><span>جلسات ثبت‌شده یا دعوت‌های شما اینجا نمایش داده می‌شوند.</span></div>
                        @endforelse
                    </div><p class="dash-search-empty" data-search-empty hidden>جلسه‌ای با این عبارت پیدا نشد.</p>
                </div>
            </section>
        </div>

        <section class="dash-workspace" id="dashboard-domains" aria-labelledby="domains-title">
            <div class="dash-section-heading"><div><p class="dash-eyebrow">دید مدیریتی</p><h2 id="domains-title">گزارش حوزه کاری</h2></div><span class="dash-muted">متناسب با نقش و دسترسی شما</span></div>
            @if($domainSections->isNotEmpty())
                <nav class="dash-domain-tabs" aria-label="انتخاب حوزه گزارش">
                    @foreach($domainSections as $section)<a id="tab-{{ $section['key'] }}" href="#domain-{{ $section['key'] }}" data-domain-tab="{{ $section['key'] }}"><i class="mdi {{ $section['icon'] }}" aria-hidden="true"></i>{{ str_replace('گزارش ', '', $section['title']) }}</a>@endforeach
                </nav>
                @foreach($domainSections as $section)
                    @include('panel.dashboard.domain', ['section' => $section])
                @endforeach
            @else
                <div class="dash-panel dash-empty"><i class="mdi mdi-shield-key-outline" aria-hidden="true"></i><strong>هنوز حوزه گزارشی در دسترس شما نیست.</strong><span>گزارش‌های تخصصی با توجه به مجوزهای نقش سازمانی نمایش داده می‌شوند.</span></div>
            @endif
        </section>

        <section id="dashboard-work" aria-labelledby="work-title">
            <div class="dash-section-heading"><div><p class="dash-eyebrow">میز کار</p><h2 id="work-title">وظایف و فعالیت‌ها</h2></div></div>
            <div class="dash-work-grid">
                <section class="dash-panel"><header class="dash-panel-heading"><h3>پرونده‌های تخصیص‌یافته به من</h3><span class="dash-badge">{{ $myAssignments->count() }} مورد اخیر</span></header>
                    <div class="dash-list" data-searchable-list="assignments">
                        @include('panel.dashboard.search', ['listKey' => 'assignments', 'searchLabel' => 'جستجو در تخصیص‌های نمایش‌داده‌شده'])
                        <div class="dash-list-rows">
                            @forelse($myAssignments as $assignment)
                                <article class="dash-task-row" data-search-row><span class="dash-task-index">{{ $loop->iteration }}</span><div class="dash-row-content"><strong>{{ $assignment->project?->title ?: 'پرونده در دسترس نیست' }}</strong><span class="dash-muted">{{ $assignment->investStep?->title ?: 'مرحله نامشخص' }} — {{ $assignment->role?->title_fa ?: $assignment->role?->title }}</span></div>@can('can-access', ['flow', 'view'])@if($assignment->project)<a class="dash-icon-link" href="{{ route('flow.show', $assignment->project) }}" aria-label="مشاهده پرونده {{ $assignment->project->title }}"><i class="mdi mdi-arrow-left" aria-hidden="true"></i></a>@endif @endcan</article>
                            @empty <div class="dash-empty"><i class="mdi mdi-clipboard-check-outline" aria-hidden="true"></i><strong>تخصیص فعالی برای شما ثبت نشده است.</strong></div> @endforelse
                        </div><p class="dash-search-empty" data-search-empty hidden>تخصیصی مطابق این عبارت پیدا نشد.</p>
                    </div>
                </section>
                <section class="dash-panel"><header class="dash-panel-heading"><h3>{{ $showsTeamActivity ? 'آخرین فعالیت‌های کارشناسان حوزه من' : 'آخرین فعالیت‌های انجام‌شده توسط من' }}</h3></header><p class="dash-muted">{{ $showsTeamActivity ? 'سوابق موفق کارشناسان مرتبط با پرونده‌های قابل مشاهده شما' : 'سوابق موفق ثبت‌شده در سامانه' }}</p>
                    <div class="dash-list" data-searchable-list="activities">
                        @include('panel.dashboard.search', ['listKey' => 'activities', 'searchLabel' => 'جستجوی فعالیت، شرح یا نام کارشناس'])
                        <div class="dash-list-rows dash-activity-list">
                            @forelse($recentActivities as $activity)
                                <article class="dash-activity-row" data-search-row><span class="dash-activity-mark" aria-hidden="true"></span><div class="dash-row-content"><strong>{{ $activity['title'] }}</strong><span class="dash-muted">{{ $activity['description'] }}</span><div class="dash-record-meta"><span>{{ $activity['actor_name'] }}@if($activity['actor_role']) — {{ $activity['actor_role'] }}@endif</span><span>{{ $activity['created_at']?->locale('fa')->diffForHumans() }}</span></div></div></article>
                            @empty <div class="dash-empty"><i class="mdi mdi-history" aria-hidden="true"></i><strong>فعالیتی برای نمایش ثبت نشده است.</strong></div> @endforelse
                        </div><p class="dash-search-empty" data-search-empty hidden>فعالیتی مطابق این عبارت پیدا نشد.</p>
                    </div>
                </section>
            </div>
        </section>
        <section id="dashboard-personal" class="dash-personal" aria-label="اطلاعات شخصی">
            <details class="dash-profile"><summary><span class="dash-avatar">{{ $initials ?: 'ک' }}</span><span><strong>{{ $dashboardProfile['name'] }}</strong><span class="dash-muted">{{ $dashboardProfile['job_title'] ?: 'حساب کاربری من' }} · تکمیل پروفایل {{ $dashboardProfile['completion'] }}٪</span></span><span class="dash-profile-action">اطلاعات شخصی <i class="mdi mdi-chevron-down" aria-hidden="true"></i></span></summary><div class="dash-profile-body"><div class="dash-profile-roles">@foreach($dashboardProfile['roles'] as $role)<span class="dash-badge">{{ $role }}</span>@endforeach</div><dl><div><dt>ایمیل</dt><dd dir="ltr">{{ $dashboardProfile['email'] ?: '—' }}</dd></div><div><dt>تلفن همراه</dt><dd dir="ltr">{{ $dashboardProfile['phone'] ?: '—' }}</dd></div><div><dt>کد ملی</dt><dd>{{ $dashboardProfile['national_id'] ?: '—' }}</dd></div><div><dt>آخرین ورود</dt><dd>{{ $dashboardProfile['last_login']?->locale('fa')->diffForHumans() ?: 'ثبت نشده' }}</dd></div></dl><a class="dash-button" href="{{ route('profile') }}">ویرایش پروفایل <i class="mdi mdi-account-edit-outline" aria-hidden="true"></i></a></div></details>
            @if($birthdayReminders->isNotEmpty())<aside class="dash-birthdays" aria-label="یادآوری تولد کارکنان">@foreach($birthdayReminders as $employee)<div><i class="mdi mdi-cake-variant" aria-hidden="true"></i><span><strong>۲ روز مانده تا تولد {{ $employee->full_name }}.</strong><span class="dash-muted">تولد این همکار گرامی را پیشاپیش تبریک می‌گوییم.</span></span></div>@endforeach</aside>@endif
        </section>
        <footer class="dash-footer"><span>بست‌شیت · مدیریت سرمایه‌گذاری و پورتفو</span><a href="#dashboard-top">بازگشت به بالای صفحه ↑</a></footer>
    </main>
@endsection
@push('scripts')
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
    <script src="{{ asset('assets/js/pages/dashboard-professional.js') }}?v=3.6.0"></script>
    <script>document.addEventListener('DOMContentLoaded', () => window.BestsheetDashboard.init({{ \Illuminate\Support\Js::from($domainSections->mapWithKeys(fn ($section) => [$section['key'] => $section['chart']])) }}));</script>
@endpush
