/**
 * App Calendar
 * Modified to use AJAX (GET/POST/PUT/DELETE) with Laravel routes:
 *  - GET  /panel/calendar/events
 *  - POST /panel/calendar/store
 *  - POST /panel/calendar/update/{id}  (with _method=PUT)
 *  - POST /panel/calendar/delete/{id}  (with _method=DELETE)
 *
 * Keeps original DOM ids/classes (eventTitle, eventStartDate, btn-add-event, ...)
 */

'use strict';

let direction = 'ltr';
if (isRtl) {
    direction = 'rtl';
}

document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const calendarEl = document.getElementById('calendar'),
            appCalendarSidebar = document.querySelector('.app-calendar-sidebar'),
            addEventSidebar = document.getElementById('addEventSidebar'),
            appOverlay = document.querySelector('.app-overlay'),
            calendarsColor = {
                Business: 'primary',
                Holiday: 'success',
                Personal: 'danger',
                Family: 'warning',
                ETC: 'info'
            },
            offcanvasTitle = document.querySelector('.offcanvas-title'),
            btnToggleSidebar = document.querySelector('.btn-toggle-sidebar'),
            btnAddEvent = document.querySelector('.btn-add-event'),
            btnUpdateEvent = document.querySelector('.btn-update-event'),
            btnDeleteEvent = document.querySelector('.btn-delete-event'),
            btnCancel = document.querySelector('.btn-cancel'),
            eventTitle = document.querySelector('#eventTitle'),
            eventStartDate = document.querySelector('#eventStartDate'),
            eventEndDate = document.querySelector('#eventEndDate'),
            eventUrl = document.querySelector('#eventURL'),
            eventLabel = $('#eventLabel'),
            // select2
            eventGuests = $('#eventGuests'), // select2
            eventLocation = document.querySelector('#eventLocation'),
            eventDescription = document.querySelector('#eventDescription'),
            allDaySwitch = document.querySelector('.allDay-switch'),
            selectAll = document.querySelector('.select-all'),
            filterInput = [].slice.call(document.querySelectorAll('.input-filter')),
            inlineCalendar = document.querySelector('.inline-calendar');

        let pendingDate = null;
        let eventToUpdate,
            currentEvents = [], // we'll populate from server
            isFormValid = false,
            inlineCalInstance;

        // CSRF setup for jQuery ajax (requires meta tag in head)
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfMeta.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        } else {
            console.warn('CSRF meta tag not found. Add <meta name="csrf-token" content="{{ csrf_token() }}"> to your layout head.');
        }

        // Init event Offcanvas
        const bsAddEventSidebar = new bootstrap.Offcanvas(addEventSidebar);

        addEventSidebar.addEventListener('show.bs.offcanvas', function () {
            ensurePickers();
        });

        addEventSidebar.addEventListener('shown.bs.offcanvas', function () {
            applyPendingDate();
        });


        // ---------- select2 inits (kept from original) ----------
        if (eventLabel.length) {
            function renderBadges(option) {
                if (!option.id) return option.text;
                var $badge = "<span class='badge badge-dot bg-" + $(option.element).data('label') + " me-2'> " + '</span>' + option.text;
                return $badge;
            }

            eventLabel.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'انتخاب',
                dropdownParent: eventLabel.parent(),
                templateResult: renderBadges,
                templateSelection: renderBadges,
                minimumResultsForSearch: -1,
                escapeMarkup: function (es) {
                    return es;
                }
            });
        }

        if (eventGuests.length) {
            function renderGuestAvatar(option) {
                if (!option.id) return option.text;
                var $avatar =
                    "<div class='d-flex flex-wrap align-items-center'>" +
                    "<div class='avatar avatar-xs me-2'>" +
                    "<img src='" + $(option.element).data('avatar') + "' alt='avatar' class='rounded-circle' />" + '</div>' + option.text + '</div>';
                return $avatar;
            }

            eventGuests.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'انتخاب',
                dropdownParent: eventGuests.parent(),
                closeOnSelect: false,
                templateResult: renderGuestAvatar,
                templateSelection: renderGuestAvatar,
                escapeMarkup: function (es) {
                    return es;
                }
            });
        }

        // امروز در ابتدای روز (بدون ساعت) برای مقایسه دقیق
        function startOfToday() {
            const now = new JDate();
            return new Date(now.getFullYear(), now.getMonth(), now.getDate());
        }

        const MIN_DATE = startOfToday();

        function isBeforeToday(dateObj) {
            if (!dateObj) return false;
            const only = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate());
            return only < MIN_DATE; // فقط قبل از امروز ممنوع؛ امروز و بعدش آزاد
        }


        // ---------- flatpickr inits (kept from original) ----------
        let start, end;

        function initStartEndPickers() {
            const isAllDay = allDaySwitch && allDaySwitch.checked;

            const common = {
                altInput: false,
                locale: 'fa',
                disableMobile: true,
                appendTo: document.body,
                static: false,
                // کمک به بسته‌شدن بهتر:
                closeOnSelect: true,
                clickOpens: true,
                allowInput: false,
                onReady: function (selectedDates, dateStr, instance) {
                    if (instance.isMobile) instance.mobileInput.setAttribute('step', null);
                }
            };

            if (eventStartDate) {
                start = eventStartDate.flatpickr(Object.assign({}, common, {
                    enableTime: !isAllDay,
                    altFormat: isAllDay ? 'Y/m/d' : 'Y/m/d - H:i',
                    positionElement: eventStartDate,
                    onChange: function (sel) {
                        if (end && sel && sel[0]) end.set('minDate', sel[0]);
                    }
                }));
            }

            if (eventEndDate) {
                end = eventEndDate.flatpickr(Object.assign({}, common, {
                    enableTime: !isAllDay,
                    altFormat: isAllDay ? 'Y/m/d' : 'Y/m/d - H:i',
                    positionElement: eventEndDate,
                    onChange: function (sel) {
                        if (start && sel && sel[0]) start.set('maxDate', sel[0]);
                    }
                }));
            }
        }

