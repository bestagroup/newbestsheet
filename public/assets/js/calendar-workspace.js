(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const workspace = document.getElementById('calendarWorkspace');
        const calendarEl = document.getElementById('calendar');

        if (!workspace || !calendarEl || typeof Calendar === 'undefined' || typeof JDate === 'undefined') {
            return;
        }

        const jalaliMonths = [
            'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];
        const jalaliMonthsShort = ['فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا', 'آذر', 'دی', 'بهم', 'اسف'];
        const weekDays = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        const labelMeta = {
            meeting: {title: 'جلسه', color: 'primary'},
            session: {title: 'نشست', color: 'danger'},
            task: {title: 'تسک', color: 'warning'},
            event: {title: 'رویداد و برنامه', color: 'info'},
            person: {title: 'شخصی', color: 'success'},
            other: {title: 'سایر', color: 'secondary'}
        };

        const urls = {
            events: workspace.dataset.eventsUrl,
            store: workspace.dataset.storeUrl,
            update: workspace.dataset.updateUrl,
            destroy: workspace.dataset.deleteUrl
        };

        const els = {
            sidebar: document.getElementById('app-calendar-sidebar'),
            overlay: workspace.querySelector('.app-overlay'),
            addSidebar: document.getElementById('addEventSidebar'),
            form: document.getElementById('eventForm'),
            formTitle: document.getElementById('addEventSidebarLabel'),
            title: document.getElementById('eventTitle'),
            label: $('#eventLabel'),
            start: document.getElementById('eventStartDate'),
            end: document.getElementById('eventEndDate'),
            startTime: document.getElementById('eventStartTime'),
            endTime: document.getElementById('eventEndTime'),
            timeFields: Array.from(workspace.querySelectorAll('.calendar-time-field')),
            timeRequiredMarks: Array.from(workspace.querySelectorAll('.event-time-required')),
            allDay: document.getElementById('eventAllDay'),
            guests: $('#eventGuests'),
            location: document.getElementById('eventLocation'),
            url: document.getElementById('eventURL'),
            description: document.getElementById('eventDescription'),
            addButton: workspace.querySelector('.btn-add-event'),
            updateButton: workspace.querySelector('.btn-update-event'),
            deleteButton: workspace.querySelector('.btn-delete-event'),
            cancelButton: workspace.querySelector('.btn-cancel'),
            newButton: workspace.querySelector('.btn-toggle-sidebar'),
            readOnlyNotice: document.getElementById('eventReadonlyNotice'),
            ownerPanel: document.getElementById('eventOwnerPanel'),
            creatorName: document.getElementById('eventCreatorName'),
            syncStatus: document.getElementById('eventSyncStatus'),
            actionLink: document.getElementById('eventActionLink'),
            inlineCalendar: workspace.querySelector('.inline-calendar'),
            search: document.getElementById('calendarSearch'),
            clearSearch: document.getElementById('clearCalendarSearch'),
            selectAll: document.getElementById('selectAll'),
            selectAllText: workspace.querySelector('.select-all-text'),
            filters: Array.from(workspace.querySelectorAll('.input-filter')),
            agenda: document.getElementById('calendarAgenda'),
            todayCount: document.getElementById('todayEventsCount'),
            upcomingCount: document.getElementById('upcomingEventsCount'),
            visibleCount: document.getElementById('visibleEventsCount'),
            todayLabel: document.getElementById('jalaliToday'),
            viewTitle: document.getElementById('calendarViewTitle'),
            loadState: document.getElementById('calendarLoadState'),
            durationButtons: Array.from(workspace.querySelectorAll('[data-duration]'))
        };

        let calendar;
        let startPicker;
        let endPicker;
        let inlinePicker;
        let selectedEvent = null;
        let pendingDates = null;
        let searchTimer = null;
        let currentEvents = [];
        let lastSelectionHandledAt = 0;

        const eventOffcanvas = new bootstrap.Offcanvas(els.addSidebar);
        const csrf = document.querySelector('meta[name="csrf-token"]');

        if (csrf && window.jQuery) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrf.content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        }

        function faDigits(value) {
            return String(value).replace(/\d/g, function (digit) {
                return '۰۱۲۳۴۵۶۷۸۹'[Number(digit)];
            });
        }

        function pad(value) {
            return String(value).padStart(2, '0');
        }

        function toJsDate(value) {
            if (!value) return null;
            if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : new Date(value.getTime());
            if (value instanceof JDate || (typeof value === 'object' && value._date instanceof Date)) {
                return new Date(value._date.getTime());
            }
            if (typeof value === 'string') {
                const clean = value.trim();
                const normalizedDigits = clean.replace(/[۰-۹]/g, function (digit) {
                    return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit));
                });
                const leadingYear = Number((normalizedDigits.match(/^(\d{4})[\/-]/) || [])[1]);
                const isJalali = clean.includes('/') || /[۰-۹]/.test(clean) || (leadingYear && leadingYear < 1700);
                if (isJalali) {
                    try {
                        const jalali = new JDate(normalizedDigits.replace(/-/g, '/'));
                        if (jalali._date instanceof Date && !Number.isNaN(jalali._date.getTime())) {
                            return new Date(jalali._date.getTime());
                        }
                    } catch (_) {
                        // Continue with the Gregorian parser for ISO values.
                    }
                }
                const parsed = new Date(clean.replace(' ', 'T'));
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            }
            return null;
        }

        function startOfDay(value) {
            const date = toJsDate(value) || new Date();
            date.setHours(0, 0, 0, 0);
            return date;
        }

        function endOfDay(value) {
            const date = startOfDay(value);
            date.setHours(23, 59, 59, 999);
            return date;
        }

        function isBeforeToday(value) {
            const date = toJsDate(value);
            return date ? startOfDay(date) < startOfDay(new Date()) : false;
        }

        function jalaliMonthStart(value, offset) {
            const jalali = new JDate(toJsDate(value) || new Date());
            let year = jalali.getFullYear();
            let month = jalali.getMonth() + (offset || 0);

            while (month < 0) {
                month += 12;
                year -= 1;
            }
            while (month > 11) {
                month -= 12;
                year += 1;
            }

            return new Date(new JDate(year, month, 1)._date.getTime());
        }

        function jalaliMonthRange(value) {
            return {
                start: jalaliMonthStart(value, 0),
                end: jalaliMonthStart(value, 1)
            };
        }

        function formatJalaliDate(value, options) {
            const date = toJsDate(value);
            if (!date) return '—';
            const jalali = new JDate(date);
            const settings = Object.assign({weekday: false, year: true, time: false, shortMonth: false}, options || {});
            const parts = [];

            if (settings.weekday) parts.push(weekDays[date.getDay()]);
            parts.push(faDigits(jalali.getDate()));
            parts.push(settings.shortMonth ? jalaliMonthsShort[jalali.getMonth()] : jalaliMonths[jalali.getMonth()]);
            if (settings.year) parts.push(faDigits(jalali.getFullYear()));

            let result = parts.join(' ');
            if (settings.time) result += '، ساعت ' + faDigits(pad(date.getHours()) + ':' + pad(date.getMinutes()));
            return result;
        }

        function formatJalaliMonth(value) {
            const jalali = new JDate(toJsDate(value) || new Date());
            return jalaliMonths[jalali.getMonth()] + ' ' + faDigits(jalali.getFullYear());
        }

        function formatForServer(value, allDay) {
            const date = toJsDate(value);
            if (!date) return null;
            const jalali = new JDate(date);
            const datePart = jalali.getFullYear() + '-' + pad(jalali.getMonth() + 1) + '-' + pad(jalali.getDate());
            return allDay ? datePart : datePart + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':00';
        }

        function inclusiveEventEnd(event) {
            const end = toJsDate(event.end || event.start);
            if (event.allDay && event.end) end.setDate(end.getDate() - 1);
            return end;
        }

        function selectedCalendars() {
            return els.filters.filter(function (filter) { return filter.checked; }).map(function (filter) {
                return filter.dataset.value;
            });
        }

        function normalizedLabel(label) {
            const value = String(label || 'other').toLowerCase();
            return labelMeta[value] ? value : 'other';
        }

        function normalizeServerEvent(event) {
            const props = event.extendedProps || {};
            let guests = props.guests || event.guests || [];
            if (typeof guests === 'string') {
                try { guests = JSON.parse(guests); } catch (_) { guests = []; }
            }
            const label = normalizedLabel(props.calendar || event.calendar || event.label);

            return {
                id: event.id,
                title: event.title || '',
                start: event.start || null,
                end: event.end || event.start || null,
                allDay: Boolean(event.allDay !== undefined ? event.allDay : event.all_day),
                url: event.url || null,
                editable: Boolean(event.editable !== undefined ? event.editable : props.canEdit),
                extendedProps: {
                    calendar: label,
                    location: props.location || event.location || '',
                    description: props.description || event.description || '',
                    guests: Array.isArray(guests) ? guests.map(String) : [],
                    guestCount: Number(props.guestCount || (Array.isArray(guests) ? guests.length : 0)),
                    creatorName: props.creatorName || 'سامانه',
                    canEdit: Boolean(props.canEdit !== undefined ? props.canEdit : event.editable),
                    canDelete: Boolean(props.canDelete !== undefined ? props.canDelete : event.editable),
                    jalaliStart: props.jalaliStart || null,
                    jalaliEnd: props.jalaliEnd || null,
                    googleSyncStatus: props.googleSyncStatus || 'not_synced'
                }
            };
        }

        function currentFilteredEvents() {
            const selected = selectedCalendars();
            return currentEvents.filter(function (event) {
                return selected.includes(normalizedLabel(event.extendedProps.calendar));
            });
        }

        function setLoadState(mode, message) {
            els.loadState.classList.toggle('is-hidden', mode === 'hidden');
            els.loadState.classList.toggle('is-error', mode === 'error');
            const text = els.loadState.querySelector('span:last-child');
            if (text && message) text.textContent = message;
        }

        function fetchEvents(info, successCallback, failureCallback) {
            setLoadState('loading', 'در حال دریافت برنامه‌ها…');
            $.ajax({
                url: urls.events,
                method: 'GET',
                dataType: 'json',
                data: {
                    start: info.startStr,
                    end: info.endStr,
                    q: els.search.value.trim()
                }
            }).done(function (response) {
                currentEvents = Array.isArray(response) ? response.map(normalizeServerEvent) : [];
                const filtered = currentFilteredEvents();
                successCallback(filtered);
                updateDashboard(filtered);
                setLoadState('hidden');
            }).fail(function (xhr) {
                currentEvents = [];
                updateDashboard([]);
                setLoadState('error', 'دریافت برنامه‌ها ناموفق بود؛ دوباره تلاش کنید.');
                if (typeof failureCallback === 'function') failureCallback(xhr);
            });
        }

        function updateDashboard(events) {
            const now = new Date();
            const todayStart = startOfDay(now);
            const todayEnd = endOfDay(now);
            const visible = events || [];
            const today = visible.filter(function (event) {
                const start = toJsDate(event.start);
                const end = toJsDate(event.end || event.start);
                return start && end && start <= todayEnd && end >= todayStart;
            });
            const upcoming = visible.filter(function (event) {
                return toJsDate(event.end || event.start) >= now;
            });

            els.todayCount.textContent = faDigits(today.length);
            els.upcomingCount.textContent = faDigits(upcoming.length);
            els.visibleCount.textContent = faDigits(visible.length);

            Object.keys(labelMeta).forEach(function (label) {
                const countEl = workspace.querySelector('[data-count-for="' + label + '"]');
                const count = currentEvents.filter(function (event) {
                    return normalizedLabel(event.extendedProps.calendar) === label;
                }).length;
                if (countEl) countEl.textContent = faDigits(count);
            });

            renderAgenda(upcoming);
        }

        function renderAgenda(events) {
            els.agenda.innerHTML = '';
            const upcoming = events.slice().sort(function (a, b) {
                return toJsDate(a.start) - toJsDate(b.start);
            }).slice(0, 6);

            if (!upcoming.length) {
                const empty = document.createElement('div');
                empty.className = 'calendar-agenda__empty';
                empty.innerHTML = '<i class="mdi mdi-calendar-blank-outline" aria-hidden="true"></i><span>برنامه‌ای در این بازه نیست.</span>';
                els.agenda.appendChild(empty);
                return;
            }

            upcoming.forEach(function (event) {
                const start = toJsDate(event.start);
                const jalali = new JDate(start);
                const item = document.createElement('button');
                const dateBox = document.createElement('span');
                const content = document.createElement('span');
                const title = document.createElement('strong');
                const meta = document.createElement('span');
                const category = labelMeta[normalizedLabel(event.extendedProps.calendar)];

                item.type = 'button';
                item.className = 'calendar-agenda__item';
                item.dataset.eventId = event.id;
                dateBox.className = 'calendar-agenda__date';
                dateBox.innerHTML = '<strong>' + faDigits(jalali.getDate()) + '</strong><small>' + jalaliMonthsShort[jalali.getMonth()] + '</small>';
                content.className = 'calendar-agenda__content';
                title.textContent = event.title;
                meta.className = 'calendar-agenda__meta';
                meta.innerHTML = '<span class="calendar-filter-dot bg-' + category.color + '"></span>';

                const metaText = document.createElement('span');
                metaText.textContent = event.allDay ? 'تمام‌روز' : faDigits(pad(start.getHours()) + ':' + pad(start.getMinutes()));
                meta.appendChild(metaText);

                if (event.extendedProps.location) {
                    const location = document.createElement('span');
                    location.textContent = '• ' + event.extendedProps.location;
                    meta.appendChild(location);
                }

                content.append(title, meta);
                item.append(dateBox, content);
                item.addEventListener('click', function () {
                    const calendarEvent = calendar.getEventById(String(event.id));
                    if (calendarEvent) openEvent(calendarEvent);
                });
                els.agenda.appendChild(item);
            });
        }

        function renderEventContent(arg) {
            const wrapper = document.createElement('span');
            wrapper.className = 'calendar-event-content';

            if (arg.timeText) {
                const time = document.createElement('span');
                time.className = 'calendar-event-content__time';
                time.textContent = faDigits(arg.timeText);
                wrapper.appendChild(time);
            }

            const title = document.createElement('span');
            title.className = 'calendar-event-content__title';
            title.textContent = arg.event.title;
            wrapper.appendChild(title);
            return {domNodes: [wrapper]};
        }

        function updateViewTitles(view) {
            let title;
            if (view.type === 'jalaliMonth' || view.type === 'jalaliList') {
                title = formatJalaliMonth(calendar.getDate());
            } else if (view.type === 'timeGridDay') {
                title = formatJalaliDate(view.currentStart, {weekday: true, year: true});
            } else {
                const end = new Date(view.currentEnd.getTime() - 86400000);
                title = formatJalaliDate(view.currentStart, {year: true, shortMonth: true}) + ' تا ' + formatJalaliDate(end, {year: true, shortMonth: true});
            }

            els.viewTitle.textContent = title;
            requestAnimationFrame(function () {
                const toolbarTitle = calendarEl.querySelector('.fc-toolbar-title');
                if (toolbarTitle) toolbarTitle.textContent = title;
                localizeListHeadings();
            });
        }

        function localizeListHeadings() {
            calendarEl.querySelectorAll('.fc-list-day[data-date]').forEach(function (row) {
                const date = toJsDate(row.dataset.date);
                const main = row.querySelector('.fc-list-day-text');
                const side = row.querySelector('.fc-list-day-side-text');
                if (main) main.textContent = formatJalaliDate(date, {weekday: true, year: false});
                if (side) side.textContent = faDigits(new JDate(date).getFullYear());
            });
        }

        function modifySidebarToggler() {
            const button = calendarEl.querySelector('.fc-sidebarToggle-button');
            if (!button) return;
            button.classList.remove('fc-button-primary');
            button.classList.add('d-lg-none', 'calendar-mobile-menu');
            button.innerHTML = '<i class="mdi mdi-menu" aria-hidden="true"></i><span class="visually-hidden">نمایش ابزارهای تقویم</span>';
            button.setAttribute('data-bs-toggle', 'sidebar');
            button.setAttribute('data-overlay', '');
            button.setAttribute('data-target', '#app-calendar-sidebar');
        }

        function navigate(offset) {
            if (calendar.view.type === 'jalaliMonth' || calendar.view.type === 'jalaliList') {
                calendar.gotoDate(jalaliMonthStart(calendar.view.currentStart || calendar.getDate(), offset));
            } else if (offset < 0) {
                calendar.prev();
            } else {
                calendar.next();
            }
        }

        const plugins = calendarPlugins;
        calendar = new Calendar(calendarEl, {
            initialView: 'jalaliMonth',
            initialDate: new Date(),
            plugins: [plugins.interaction, plugins.dayGrid, plugins.timeGrid, plugins.list],
            views: {
                jalaliMonth: {
                    type: 'dayGrid',
                    buttonText: 'ماه',
                    fixedWeekCount: false,
                    visibleRange: jalaliMonthRange
                },
                jalaliList: {
                    type: 'list',
                    buttonText: 'فهرست',
                    visibleRange: jalaliMonthRange
                }
            },
            events: fetchEvents,
            direction: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
            locale: 'fa',
            firstDay: 6,
            nowIndicator: true,
            navLinks: false,
            selectable: true,
            selectMirror: true,
            editable: true,
            eventResizableFromStart: true,
            dragScroll: true,
            dayMaxEvents: 3,
            height: 'auto',
            scrollTime: '08:00:00',
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            slotDuration: '00:30:00',
            slotLabelFormat: {hour: '2-digit', minute: '2-digit', hour12: false},
            eventTimeFormat: {hour: '2-digit', minute: '2-digit', hour12: false},
            customButtons: {
                sidebarToggle: {text: 'ابزارها'},
                todayJalali: {text: 'امروز', click: function () { calendar.today(); }},
                prevJalali: {text: 'قبلی', click: function () { navigate(-1); }},
                nextJalali: {text: 'بعدی', click: function () { navigate(1); }}
            },
            headerToolbar: {
                start: 'sidebarToggle todayJalali',
                center: 'prevJalali title nextJalali',
                end: 'jalaliMonth,timeGridWeek,timeGridDay,jalaliList'
            },
            buttonText: {today: 'امروز', month: 'ماه', week: 'هفته', day: 'روز', list: 'فهرست'},
            allDayText: 'تمام‌روز',
            weekText: 'هفته',
            moreLinkText: function (count) { return '+' + faDigits(count) + ' برنامه'; },
            noEventsText: 'در این بازه برنامه‌ای وجود ندارد.',
            dayHeaderContent: function (arg) {
                return weekDays[arg.date.getDay()];
            },
            dayCellContent: function (arg) {
                return faDigits(new JDate(arg.date).getDate());
            },
            dayCellClassNames: function (arg) {
                return isBeforeToday(arg.date) ? ['calendar-day--past'] : [];
            },
            dayCellDidMount: function (arg) {
                if (!isBeforeToday(arg.date)) return;
                arg.el.setAttribute('aria-disabled', 'true');
                arg.el.setAttribute('title', 'این روز گذشته است؛ فقط برنامه‌های ثبت‌شده قابل مشاهده‌اند.');
            },
            eventClassNames: function (arg) {
                const label = normalizedLabel(arg.event.extendedProps.calendar);
                return ['fc-event-' + labelMeta[label].color];
            },
            eventContent: renderEventContent,
            eventDidMount: function (info) {
                const props = info.event.extendedProps;
                const detail = [formatJalaliDate(info.event.start, {weekday: true, year: true, time: !info.event.allDay})];
                if (props.location) detail.push(props.location);
                info.el.setAttribute('title', detail.join(' — '));
                window.setTimeout(localizeListHeadings, 0);
            },
            select: function (info) {
                lastSelectionHandledAt = Date.now();
                handleCalendarSelection(info);
            },
            dateClick: function (info) {
                if (isBeforeToday(info.date)) return;
                window.setTimeout(function () {
                    // FullCalendar normally emits `select` for a click as well. This
                    // fallback keeps single-day clicks reliable without opening the
                    // form twice when both callbacks are emitted.
                    if (Date.now() - lastSelectionHandledAt < 150) return;
                    handleCalendarSelection({
                        start: info.date,
                        end: info.date,
                        allDay: info.allDay,
                        view: info.view,
                        isSingleClick: true
                    });
                }, 0);
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                info.jsEvent.stopPropagation();
                openEvent(info.event);
            },
            selectAllow: function (info) {
                return !isBeforeToday(info.start);
            },
            eventAllow: function (dropInfo) {
                return !isBeforeToday(dropInfo.start);
            },
            eventDrop: persistCalendarMove,
            eventResize: persistCalendarMove,
            datesSet: function (info) {
                modifySidebarToggler();
                updateViewTitles(info.view);
            },
            viewDidMount: modifySidebarToggler
        });

        calendar.render();

        function initSelect2() {
            function renderLabel(option) {
                if (!option.id) return option.text;
                return '<span class="badge badge-dot bg-' + $(option.element).data('label') + ' me-2"></span>' + option.text;
            }

            function renderGuest(option) {
                if (!option.id) return option.text;
                const avatar = $(option.element).data('avatar');
                return '<span class="d-flex align-items-center"><span class="avatar avatar-xs me-2"><img src="' + avatar + '" alt="" class="rounded-circle"></span>' + $('<div>').text(option.text).html() + '</span>';
            }

            els.label.wrap('<div class="position-relative"></div>').select2({
                dropdownParent: els.label.parent(),
                minimumResultsForSearch: -1,
                templateResult: renderLabel,
                templateSelection: renderLabel,
                escapeMarkup: function (markup) { return markup; }
            });

            els.guests.wrap('<div class="position-relative"></div>').select2({
                dropdownParent: els.guests.parent(),
                placeholder: 'انتخاب کارکنان',
                closeOnSelect: false,
                templateResult: renderGuest,
                templateSelection: renderGuest,
                escapeMarkup: function (markup) { return markup; }
            });
        }

        function pickerDate(picker) {
            if (!picker || !picker.selectedDates || !picker.selectedDates.length) return null;
            return toJsDate(picker.selectedDates[0]);
        }

        function setPickerDate(picker, value, trigger) {
            const date = toJsDate(value);
            if (picker && date) picker.setDate(date, trigger !== false);
        }

        function buildPickers() {
            if (startPicker) startPicker.destroy();
            if (endPicker) endPicker.destroy();

            const common = {
                locale: 'fa',
                disableMobile: true,
                allowInput: false,
                clickOpens: true,
                enableTime: false,
                dateFormat: 'Y/m/d',
                position: 'auto center',
                appendTo: document.body
            };

            startPicker = flatpickr(els.start, Object.assign({}, common, {
                onChange: function (selected) {
                    if (endPicker) endPicker.set('minDate', selected.length ? toJsDate(selected[0]) : null);
                }
            }));

            endPicker = flatpickr(els.end, Object.assign({}, common, {
                onChange: function (selected) {
                    const end = selected.length ? toJsDate(selected[0]) : null;
                    if (startPicker) startPicker.set('maxDate', end || null);
                }
            }));
        }

        function initInlineCalendar() {
            inlinePicker = flatpickr(els.inlineCalendar, {
                inline: true,
                monthSelectorType: 'static',
                locale: 'fa',
                disableMobile: true,
                onChange: function (selected) {
                    if (!selected.length) return;
                    calendar.gotoDate(toJsDate(selected[0]));
                    closeMobileSidebar();
                }
            });
        }

        function defaultTimesForDate(value) {
            const date = toJsDate(value) || new Date();
            const now = new Date();
            if (startOfDay(date).getTime() === startOfDay(now).getTime()) {
                const minutes = Math.ceil(now.getMinutes() / 15) * 15;
                date.setHours(now.getHours(), minutes, 0, 0);
            } else {
                date.setHours(9, 0, 0, 0);
            }
            const end = new Date(date.getTime() + 60 * 60000);
            return {start: timeValue(date), end: timeValue(end)};
        }

        function timeValue(value) {
            const date = toJsDate(value);
            return date ? pad(date.getHours()) + ':' + pad(date.getMinutes()) : '';
        }

        function combineDateAndTime(dateValue, time, allDay) {
            const date = toJsDate(dateValue);
            if (!date) return null;
            if (allDay) {
                date.setHours(0, 0, 0, 0);
                return date;
            }
            if (!/^\d{2}:\d{2}$/.test(time || '')) return null;
            const parts = time.split(':').map(Number);
            date.setHours(parts[0], parts[1], 0, 0);
            return date;
        }

        function formDateRange() {
            const allDay = els.allDay.checked;
            return {
                start: combineDateAndTime(pickerDate(startPicker), els.startTime.value, allDay),
                end: combineDateAndTime(pickerDate(endPicker), els.endTime.value, allDay)
            };
        }

        function updateTimeFieldsState() {
            const allDay = els.allDay.checked;
            const readOnly = els.form.classList.contains('is-readonly');
            els.timeFields.forEach(function (field) { field.classList.toggle('d-none', allDay); });
            els.timeRequiredMarks.forEach(function (mark) { mark.classList.toggle('d-none', allDay); });
            els.startTime.disabled = allDay || readOnly;
            els.endTime.disabled = allDay || readOnly;
            els.startTime.required = !allDay;
            els.endTime.required = !allDay;
            els.durationButtons.forEach(function (button) { button.disabled = allDay || readOnly; });
        }

        function handleCalendarSelection(info) {
            const start = toJsDate(info.start);
            let end = toJsDate(info.end || info.start);
            if (!start || !end) return;

            if (isBeforeToday(start)) {
                calendar.unselect();
                return;
            }

            const isTimeGrid = info.view && info.view.type.indexOf('timeGrid') === 0;
            const isAllDaySlot = Boolean(isTimeGrid && info.allDay);
            const isDateRange = Boolean(info.allDay && !info.isSingleClick);

            if (!info.allDay && info.isSingleClick) {
                end = new Date(start.getTime() + 60 * 60000);
            }

            // FullCalendar reports the end of an all-day selection as exclusive.
            // Converting it to an inclusive value makes a three-day selection fill
            // the first selected day through the third selected day in the form.
            if (isDateRange) end.setDate(end.getDate() - 1);

            const fallbackTimes = defaultTimesForDate(start);
            openCreateRange({
                start: start,
                end: end,
                allDay: isAllDaySlot,
                startTime: info.allDay ? fallbackTimes.start : timeValue(start),
                endTime: info.allDay ? fallbackTimes.end : timeValue(end)
            });
        }

        function setReadOnly(readOnly) {
            els.form.classList.toggle('is-readonly', readOnly);
            els.readOnlyNotice.classList.toggle('d-none', !readOnly);
            els.form.querySelectorAll('input, textarea').forEach(function (control) {
                control.disabled = readOnly;
            });
            els.label.prop('disabled', readOnly).trigger('change.select2');
            els.guests.prop('disabled', readOnly).trigger('change.select2');
            updateTimeFieldsState();
            els.cancelButton.textContent = readOnly ? 'بستن' : 'انصراف';
        }

        function resetForm() {
            selectedEvent = null;
            pendingDates = null;
            els.form.reset();
            setReadOnly(false);
            els.label.val('meeting').trigger('change');
            els.guests.val(null).trigger('change');
            els.ownerPanel.classList.add('d-none');
            els.actionLink.classList.add('d-none');
            els.actionLink.removeAttribute('href');
            els.readOnlyNotice.classList.add('d-none');
            els.addButton.classList.remove('d-none');
            els.updateButton.classList.add('d-none');
            els.deleteButton.classList.add('d-none');
            els.formTitle.textContent = 'افزودن برنامه';
            buildPickers();
            startPicker.clear();
            endPicker.clear();
            startPicker.set('maxDate', null);
            endPicker.set('minDate', null);
            els.startTime.value = '';
            els.endTime.value = '';
            updateTimeFieldsState();
            clearValidation();
        }

        function openCreateRange(range) {
            resetForm();
            els.allDay.checked = Boolean(range.allDay);
            pendingDates = range;
            updateTimeFieldsState();
            eventOffcanvas.show();
        }

        function applyPendingDates() {
            if (!pendingDates) return;
            setPickerDate(startPicker, pendingDates.start, false);
            setPickerDate(endPicker, pendingDates.end, false);
            endPicker.set('minDate', pendingDates.start);
            startPicker.set('maxDate', pendingDates.end);
            els.startTime.value = pendingDates.allDay ? '' : pendingDates.startTime;
            els.endTime.value = pendingDates.allDay ? '' : pendingDates.endTime;
            updateTimeFieldsState();
            pendingDates = null;
        }

        function syncStatusMeta(status) {
            const statuses = {
                synced: ['همگام با گوگل', 'success'],
                failed: ['خطا در همگام‌سازی', 'danger'],
                not_connected: ['ذخیره محلی', 'secondary'],
                not_synced: ['ذخیره محلی', 'secondary']
            };
            return statuses[status] || statuses.not_synced;
        }

        function openEvent(event) {
            resetForm();
            selectedEvent = event;
            const props = event.extendedProps;
            const isPastEvent = inclusiveEventEnd(event) < startOfDay(new Date());
            const canEdit = Boolean(props.canEdit) && !isPastEvent;

            els.formTitle.textContent = canEdit ? 'ویرایش برنامه' : 'جزئیات برنامه';
            els.addButton.classList.add('d-none');
            els.updateButton.classList.toggle('d-none', !canEdit);
            els.deleteButton.classList.toggle('d-none', !props.canDelete);
            els.ownerPanel.classList.remove('d-none');
            els.creatorName.textContent = props.creatorName || 'سامانه';
            els.readOnlyNotice.innerHTML = isPastEvent
                ? '<i class="mdi mdi-calendar-lock-outline me-1"></i>زمان این برنامه گذشته است و جزئیات آن فقط برای مشاهده نمایش داده می‌شود.'
                : '<i class="mdi mdi-eye-outline me-1"></i>این برنامه به شما تخصیص داده شده است و فقط ایجادکننده می‌تواند آن را ویرایش کند.';

            const sync = syncStatusMeta(props.googleSyncStatus);
            els.syncStatus.textContent = sync[0];
            els.syncStatus.className = 'badge bg-label-' + sync[1];

            els.title.value = event.title || '';
            els.label.val(normalizedLabel(props.calendar)).trigger('change');
            els.allDay.checked = event.allDay;
            updateTimeFieldsState();

            const rawStart = props.jalaliStart ? toJsDate(props.jalaliStart) : event.start;
            const rawEnd = props.jalaliEnd ? toJsDate(props.jalaliEnd) : inclusiveEventEnd(event);
            setPickerDate(startPicker, rawStart, false);
            setPickerDate(endPicker, rawEnd, false);
            endPicker.set('minDate', rawStart);
            startPicker.set('maxDate', rawEnd);
            els.startTime.value = event.allDay ? '' : timeValue(rawStart);
            els.endTime.value = event.allDay ? '' : timeValue(rawEnd);
            els.guests.val((props.guests || []).map(String)).trigger('change');
            els.location.value = props.location || '';
            els.url.value = event.url || '';
            els.description.value = props.description || '';
            if (event.url) {
                els.actionLink.href = event.url;
                els.actionLink.classList.remove('d-none');
            }
            setReadOnly(!canEdit);
            eventOffcanvas.show();
        }

        function clearValidation() {
            els.form.querySelectorAll('.is-invalid').forEach(function (input) { input.classList.remove('is-invalid'); });
        }

        function validateForm() {
            clearValidation();
            const dates = formDateRange();
            const start = dates.start;
            const end = dates.end;
            const invalid = [];

            if (!els.title.value.trim()) invalid.push(els.title);
            if (!pickerDate(startPicker)) invalid.push(els.start);
            if (!pickerDate(endPicker)) invalid.push(els.end);
            if (!els.allDay.checked && !els.startTime.value) invalid.push(els.startTime);
            if (!els.allDay.checked && !els.endTime.value) invalid.push(els.endTime);

            invalid.forEach(function (input) { input.classList.add('is-invalid'); });
            if (invalid.length) {
                invalid[0].focus();
                notify('عنوان، تاریخ شروع، تاریخ پایان و ساعت‌ها را کامل کنید.', 'warning');
                return false;
            }
            if (!start || !end) {
                notify('بازه زمانی واردشده معتبر نیست.', 'warning');
                return false;
            }
            if (isBeforeToday(start)) {
                notify('امکان ثبت برنامه با تاریخ شروع گذشته وجود ندارد.', 'warning');
                return false;
            }
            if (end < start) {
                notify('زمان پایان نمی‌تواند قبل از زمان شروع باشد.', 'warning');
                return false;
            }
            return true;
        }

        function formPayload() {
            const allDay = els.allDay.checked;
            const dates = formDateRange();
            return {
                eventTitle: els.title.value.trim(),
                eventLabel: els.label.val() || 'other',
                eventStartDate: formatForServer(dates.start, allDay),
                eventEndDate: formatForServer(dates.end, allDay),
                allDay: allDay ? 1 : 0,
                eventURL: els.url.value.trim() || null,
                eventLocation: els.location.value.trim() || null,
                eventDescription: els.description.value.trim() || null,
                'eventGuests[]': els.guests.val() || []
            };
        }

        function eventPayload(event) {
            const props = event.extendedProps;
            return {
                eventTitle: event.title,
                eventLabel: normalizedLabel(props.calendar),
                eventStartDate: formatForServer(event.start, event.allDay),
                eventEndDate: formatForServer(inclusiveEventEnd(event), event.allDay),
                allDay: event.allDay ? 1 : 0,
                eventURL: event.url || null,
                eventLocation: props.location || null,
                eventDescription: props.description || null,
                'eventGuests[]': props.guests || []
            };
        }

        function urlFor(template, id) {
            return template.replace('__ID__', encodeURIComponent(id));
        }

        function setButtonLoading(button, loading) {
            if (!button) return;
            button.disabled = loading;
            const label = button.querySelector('.button-label');
            if (!label) return;
            if (loading) {
                label.dataset.original = label.innerHTML;
                label.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>لطفاً صبر کنید…';
            } else if (label.dataset.original) {
                label.innerHTML = label.dataset.original;
                delete label.dataset.original;
            }
        }

        function requestErrorMessage(xhr) {
            const response = xhr && xhr.responseJSON;
            if (response && response.errors) {
                const messages = Object.values(response.errors).flat();
                if (messages.length) return messages[0];
            }
            if (xhr && xhr.status === 403) return 'شما اجازه تغییر این برنامه را ندارید.';
            if (xhr && xhr.status === 422) return 'اطلاعات برنامه معتبر نیست؛ فیلدها را بررسی کنید.';
            return 'عملیات انجام نشد. اتصال خود را بررسی کرده و دوباره تلاش کنید.';
        }

        function notify(message, type) {
            if (window.toastr) {
                const method = type === 'danger' ? 'error' : (type || 'info');
                if (typeof toastr[method] === 'function') toastr[method](message);
                return;
            }
            if (window.Swal) {
                Swal.fire({text: message, icon: type === 'danger' ? 'error' : type, confirmButtonText: 'متوجه شدم'});
                return;
            }
            window.alert(message);
        }

        function confirmDelete() {
            if (!window.Swal) return Promise.resolve(window.confirm('آیا از حذف این برنامه مطمئن هستید؟'));
            return Swal.fire({
                title: 'حذف برنامه؟',
                text: 'این عملیات قابل بازگشت نیست و افراد مرتبط نیز از لغو برنامه مطلع می‌شوند.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود',
                cancelButtonText: 'انصراف',
                customClass: {confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-label-secondary'},
                buttonsStyling: false
            }).then(function (result) { return result.isConfirmed; });
        }

        function saveNewEvent() {
            if (!validateForm()) return;
            setButtonLoading(els.addButton, true);
            $.ajax({url: urls.store, method: 'POST', dataType: 'json', data: formPayload()})
                .done(function () {
                    eventOffcanvas.hide();
                    calendar.refetchEvents();
                    notify('برنامه با موفقیت ثبت شد.', 'success');
                })
                .fail(function (xhr) { notify(requestErrorMessage(xhr), 'danger'); })
                .always(function () { setButtonLoading(els.addButton, false); });
        }

        function updateSelectedEvent() {
            if (!selectedEvent || !validateForm()) return;
            setButtonLoading(els.updateButton, true);
            $.ajax({
                url: urlFor(urls.update, selectedEvent.id),
                method: 'PATCH',
                dataType: 'json',
                data: formPayload()
            }).done(function () {
                eventOffcanvas.hide();
                calendar.refetchEvents();
                notify('تغییرات برنامه ذخیره شد.', 'success');
            }).fail(function (xhr) {
                notify(requestErrorMessage(xhr), 'danger');
            }).always(function () {
                setButtonLoading(els.updateButton, false);
            });
        }

        function persistCalendarMove(info) {
            if (isBeforeToday(info.event.start)) {
                info.revert();
                notify('انتقال برنامه به تاریخ گذشته امکان‌پذیر نیست.', 'warning');
                return;
            }
            $.ajax({
                url: urlFor(urls.update, info.event.id),
                method: 'PATCH',
                dataType: 'json',
                data: eventPayload(info.event)
            }).done(function () {
                calendar.refetchEvents();
                notify('زمان برنامه به‌روزرسانی شد.', 'success');
            }).fail(function (xhr) {
                info.revert();
                notify(requestErrorMessage(xhr), 'danger');
            });
        }

        function deleteSelectedEvent() {
            if (!selectedEvent) return;
            confirmDelete().then(function (confirmed) {
                if (!confirmed) return;
                els.deleteButton.disabled = true;
                $.ajax({
                    url: urlFor(urls.destroy, selectedEvent.id),
                    method: 'DELETE',
                    dataType: 'json'
                }).done(function () {
                    eventOffcanvas.hide();
                    calendar.refetchEvents();
                    notify('برنامه حذف شد.', 'success');
                }).fail(function (xhr) {
                    notify(requestErrorMessage(xhr), 'danger');
                }).always(function () {
                    els.deleteButton.disabled = false;
                });
            });
        }

        function closeMobileSidebar() {
            els.sidebar.classList.remove('show');
            if (els.overlay) els.overlay.classList.remove('show');
        }

        initSelect2();
        buildPickers();
        initInlineCalendar();
        els.todayLabel.textContent = formatJalaliDate(new Date(), {weekday: true, year: true});

        els.addSidebar.addEventListener('shown.bs.offcanvas', applyPendingDates);
        els.addSidebar.addEventListener('hidden.bs.offcanvas', function () {
            calendar.unselect();
            resetForm();
        });
        els.addButton.addEventListener('click', saveNewEvent);
        els.updateButton.addEventListener('click', updateSelectedEvent);
        els.deleteButton.addEventListener('click', deleteSelectedEvent);

        els.newButton.addEventListener('click', function () {
            resetForm();
            closeMobileSidebar();
        });

        els.allDay.addEventListener('change', function () {
            if (!els.allDay.checked && (!els.startTime.value || !els.endTime.value)) {
                const defaults = defaultTimesForDate(pickerDate(startPicker) || new Date());
                if (!els.startTime.value) els.startTime.value = defaults.start;
                if (!els.endTime.value) els.endTime.value = defaults.end;
            }
            updateTimeFieldsState();
        });

        els.durationButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const start = combineDateAndTime(pickerDate(startPicker), els.startTime.value, false);
                if (!start) {
                    notify('ابتدا تاریخ و ساعت شروع را وارد کنید.', 'warning');
                    return;
                }
                const end = new Date(start.getTime() + Number(button.dataset.duration) * 60000);
                setPickerDate(endPicker, end, true);
                els.endTime.value = timeValue(end);
            });
        });

        els.selectAll.addEventListener('change', function () {
            els.filters.forEach(function (filter) { filter.checked = els.selectAll.checked; });
            calendar.refetchEvents();
        });

        els.selectAllText.addEventListener('click', function () {
            els.selectAll.checked = true;
            els.filters.forEach(function (filter) { filter.checked = true; });
            calendar.refetchEvents();
        });

        els.filters.forEach(function (filter) {
            filter.addEventListener('change', function () {
                els.selectAll.checked = els.filters.every(function (item) { return item.checked; });
                calendar.refetchEvents();
            });
        });

        els.search.addEventListener('input', function () {
            window.clearTimeout(searchTimer);
            els.clearSearch.classList.toggle('d-none', !els.search.value);
            searchTimer = window.setTimeout(function () { calendar.refetchEvents(); }, 300);
        });

        els.clearSearch.addEventListener('click', function () {
            els.search.value = '';
            els.clearSearch.classList.add('d-none');
            els.search.focus();
            calendar.refetchEvents();
        });
    });
})();
