(function (window, document, $) {
    'use strict';

    if (window.FormEnhancements) {
        return;
    }

    const SELECT_IGNORE_SELECTOR = [
        '[data-select2-ignore]',
        '[data-select2-manual]',
        '.select2-hidden-accessible',
        '.jdp-container select',
        '.flatpickr-calendar select'
    ].join(', ');

    const DATE_FIELD_SELECTOR = [
        'input[data-jdp]',
        'input[data-datepicker]',
        'input[type="date"]',
        'input[name="date"]',
        'input[name$="_date"]',
        'input[name$="[date]"]',
        'input[name="birthday"]',
        'input[name$="[birthday]"]',
        'input[name="deadline"]',
        'input[name$="[deadline]"]',
        'input[name="date_sabt"]'
    ].join(', ');

    const faLanguage = {
        errorLoading: function () { return 'خطا در بارگذاری نتایج'; },
        inputTooLong: function (args) { return 'حداکثر ' + args.maximum + ' نویسه مجاز است'; },
        inputTooShort: function (args) { return 'حداقل ' + (args.minimum - args.input.length) + ' نویسه دیگر وارد کنید'; },
        loadingMore: function () { return 'در حال بارگذاری موارد بیشتر…'; },
        maximumSelected: function (args) { return 'حداکثر ' + args.maximum + ' مورد قابل انتخاب است'; },
        noResults: function () { return 'نتیجه‌ای یافت نشد'; },
        searching: function () { return 'در حال جستجو…'; },
        removeAllItems: function () { return 'حذف همه موارد'; }
    };

    let datepickerStarted = false;
    let observer = null;
    let scheduledRoot = null;
    let frameId = null;

    function isElement(value) {
        return value && value.nodeType === Node.ELEMENT_NODE;
    }

    function collect(root, selector) {
        const elements = [];

        if (isElement(root) && root.matches(selector)) {
            elements.push(root);
        }

        if (root && typeof root.querySelectorAll === 'function') {
            elements.push.apply(elements, root.querySelectorAll(selector));
        }

        return elements;
    }

    function prepareDateFields(root) {
        collect(root, DATE_FIELD_SELECTOR).forEach(function (input) {
            if (input.matches('[data-datepicker-ignore], .flatpickr-input') || input._flatpickr) {
                return;
            }

            if (input.type === 'date') {
                input.dataset.originalInputType = 'date';
                input.type = 'text';
            }

            input.setAttribute('data-jdp', '');
            input.setAttribute('autocomplete', 'off');
        });
    }

    function selectPlaceholder($select) {
        const explicit = $select.attr('data-placeholder');
        if (explicit) {
            return explicit;
        }

        const firstOption = $select.find('option').first();
        return firstOption.length && firstOption.val() === ''
            ? (firstOption.text().trim() || 'انتخاب کنید')
            : null;
    }

    function initSelect(select) {
        if (!$ || !$.fn || !$.fn.select2 || select.matches(SELECT_IGNORE_SELECTOR)) {
            return;
        }

        if (select.closest('.jdp-container, .flatpickr-calendar')) {
            return;
        }

        const $select = $(select);
        if ($select.data('select2')) {
            $select.trigger('change.select2');
            return;
        }

        const $overlay = $select.closest('.modal, .offcanvas');
        const placeholder = selectPlaceholder($select);
        const options = {
            width: '100%',
            dir: document.documentElement.dir || 'rtl',
            language: faLanguage,
            dropdownParent: $overlay.length ? $overlay : $(document.body)
        };

        if (placeholder) {
            options.placeholder = placeholder;
            options.allowClear = !select.required && !select.multiple;
        }

        const minimumResults = Number.parseInt($select.attr('data-minimum-results-for-search'), 10);
        if (!Number.isNaN(minimumResults)) {
            options.minimumResultsForSearch = minimumResults;
        }

        $select.select2(options);
    }

    function initSelects(root) {
        collect(root, 'select').forEach(initSelect);
    }

    function init(root) {
        let context = root || document;
        if (isElement(context) && context.matches('option, optgroup')) {
            context = context.closest('select') || context;
        }
        prepareDateFields(context);
        initSelects(context);
    }

    function startDatepicker() {
        if (datepickerStarted || !window.jalaliDatepicker) {
            return;
        }

        window.jalaliDatepicker.startWatch({
            selector: 'input[data-jdp]:not([data-datepicker-ignore])',
            autoHide: true,
            hideAfterChange: true,
            useDropDownYears: true,
            persianDigits: false,
            zIndex: 2056
        });
        datepickerStarted = true;
    }

    function scheduleInit(root) {
        const nextRoot = root || document;
        if (!scheduledRoot) {
            scheduledRoot = nextRoot;
        } else if (scheduledRoot !== document && scheduledRoot !== nextRoot) {
            const rootsOverlap = isElement(scheduledRoot)
                && isElement(nextRoot)
                && (scheduledRoot.contains(nextRoot) || nextRoot.contains(scheduledRoot));
            scheduledRoot = rootsOverlap
                ? (scheduledRoot.contains(nextRoot) ? scheduledRoot : nextRoot)
                : document;
        }
        if (frameId !== null) {
            return;
        }

        const schedule = window.requestAnimationFrame || function (callback) {
            return window.setTimeout(callback, 0);
        };

        frameId = schedule(function () {
            const context = scheduledRoot || document;
            scheduledRoot = null;
            frameId = null;
            init(context.isConnected === false ? document : context);
        });
    }

    function observeDynamicForms() {
        if (!window.MutationObserver || observer || !document.body) {
            return;
        }

        observer = new MutationObserver(function (mutations) {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (isElement(node)) {
                        scheduleInit(node);
                        return;
                    }
                }
            }
        });

        observer.observe(document.body, {childList: true, subtree: true});
    }

    function boot() {
        init(document);
        startDatepicker();
        observeDynamicForms();

        document.addEventListener('shown.bs.modal', function (event) {
            init(event.target);
        });
        document.addEventListener('shown.bs.offcanvas', function (event) {
            init(event.target);
        });
        document.addEventListener('reset', function (event) {
            window.setTimeout(function () {
                if ($) {
                    $(event.target).find('select.select2-hidden-accessible').trigger('change.select2');
                }
            }, 0);
        }, true);
    }

    window.FormEnhancements = {
        init: init,
        version: '1.0.0'
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, {once: true});
    } else {
        boot();
    }
})(window, document, window.jQuery);