// نخستین بار اینیت
        initStartEndPickers();

        document.addEventListener('mousedown', function (e) {
            const cal = document.querySelector('.flatpickr-calendar.open');
            if (!cal) return;
            const s = eventStartDate, t = eventEndDate;
            if (!cal.contains(e.target) && !s.contains(e.target) && !t.contains(e.target)) {
                try {
                    if (start) start.close();
                    if (end) end.close();
                } catch (_) {
                }
            }
        }, true);


// تغییر حالت "تمام‌روز" → destroy & reinit
        if (allDaySwitch) {
            allDaySwitch.addEventListener('change', function () {
                try {
                    if (start) start.destroy();
                } catch (e) {
                }
                try {
                    if (end) end.destroy();
                } catch (e) {
                }
                initStartEndPickers();
            });
        }

        // ---------- inline calendar init ----------
        if (inlineCalendar) {
            // روی DIV بهتره ورژن تابعی را صدا بزنیم
            inlineCalInstance = flatpickr(inlineCalendar, {
                inline: true,
                monthSelectorType: 'static',
                locale: 'fa',
                disableMobile: true
            });
        }

        // ---------- helper utilities ----------

        function pad(n) {
            return n < 10 ? '0' + n : n;
        }

        // format JS Date -> "YYYY-MM-DD" or "YYYY-MM-DDTHH:mm:ss" (no timezone)
        function formatDateForServer(dateObj, allDay = false) {
            if (!dateObj) return null;
            const pad = n => (n < 10 ? '0' + n : n);
            const y = dateObj.getFullYear();
            const m = pad(dateObj.getMonth() + 1);
            const d = pad(dateObj.getDate());
            if (allDay) return `${y}-${m}-${d}`; // DATE
            const hh = pad(dateObj.getHours());
            const mm = pad(dateObj.getMinutes());
            const ss = pad(dateObj.getSeconds());
            return `${y}-${m}-${d} ${hh}:${mm}:${ss}`; // DATETIME با فاصله
        }

        // ⬇️ بعد از formatDateForServer و قبل از normalizeServerEvent
        function toJsDate(x) {
            if (!x) return null;
            // اگر flatpickr به ما Date داده
            if (x instanceof Date && !isNaN(x)) return x;
            // اگر JDate باشد و تاریخ داخلی داشته باشد
            if (typeof x === 'object' && x['_date'] instanceof Date && !isNaN(x['_date'])) return x['_date'];
            // اگر استرینگ میلادی باشد
            if (typeof x === 'string') {
                const d = new Date(x.replace('T', ' '));
                if (!isNaN(d)) return d;
            }
            return null;
        }

        // مطمئن شو اینستنس‌های flatpickr موجودند (اگر destroy شده‌اند، دوباره init کن)
        function ensurePickers() {
            if (!start || !end) {
                initStartEndPickers();
            }
        }

        // ست تاریخ روی flatpickr با درنظر گرفتن allDay
        function safeSet(fp, value, isAllDay) {
            if (!fp || !value) return;
            const jsDate = toJsDate(value);
            if (!jsDate) return;
            fp.setDate(jsDate, true, isAllDay ? 'Y-m-d' : 'Y-m-d H:i');
        }

        function applyPendingDate() {
            if (!pendingDate) return;
            ensurePickers();
            const isAllDay = allDaySwitch && allDaySwitch.checked;
            try { if (start) start.clear(); if (end) end.clear(); } catch(_) {}

            // ست هر دو فیلد روی تاریخ کلیک‌شده
            safeSet(start, pendingDate, isAllDay);
            safeSet(end,   pendingDate, isAllDay);

            // برای اینکه FormValidation متوجه مقدار شدن فیلدها بشه:
            if (eventStartDate) eventStartDate.dispatchEvent(new Event('change', { bubbles: true }));
            if (eventEndDate)   eventEndDate.dispatchEvent(new Event('change',   { bubbles: true }));

            pendingDate = null;
        }



        // REPLACE: getDateFromInput
        function getDateFromInput(fpInstance, inputValue) {
            try {
                // 1) اگر flatpickr اینستنس داریم و selectedDates پر است، همان را برگردان
                if (fpInstance && fpInstance.selectedDates && fpInstance.selectedDates.length) {
                    return fpInstance.selectedDates[0];
                }

                // 2) اگر اینستنس داریم ولی selectedDates خالی است، از parser خودش استفاده کن
                if (fpInstance) {
                    const raw = fpInstance.input ? fpInstance.input.value : (inputValue || '');
                    if (raw) {
                        // فرمت پایه flatpickr وقتی altInput روشن است، value خود input به فرمت ISO یکنواخت است
                        const fmt = fpInstance.config.enableTime ? 'Y-m-d H:i' : 'Y-m-d';
                        const parsed = fpInstance.parseDate(raw, fmt);
                        if (parsed instanceof Date && !isNaN(parsed)) return parsed;
                    }
                }

                // 3) اگر رشته میلادی استاندارد بود، مستقیم Date بزن
                if (typeof inputValue === 'string' && /^\d{4}-\d{2}-\d{2}/.test(inputValue)) {
                    const d = new Date(inputValue.replace('T', ' ')); // سازگاری
                    if (!isNaN(d)) return d;
                }

                // 4) فقط اگر نشانه‌های شمسی دارد (اسلش یا ارقام فارسی)، JDate را امتحان کن
                const looksJalali = typeof inputValue === 'string' && (inputValue.includes('/') || /[۰-۹]/.test(inputValue));
                if (looksJalali) {
                    const jd = new JDate(inputValue);
                    const d = jd && jd['_date'];
                    if (d instanceof Date && !isNaN(d)) return d;
                }

                return null;
            } catch (e) {
                return null;
            }
        }


        function normalizeServerEvent(e) {
            // Accept different server shapes and normalize to FullCalendar shape expected by this script
            let guests = [];
            if (e.guests) {
                try {
                    guests = (typeof e.guests === 'string') ? JSON.parse(e.guests) : e.guests;
                } catch (err) {
                    guests = Array.isArray(e.guests) ? e.guests : [];
                }
            } else if (e.extendedProps && e.extendedProps.guests) {
                guests = e.extendedProps.guests;
            }

            const label = (e.label || e.calendar || (e.extendedProps && e.extendedProps.calendar) || '').toString();

            return {
                id: e.id,
                title: e.title || e.name || '',
                start: e.start || e.start_date || e.start_date_time || null,
                end: e.end || e.end_date || e.end_date_time || null,
                allDay: (e.allDay !== undefined) ? !!e.allDay : !!e.all_day,
                url: e.url || null,
                extendedProps: {
                    description: e.description || (e.extendedProps && e.extendedProps.description) || '',
                    location: e.location || (e.extendedProps && e.extendedProps.location) || '',
                    guests: guests || [],
                    calendar: label
                }
            };
        }

        // ---------- Event click (kept mostly the same) ----------
        function eventClick(info) {
            eventToUpdate = info.event;

            // جلوگیری از باز شدن صفحه جدید در هر شرایطی
            info.jsEvent.preventDefault();

            // نمایش سایدبار (offcanvas)
            bsAddEventSidebar.show();

            // دکمه‌ها
            btnAddEvent.classList.add('d-none');
            btnUpdateEvent.classList.remove('d-none');
            btnDeleteEvent.classList.remove('d-none');

            if (offcanvasTitle) offcanvasTitle.innerHTML = 'به‌روزرسانی رویداد';

            // پر کردن فرم با اطلاعات event
            eventTitle.value = eventToUpdate.title;

            // هماهنگ کردن حالت تمام‌روز + ری‌اینیت بر اساس آن
            allDaySwitch.checked = !!eventToUpdate.allDay;
            // تریگر تغییر تا destroy/reinit داخلی‌ات اجرا شود
            allDaySwitch.dispatchEvent(new Event('change'));

            // مطمئن شو پیکرها حاضرند
            ensurePickers();

            // تبدیل و ست
            const s = toJsDate(eventToUpdate.start);
            const e = toJsDate(eventToUpdate.end || eventToUpdate.start);
            try {
                if (start) start.clear();
                if (end) end.clear();
            } catch (e) {
            }
            safeSet(start, s, !!eventToUpdate.allDay);
            safeSet(end, e, !!eventToUpdate.allDay);


            eventLabel.val(eventToUpdate.extendedProps.calendar).trigger('change');

            if (eventToUpdate.extendedProps.location !== undefined) {
                eventLocation.value = eventToUpdate.extendedProps.location;
            }

            if (eventToUpdate.extendedProps.guests !== undefined) {
                eventGuests.val(eventToUpdate.extendedProps.guests).trigger('change');
            }

            if (eventToUpdate.extendedProps.description !== undefined) {
                eventDescription.value = eventToUpdate.extendedProps.description;
            }

            eventUrl.value = eventToUpdate.url || '';
        }


        // Modify sidebar toggler (kept)
        function modifyToggler() {
            const fcSidebarToggleButton = document.querySelector('.fc-sidebarToggle-button');
            if (!fcSidebarToggleButton) return;
            fcSidebarToggleButton.classList.remove('fc-button-primary');
            fcSidebarToggleButton.classList.add('d-lg-none', 'd-inline-block', 'ps-0');
            while (fcSidebarToggleButton.firstChild) fcSidebarToggleButton.firstChild.remove();
            fcSidebarToggleButton.setAttribute('data-bs-toggle', 'sidebar');
            fcSidebarToggleButton.setAttribute('data-overlay', '');
            fcSidebarToggleButton.setAttribute('data-target', '#app-calendar-sidebar');
            fcSidebarToggleButton.insertAdjacentHTML('beforeend', '<i class="bx bx-menu bx-sm"></i>');
        }

        // Filter events by calendar (kept)
        function selectedCalendars() {
            let selected = [], filterInputChecked = [].slice.call(document.querySelectorAll('.input-filter:checked'));
            filterInputChecked.forEach(item => selected.push(item.getAttribute('data-value')));
            return selected;
        }

        // ---------- fetchEvents: call server ----------
        function fetchEvents(info, successCallback, failureCallback) {
            // send start/end range and selected calendars (optional)
            const params = {
                start: info.startStr || null,
                end: info.endStr || null,
                calendars: selectedCalendars().join(',')
            };

            $.ajax({
                url: '/panel/calendar/events',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function (res) {
                    // JSON خام از سرور:
                    console.group('GET /panel/calendar/events → RAW');
                    console.log('query:', params);
                    console.log('raw:', res);
                    try {
                        console.table(Array.isArray(res) ? res : []);
                    } catch (_) {
                    }
                    console.groupEnd();

                    // نگه‌داشتن آخرین پاسخ برای بررسی بعدی:
                    window._lastEventsRaw = res;

                    if (!Array.isArray(res)) {
                        successCallback([]);
                        return;
                    }

                    // نرمال‌سازی خودت:
                    currentEvents = res.map(normalizeServerEvent);

                    // خروجی نهایی که به FullCalendar می‌دهی:
                    console.group('GET /panel/calendar/events → NORMALIZED');
                    console.table(currentEvents.map(e => ({
                        id: e.id, title: e.title, start: e.start, end: e.end,
                        allDay: e.allDay, calendar: e.extendedProps?.calendar
                    })));
                    console.groupEnd();

                    window._lastEventsNormalized = currentEvents;

                    const calendars = selectedCalendars();
                    const filtered = currentEvents.filter(function (ev) {
                        if (!calendars || calendars.length === 0) return true;
                        const evCal = (ev.extendedProps?.calendar || 'etc').toString().toLowerCase();
                        return calendars.includes(evCal);
                    });

                    successCallback(filtered);
                },

                error: function (err) {
                    console.error('خطا در دریافت رویدادها', err);
                    if (typeof failureCallback === 'function') failureCallback(err);
                }
            });
        }

        // ---------- Init FullCalendar (kept mostly same) ----------
        let {dayGrid, interaction, timeGrid, list} = calendarPlugins;
        let calendar = new Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            validRange: function (nowDate) {
                return {start: nowDate};
            },
            events: fetchEvents,
            plugins: [interaction, dayGrid, timeGrid, list],
            editable: true,
            dragScroll: true,
            dayMaxEvents: 2,
            eventResizableFromStart: true,
            customButtons: {
                sidebarToggle: {text: 'نوار کناری'}
            },
            headerToolbar: {
                start: '',
                center: 'prev title next',
                end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            direction: direction,
            initialDate: new Date(),
            navLinks: true,
            eventClassNames: function ({event: calendarEvent}) {
                const colorName = calendarsColor[calendarEvent._def.extendedProps.calendar];
                return ['fc-event-' + colorName];
            },
            eventContent: function (arg) {
                // اگر ساعت داشته باشه (allDay نیست) → نمایش ساعت + خط تیره + عنوان
                let timeText = arg.timeText ? `<span class="me-1">${arg.timeText}</span>` : '';
                let separator = arg.timeText ? `<span class="mx-1">-</span>` : '';
                let title = `<span>${arg.event.title}</span>`;

                return {html: timeText + separator + title};
            },
            dateClick: function (info) {
                resetValues();

                if (offcanvasTitle) offcanvasTitle.innerHTML = 'افزودن رویداد';
                btnAddEvent.classList.remove('d-none');
                btnUpdateEvent.classList.add('d-none');
                btnDeleteEvent.classList.add('d-none');

                // تاریخ کلیک‌شده را در صف بگذار
                pendingDate = new Date(info.date);

                // اگر سایدبار باز است، همین الآن ست کن؛ وگرنه بعد از shown ست می‌شود
                if (addEventSidebar.classList.contains('show')) {
                    ensurePickers();
                    const isAllDay = allDaySwitch && allDaySwitch.checked;
                    try { if (start) start.clear(); if (end) end.clear(); } catch (_) {}
                    safeSet(start, pendingDate, isAllDay);
                    safeSet(end,   pendingDate, isAllDay);

                    // اطلاع به ولیدیشن
                    if (eventStartDate) eventStartDate.dispatchEvent(new Event('change', { bubbles: true }));
                    if (eventEndDate)   eventEndDate.dispatchEvent(new Event('change',   { bubbles: true }));

                    pendingDate = null;
                } else {
                    bsAddEventSidebar.show();
                }
            },

            eventAllow: function (dropInfo, draggedEvent) {
                // dropInfo.start = تاریخ جدید
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return dropInfo.start >= today; // فقط امروز و بعد از اون مجازه
            },
            eventClick: function (info) {
                eventClick(info);
            },
            datesSet: function () {
                modifyToggler();
            },
            viewDidMount: function () {
                modifyToggler();
            },
            locale: 'fa',
            firstDay: 6,
            buttonText: {today: 'امروز', month: 'ماه', week: 'هفته', day: 'روز', list: 'لیست'},
            weekText: 'هفته',
            allDayText: 'تمام روز',
            moreLinkText: function (n) {
                return '+' + n + ' مورد دیگر';
            },
            noEventsText: 'رویدادی برای نمایش وجود ندارد'
        });

        calendar.render();
        modifyToggler();

        // ---------- Form validation (kept) ----------
        const eventForm = document.getElementById('eventForm');
        const fv = FormValidation.formValidation(eventForm, {
            fields: {
                eventTitle: {validators: {notEmpty: {message: 'لطفا عنوان رویداد را وارد کنید '}}},
                eventStartDate: {validators: {notEmpty: {message: 'لطفا تاریخ شروع را وارد کنید '}}},
                eventEndDate: {validators: {notEmpty: {message: 'لطفا تاریخ پایان را وارد کنید '}}}
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: '',
                    rowSelector: function (field, ele) {
                        return '.mb-3';
                    }
                }),
                submitButton: new FormValidation.plugins.SubmitButton(),
                autoFocus: new FormValidation.plugins.AutoFocus()
            }
        }).on('core.form.valid', function () {
            isFormValid = true;
        });

        if (btnToggleSidebar) {
            btnToggleSidebar.addEventListener('click', e => {
                btnCancel.classList.remove('d-none');
            });
        }

        // ---------- Local helpers (kept) ----------
        function addEventLocal(eventData) {
            currentEvents.push(eventData);
            calendar.refetchEvents();
        }

        function updateEventLocal(eventData) {
            eventData.id = parseInt(eventData.id);
            const idx = currentEvents.findIndex(el => String(el.id) === String(eventData.id));
            if (idx !== -1) currentEvents[idx] = eventData;
            calendar.refetchEvents();
        }

        function removeEventLocal(eventId) {
            currentEvents = currentEvents.filter(function (event) {
                return String(event.id) !== String(eventId);
            });
            calendar.refetchEvents();
        }

        // ---------- Add new event (AJAX -> store) ----------
        btnAddEvent.addEventListener('click', async e => {
            const status = await fv.validate(); //


            if (status !== 'Valid') return;

            const startDateObj = getDateFromInput(start, eventStartDate.value);
            const endDateObj = getDateFromInput(end, eventEndDate.value);


            if (!startDateObj || isBeforeToday(startDateObj)) {
                alert('امکان ثبت رویداد با تاریخ شروع گذشته از امروز وجود ندارد.');
                return;
            }
            if (endDateObj && endDateObj < startDateObj) {
                alert('تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.');
                return;
            }

            const payload = {
                eventTitle: eventTitle.value,
                eventLabel: eventLabel.val() || null,
                eventStartDate: formatDateForServer(startDateObj, allDaySwitch.checked),
                eventEndDate: formatDateForServer(endDateObj, allDaySwitch.checked),
                allDay: allDaySwitch.checked ? 1 : 0,
                eventURL: eventUrl.value || null,
                eventLocation: eventLocation.value || null,
                eventDescription: eventDescription.value || null,
                'eventGuests[]': eventGuests.val() || []
            };
            // این دو خط کمک می‌کنند واضح ببینی چی میره
            console.group('POST /panel/calendar/store');
            console.table(payload);       // نمای جدولی
            console.log(payload);         // آبجکت کامل
            console.groupEnd();

// برای دسترسی بعدی در کنسول:
            window._lastCreateEventPayload = payload;

            $.ajax({
                url: '/panel/calendar/store',
                method: 'POST',
                data: payload,
                dataType: 'json',
                success: function (res) {
                    const normalized = normalizeServerEvent(res);
                    addEventLocal(normalized);
                    bsAddEventSidebar.hide();
                    resetValues();
                },
                error: function (xhr) {
                    console.error('خطا در ذخیره رویداد:', xhr.responseText || xhr.statusText);
                    alert('خطا در ذخیره‌سازی. کنسول را چک کنید.');
                }
            });
        });

        // ---------- Update event (AJAX -> update) ----------

        btnUpdateEvent.addEventListener('click', async e => {
            if (!eventToUpdate) return;

            const status = await fv.validate(); // اجرای صریح ولیدیشن
            if (status !== 'Valid') return;

            const startDateObj = getDateFromInput(start, eventStartDate.value);
            const endDateObj = getDateFromInput(end, eventEndDate.value);

            if (!startDateObj || isBeforeToday(startDateObj)) {
                alert('امکان ثبت رویداد با تاریخ شروع گذشته از امروز وجود ندارد.');
                return;
            }
            if (endDateObj && endDateObj < startDateObj) {
                alert('تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.');
                return;
            }

            const payload = {
                _method: 'PUT',
                eventTitle: eventTitle.value,
                eventLabel: eventLabel.val() || null,
                eventStartDate: formatDateForServer(startDateObj, allDaySwitch.checked),
                eventEndDate: formatDateForServer(endDateObj, allDaySwitch.checked),
                allDay: allDaySwitch.checked ? 1 : 0,
                eventURL: eventUrl.value || null,
                eventLocation: eventLocation.value || null,
                eventDescription: eventDescription.value || null,
                'eventGuests[]': eventGuests.val() || []
            };

            $.ajax({
                url: '/panel/calendar/update/' + eventToUpdate.id,
                method: 'patch',
                data: payload,
                dataType: 'json',
                success: function (res) {
                    const normalized = normalizeServerEvent(res);
                    updateEventLocal(normalized);
                    bsAddEventSidebar.hide();
                    resetValues();
                },
                error: function (xhr) {
                    console.error('خطا در بروزرسانی رویداد:', xhr.responseText || xhr.statusText);
                    alert('خطا در بروزرسانی. کنسول را چک کنید.');
                }
            });
        });

        // ---------- Delete event (AJAX -> delete) ----------
        btnDeleteEvent.addEventListener('click', e => {
            if (!eventToUpdate) return;
            if (!confirm('آیا از حذف این رویداد مطمئن هستید؟')) return;

            $.ajax({
                url: '/panel/calendar/delete/' + eventToUpdate.id,
                method: 'delete',
                data: {_method: 'DELETE'},
                dataType: 'json',
                success: function (res) {
                    removeEventLocal(eventToUpdate.id);
                    bsAddEventSidebar.hide();
                    resetValues();
                },
                error: function (xhr) {
                    console.error('خطا در حذف رویداد:', xhr.responseText || xhr.statusText);
                    alert('خطا در حذف. کنسول را چک کنید.');
                }
            });
        });

        // ---------- Reset form ----------
        function resetValues() {
            try {
                if (end) end.clear();
                if (start) start.clear();
            } catch (e) {
            }
            eventUrl.value = '';
            eventTitle.value = '';
            eventLocation.value = '';
            if (allDaySwitch) allDaySwitch.checked = false;
            if (eventGuests && eventGuests.val) eventGuests.val('').trigger('change');
            eventDescription.value = '';
        }

        addEventSidebar.addEventListener('hidden.bs.offcanvas', function () {
            resetValues();
            // try {
            //     if (start) {
            //         start.destroy();
            //         start = null;
            //     }
            // } catch (e) {
            // }
            // try {
            //     if (end) {
            //         end.destroy();
            //         end = null;
            //     }
            // } catch (e) {
            // }
        });


        // ---------- Sidebar toggle behaviour ----------
        btnToggleSidebar.addEventListener('click', e => {
            btnDeleteEvent.classList.add('d-none');
            btnUpdateEvent.classList.add('d-none');
            btnAddEvent.classList.remove('d-none');
            appCalendarSidebar.classList.remove('show');
            appOverlay.classList.remove('show');
        });

        // ---------- Filter checkboxes ----------
        if (selectAll) {
            selectAll.addEventListener('click', e => {
                if (e.currentTarget.checked) document.querySelectorAll('.input-filter').forEach(c => (c.checked = 1));
                else document.querySelectorAll('.input-filter').forEach(c => (c.checked = 0));
                calendar.refetchEvents();
            });
        }
        if (filterInput) {
            filterInput.forEach(item => {
                item.addEventListener('click', () => {
                    document.querySelectorAll('.input-filter:checked').length < document.querySelectorAll('.input-filter').length
                        ? (selectAll.checked = false)
                        : (selectAll.checked = true);
                    calendar.refetchEvents();
                });
            });
        }

        // ---------- inline calendar change ----------
        if (inlineCalInstance) {
            inlineCalInstance.config.onChange.push(function (date) {
                calendar.changeView(calendar.view.type, moment(date[0]['_date']).format('YYYY-MM-DD'));
                modifyToggler();
                appCalendarSidebar.classList.remove('show');
                appOverlay.classList.remove('show');
            });
        }
    })();
});
