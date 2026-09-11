@extends('layouts.base')

@section('title', 'تقویم کاری')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/app-calendar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/calendar-workspace.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    <div
        id="calendarWorkspace"
        class="calendar-workspace"
        data-events-url="{{ route('calendar.events') }}"
        data-store-url="{{ route('calendar.store') }}"
        data-update-url="{{ route('calendar.update', ['id' => '__ID__']) }}"
        data-delete-url="{{ route('calendar.destroy', ['id' => '__ID__']) }}"
    >
        <header class="calendar-hero mb-4">
            <div class="calendar-hero__content">
                <div class="calendar-hero__icon" aria-hidden="true">
                    <i class="mdi mdi-calendar-check-outline"></i>
                </div>
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h1 class="calendar-hero__title mb-0">تقویم کاری من</h1>
                        <span class="calendar-system-badge">تقویم شمسی</span>
                    </div>
                    <p class="calendar-hero__subtitle mb-0">جلسه‌ها، تسک‌ها و برنامه‌های تخصیص‌یافته را یک‌جا ببینید و مدیریت کنید.</p>
                </div>
            </div>
            <div class="calendar-hero__today" aria-label="تاریخ امروز">
                <span class="calendar-hero__today-label">امروز</span>
                <strong id="jalaliToday">—</strong>
            </div>
        </header>

        <section class="calendar-stats mb-4" aria-label="خلاصه برنامه‌ها">
            <div class="calendar-stat">
                <span class="calendar-stat__icon calendar-stat__icon--primary"><i class="mdi mdi-calendar-today-outline"></i></span>
                <span><small>برنامه‌های امروز</small><strong id="todayEventsCount">۰</strong></span>
            </div>
            <div class="calendar-stat">
                <span class="calendar-stat__icon calendar-stat__icon--warning"><i class="mdi mdi-clock-fast"></i></span>
                <span><small>در پیش رو در این نما</small><strong id="upcomingEventsCount">۰</strong></span>
            </div>
            <div class="calendar-stat">
                <span class="calendar-stat__icon calendar-stat__icon--success"><i class="mdi mdi-account-multiple-check-outline"></i></span>
                <span><small>کل برنامه‌های این نما</small><strong id="visibleEventsCount">۰</strong></span>
            </div>
        </section>

        <div class="card app-calendar-wrapper calendar-shell">
            <div class="row g-0">
                <aside class="col app-calendar-sidebar" id="app-calendar-sidebar" aria-label="ابزارهای تقویم">
                    <div class="calendar-sidebar__top border-bottom">
                        <button
                            type="button"
                            class="btn btn-primary btn-toggle-sidebar w-100"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#addEventSidebar"
                            aria-controls="addEventSidebar"
                        >
                            <i class="mdi mdi-plus-circle-outline me-1"></i>
                            <span>برنامه جدید</span>
                        </button>
                    </div>

                    <div class="calendar-sidebar__body">
                        <div class="calendar-search mb-4">
                            <label class="form-label" for="calendarSearch">جست‌وجوی برنامه‌ها</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input id="calendarSearch" type="search" class="form-control" placeholder="عنوان، مکان یا توضیحات…" autocomplete="off">
                                <button id="clearCalendarSearch" type="button" class="btn btn-icon btn-label-secondary d-none" aria-label="پاک‌کردن جست‌وجو">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                        </div>

                        <section class="calendar-mini-section" aria-labelledby="miniCalendarTitle">
                            <div class="calendar-section-heading">
                                <h2 id="miniCalendarTitle">انتخاب سریع تاریخ</h2>
                                <span>شمسی</span>
                            </div>
                            <div class="inline-calendar" aria-label="تقویم کوچک شمسی"></div>
                        </section>

                        <hr class="my-4">

                        <section aria-labelledby="calendarFilterTitle">
                            <div class="calendar-section-heading mb-3">
                                <h2 id="calendarFilterTitle">دسته‌بندی‌ها</h2>
                                <button type="button" class="btn btn-sm btn-link p-0 select-all-text">انتخاب همه</button>
                            </div>

                            <div class="form-check calendar-filter-all mb-3">
                                <input class="form-check-input select-all" type="checkbox" id="selectAll" data-value="all" checked>
                                <label class="form-check-label" for="selectAll">نمایش همه برنامه‌ها</label>
                            </div>

                            <div class="app-calendar-events-filter">
                                @foreach([
                                    ['meeting', 'جلسه', 'primary'],
                                    ['session', 'نشست', 'danger'],
                                    ['task', 'تسک', 'warning'],
                                    ['event', 'رویداد و برنامه', 'info'],
                                    ['person', 'شخصی', 'success'],
                                    ['other', 'سایر', 'secondary'],
                                ] as [$value, $label, $color])
                                    <div class="form-check calendar-filter-item">
                                        <input class="form-check-input input-filter" type="checkbox" id="select-{{ $value }}" data-value="{{ $value }}" checked>
                                        <label class="form-check-label" for="select-{{ $value }}">
                                            <span class="calendar-filter-dot bg-{{ $color }}"></span>
                                            <span>{{ $label }}</span>
                                            <span class="calendar-filter-count" data-count-for="{{ $value }}">۰</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <hr class="my-4">

                        <section aria-labelledby="agendaTitle">
                            <div class="calendar-section-heading mb-3">
                                <h2 id="agendaTitle">نزدیک‌ترین برنامه‌ها</h2>
                                <span id="agendaRangeLabel">در این نما</span>
                            </div>
                            <div id="calendarAgenda" class="calendar-agenda" aria-live="polite">
                                <div class="calendar-agenda__empty">
                                    <i class="mdi mdi-calendar-blank-outline"></i>
                                    <span>برنامه‌ای در این بازه نیست.</span>
                                </div>
                            </div>
                        </section>
                    </div>
                </aside>

                <main class="col app-calendar-content">
                    <div class="calendar-main-toolbar">
                        <div>
                            <span class="calendar-main-toolbar__eyebrow">نمای برنامه‌ریزی</span>
                            <h2 id="calendarViewTitle" class="calendar-main-toolbar__title">—</h2>
                        </div>
                        <div id="calendarLoadState" class="calendar-load-state" aria-live="polite">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <span>در حال دریافت برنامه‌ها…</span>
                        </div>
                    </div>

                    <div class="card shadow-none border-0">
                        <div class="card-body calendar-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                    <div class="app-overlay"></div>

                    <div class="offcanvas offcanvas-end event-sidebar" tabindex="-1" id="addEventSidebar" aria-labelledby="addEventSidebarLabel">
                        <div class="offcanvas-header calendar-form-header border-bottom">
                            <div>
                                <span class="calendar-form-header__eyebrow">تقویم کاری</span>
                                <h2 class="offcanvas-title" id="addEventSidebarLabel">افزودن برنامه</h2>
                            </div>
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
                        </div>
                        <div class="offcanvas-body">
                            <div id="eventReadonlyNotice" class="alert alert-label-info d-none mb-4" role="status">
                                <i class="mdi mdi-eye-outline me-1"></i>
                                این برنامه به شما تخصیص داده شده است و فقط ایجادکننده می‌تواند آن را ویرایش کند.
                            </div>

                            <div id="eventOwnerPanel" class="calendar-event-owner d-none mb-4">
                                <span class="calendar-event-owner__icon"><i class="mdi mdi-account-circle-outline"></i></span>
                                <span><small>ایجادکننده</small><strong id="eventCreatorName">—</strong></span>
                                <span id="eventSyncStatus" class="badge bg-label-secondary">ذخیره محلی</span>
                            </div>

                            <a id="eventActionLink" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-label-primary d-none mb-4">
                                <i class="mdi mdi-open-in-new me-1"></i>
                                باز کردن لینک برنامه
                            </a>

                            <form class="event-form" id="eventForm" onsubmit="return false" novalidate>
                                <div class="mb-4">
                                    <label class="form-label" for="eventTitle">عنوان برنامه <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="eventTitle" name="eventTitle" placeholder="مثلاً جلسه بررسی پیشرفت پروژه" maxlength="255" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="eventLabel">نوع برنامه</label>
                                    <select class="select2 select-event-label form-select" id="eventLabel" name="eventLabel" data-select2-manual>
                                        <option data-label="primary" value="meeting" selected>جلسه</option>
                                        <option data-label="danger" value="session">نشست</option>
                                        <option data-label="warning" value="task">تسک</option>
                                        <option data-label="info" value="event">رویداد و برنامه</option>
                                        <option data-label="success" value="person">شخصی</option>
                                        <option data-label="secondary" value="other">سایر</option>
                                    </select>
                                </div>

                                <div class="calendar-all-day-row mb-4">
                                    <div>
                                        <strong>برنامه تمام‌روز</strong>
                                        <small>در صورت فعال‌بودن، ساعت نمایش داده نمی‌شود.</small>
                                    </div>
                                    <label class="switch mb-0">
                                        <input id="eventAllDay" name="allDay" type="checkbox" class="switch-input allDay-switch">
                                        <span class="switch-toggle-slider"><span class="switch-on"></span><span class="switch-off"></span></span>
                                        <span class="visually-hidden">برنامه تمام‌روز</span>
                                    </label>
                                </div>

                                <div class="calendar-section-heading mb-3">
                                    <h3 class="mb-0">بازه زمانی برنامه</h3>
                                    <span>تاریخ‌ها شمسی هستند</span>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <div class="calendar-datetime-card">
                                            <div class="calendar-datetime-card__title">
                                                <i class="mdi mdi-calendar-start-outline"></i>
                                                <span>شروع برنامه</span>
                                            </div>
                                            <div>
                                                <label class="form-label" for="eventStartDate">از تاریخ <span class="text-danger">*</span></label>
                                                <div class="input-group calendar-date-group">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar-blank-outline"></i></span>
                                                    <input type="text" class="form-control date-input--date" id="eventStartDate" name="eventStartDate" data-datepicker-ignore placeholder="انتخاب تاریخ شروع" autocomplete="off" required>
                                                </div>
                                            </div>
                                            <div class="calendar-time-field">
                                                <label class="form-label" for="eventStartTime">ساعت شروع <span class="text-danger event-time-required">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="mdi mdi-clock-outline"></i></span>
                                                    <input type="time" class="form-control" id="eventStartTime" name="eventStartTime" step="900" dir="ltr" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="calendar-datetime-card">
                                            <div class="calendar-datetime-card__title">
                                                <i class="mdi mdi-calendar-end-outline"></i>
                                                <span>پایان برنامه</span>
                                            </div>
                                            <div>
                                                <label class="form-label" for="eventEndDate">تا تاریخ <span class="text-danger">*</span></label>
                                                <div class="input-group calendar-date-group">
                                                    <span class="input-group-text"><i class="mdi mdi-calendar-blank-outline"></i></span>
                                                    <input type="text" class="form-control date-input--date" id="eventEndDate" name="eventEndDate" data-datepicker-ignore placeholder="انتخاب تاریخ پایان" autocomplete="off" required>
                                                </div>
                                            </div>
                                            <div class="calendar-time-field">
                                                <label class="form-label" for="eventEndTime">ساعت پایان <span class="text-danger event-time-required">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="mdi mdi-clock-check-outline"></i></span>
                                                    <input type="time" class="form-control" id="eventEndTime" name="eventEndTime" step="900" dir="ltr" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="calendar-duration-shortcuts mb-4" aria-label="تنظیم سریع مدت برنامه">
                                    <span>مدت سریع:</span>
                                    <button type="button" class="btn btn-sm btn-label-secondary" data-duration="30">۳۰ دقیقه</button>
                                    <button type="button" class="btn btn-sm btn-label-secondary" data-duration="60">۱ ساعت</button>
                                    <button type="button" class="btn btn-sm btn-label-secondary" data-duration="90">۹۰ دقیقه</button>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="eventGuests">کارکنان مرتبط</label>
                                    <select class="select2 select-event-guests form-select" id="eventGuests" name="eventGuests[]" data-select2-manual multiple>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" data-avatar="{{ $user->gender == 2 ? asset('assets/img/avatars/2.png') : asset('assets/img/avatars/1.png') }}">
                                                {{ $user->name }}{{ $user->email ? ' — '.$user->email : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">افراد انتخاب‌شده، اعلان برنامه را دریافت می‌کنند.</div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="eventLocation">مکان یا بستر برگزاری</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="mdi mdi-map-marker-outline"></i></span>
                                        <input type="text" class="form-control" id="eventLocation" name="eventLocation" placeholder="مثلاً اتاق جلسات یا آنلاین" maxlength="255">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="eventURL">لینک جلسه یا برنامه</label>
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i class="mdi mdi-link-variant"></i></span>
                                        <input type="url" class="form-control" id="eventURL" name="eventURL" placeholder="https://…" maxlength="2048" dir="ltr">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label" for="eventDescription">توضیحات</label>
                                    <textarea class="form-control" name="eventDescription" id="eventDescription" rows="4" maxlength="5000" placeholder="دستور جلسه، خروجی مورد انتظار یا نکات تکمیلی…"></textarea>
                                </div>

                                <div class="calendar-form-actions">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-add-event">
                                            <span class="button-label"><i class="mdi mdi-check me-1"></i>ثبت برنامه</span>
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-update-event d-none">
                                            <span class="button-label"><i class="mdi mdi-content-save-outline me-1"></i>ذخیره تغییرات</span>
                                        </button>
                                        <button type="reset" class="btn btn-label-secondary btn-cancel" data-bs-dismiss="offcanvas">انصراف</button>
                                    </div>
                                    <button type="button" class="btn btn-icon btn-label-danger btn-delete-event d-none" aria-label="حذف برنامه" title="حذف برنامه">
                                        <i class="mdi mdi-delete-outline"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/calendar-workspace.js') }}"></script>
@endpush
