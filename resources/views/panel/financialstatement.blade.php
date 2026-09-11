@extends('layouts.base')

@section('title', $thispage['title'])

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .financial-page-heading {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .financial-page-heading__icon {
            width: 3rem;
            height: 3rem;
            flex: 0 0 3rem;
            display: grid;
            place-items: center;
            border-radius: .85rem;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .12);
            font-size: 1.5rem;
        }

        .statement-filter-bar {
            padding: 1rem;
            border: 1px solid rgba(var(--bs-secondary-rgb), .18);
            border-radius: .85rem;
            background: rgba(var(--bs-secondary-rgb), .035);
        }

        .statement-table-wrap {
            border: 1px solid rgba(var(--bs-secondary-rgb), .16);
            border-radius: .85rem;
            overflow: hidden;
        }

        #financialstatement {
            margin: 0 !important;
            width: 100% !important;
        }

        #financialstatement thead th {
            white-space: nowrap;
            font-size: .78rem;
            color: var(--bs-secondary-color);
            background: rgba(var(--bs-secondary-rgb), .065);
            border-bottom-width: 1px;
        }

        #financialstatement tbody td {
            vertical-align: middle;
            white-space: nowrap;
            padding-block: .8rem;
        }

        .statement-company {
            display: flex;
            align-items: center;
            gap: .7rem;
            min-width: 12rem;
        }

        .statement-company__avatar {
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 2.25rem;
            display: grid;
            place-items: center;
            border-radius: .7rem;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .1);
            font-weight: 700;
        }

        .statement-company__project {
            display: block;
            max-width: 13rem;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--bs-secondary-color);
            font-size: .72rem;
        }

        .statement-period {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .35rem .6rem;
            border-radius: .55rem;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .1);
            font-weight: 600;
            direction: ltr;
        }

        .statement-amount {
            display: inline-block;
            min-width: 5rem;
            direction: ltr;
            text-align: left;
            font-variant-numeric: tabular-nums;
        }

        .statement-form-section {
            padding: 1rem;
            border: 1px solid rgba(var(--bs-secondary-rgb), .16);
            border-radius: .85rem;
        }

        .statement-form-section__title {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1rem;
            color: var(--bs-heading-color);
            font-weight: 700;
        }

        .statement-form-section__title i {
            color: var(--bs-primary);
            font-size: 1.25rem;
        }

        .statement-form-actions {
            position: sticky;
            bottom: -.5rem;
            z-index: 2;
            padding-block: .85rem .35rem;
            border-top: 1px solid rgba(var(--bs-secondary-rgb), .14);
            background: var(--bs-body-bg);
        }

        .statement-detail-group {
            border: 1px solid rgba(var(--bs-secondary-rgb), .16);
            border-radius: .85rem;
            overflow: hidden;
        }

        .statement-detail-group__title {
            margin: 0;
            padding: .8rem 1rem;
            color: var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), .07);
            font-size: .9rem;
            font-weight: 700;
        }

        .statement-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: .7rem 1rem;
            border-top: 1px solid rgba(var(--bs-secondary-rgb), .1);
        }

        .statement-detail-item:first-child {
            border-top: 0;
        }

        .statement-detail-item strong {
            direction: ltr;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .dt-container .dt-search input,
        .dt-container .dt-length select {
            border-radius: .55rem;
        }

        @media (max-width: 1199.98px) {
            #financialstatement .statement-col-wide {
                display: none;
            }
        }

        @media (max-width: 767.98px) {
            #financialstatement .statement-col-medium {
                display: none;
            }

            .financial-page-heading__icon {
                display: none;
            }

            .statement-company {
                min-width: 9rem;
            }

            .statement-company__project {
                max-width: 9rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div class="financial-page-heading">
                    <span class="financial-page-heading__icon"><i class="mdi mdi-finance"></i></span>
                    <div>
                        <h5 class="card-title mb-1">{{ $thispage['list'] }}</h5>
                        <p class="text-muted mb-0">مرور دوره‌ای عملکرد مالی شرکت‌های واردشده به پورتفو</p>
                    </div>
                </div>
                @can('can-access', ['financialstatement', 'insert'])
                    <button type="button" class="btn btn-primary align-self-start" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="mdi mdi-plus me-1"></i>{{ $thispage['add'] }}
                    </button>
                @endcan
            </div>

            <div class="statement-filter-bar mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="statementProjectFilter">شرکت پورتفو</label>
                        <select id="statementProjectFilter" class="form-select">
                            <option value="">همه شرکت‌ها</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->company_name ?: $project->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-8 col-md-3">
                        <label class="form-label" for="statementYearFilter">سال مالی</label>
                        <select id="statementYearFilter" class="form-select">
                            <option value="">همه سال‌ها</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-md-2">
                        <button type="button" class="btn btn-label-secondary w-100" id="clearStatementFilters">
                            <i class="mdi mdi-filter-remove-outline me-1"></i>پاک‌کردن
                        </button>
                    </div>
                    <div class="col-12 col-md-2 text-md-end">
                        <small class="text-muted"><i class="mdi mdi-information-outline me-1"></i>تمام ارقام به ریال</small>
                    </div>
                </div>
            </div>

            <div class="statement-table-wrap">
                <div class="table-responsive">
                    <table id="financialstatement" class="table table-hover yajra-datatable">
                        <thead>
                        <tr>
                            <th>شرکت</th>
                            <th>دوره مالی</th>
                            <th class="statement-col-medium">فروش خالص</th>
                            <th class="statement-col-wide">سود ناخالص</th>
                            <th class="statement-col-wide">سود / زیان عملیاتی</th>
                            <th>سود / زیان خالص</th>
                            <th class="statement-col-medium">جمع دارایی‌ها</th>
                            <th class="statement-col-wide">جمع بدهی‌ها</th>
                            <th class="statement-col-wide">حقوق مالکانه</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="detailsModalLabel">جزئیات صورت مالی</h5>
                        <small class="text-muted" id="detailsModalPeriod"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body" id="detailsModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">بستن</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="deleteModalLabel">{{ $thispage['delete'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <span class="d-inline-grid rounded-circle bg-label-danger p-3 mb-3">
                        <i class="mdi mdi-delete-alert-outline mdi-36px"></i>
                    </span>
                    <h6 id="deleteStatementName">این صورت مالی حذف شود؟</h6>
                    <p class="text-muted mb-0">این عملیات قابل بازگشت نیست و رکورد دوره انتخاب‌شده را برای همیشه حذف می‌کند.</p>
                </div>
                <div class="modal-footer justify-content-center border-top-0">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="mdi mdi-delete-outline me-1"></i>حذف قطعی
                    </button>
                </div>
            </div>
        </div>
    </div>

    @can('can-access', ['financialstatement', 'insert'])
        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addModalLabel">{{ $thispage['add'] }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addform" data-type="create" data-table-target="#financialstatement"
                              method="POST" class="row g-4" action="{{ route('financialstatement.store') }}">
                            @csrf
                            @include('panel.partials.financial-statement-fields', [
                                'statement' => null,
                                'idPrefix' => 'add_statement',
                            ])
                            <div class="col-12 d-flex justify-content-end gap-2 statement-form-actions">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="mdi mdi-content-save-outline me-1"></i>ثبت صورت مالی
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">{{ $thispage['edit'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body" id="editModalBody">
                    <div class="text-center text-muted py-5">
                        <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>در حال بارگذاری...
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    <script>
        $(function () {
            const indexUrl = @json(route('financialstatement.index'));
            const csrfToken = @json(csrf_token());
            const fieldGroups = @json($fieldGroups);
            const tableElement = document.getElementById('financialstatement');
            const editModalElement = document.getElementById('editModal');
            const deleteModalElement = document.getElementById('deleteModal');
            const detailsModalElement = document.getElementById('detailsModal');
            const editModal = bootstrap.Modal.getOrCreateInstance(editModalElement);
            const deleteModal = bootstrap.Modal.getOrCreateInstance(deleteModalElement);
            const detailsModal = bootstrap.Modal.getOrCreateInstance(detailsModalElement);
            let pendingDelete = null;

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            function latinDigits(value) {
                const persian = '۰۱۲۳۴۵۶۷۸۹';
                const arabic = '٠١٢٣٤٥٦٧٨٩';

                return String(value ?? '')
                    .replace(/[۰-۹]/g, digit => persian.indexOf(digit))
                    .replace(/[٠-٩]/g, digit => arabic.indexOf(digit));
            }

            function normalizedAmount(value) {
                if (value === null || value === undefined || String(value).trim() === '') {
                    return null;
                }

                const normalized = latinDigits(value).replace(/[٬،,\s]/g, '').replace('٫', '.');

                return /^-?\d+(?:\.\d+)?$/.test(normalized) ? normalized : null;
            }

            function amountText(value) {
                const amount = normalizedAmount(value);
                if (amount === null) return '—';

                const negative = amount.startsWith('-');
                const unsigned = negative ? amount.slice(1) : amount;
                const [integer, fraction] = unsigned.split('.');
                const grouped = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                return `${negative ? '-' : ''}${grouped}${fraction !== undefined ? `.${fraction}` : ''}`;
            }

            function renderAmount(value, type) {
                const amount = normalizedAmount(value);

                if (type === 'sort' || type === 'type') {
                    return amount ?? '';
                }

                if (amount === null) {
                    return '<span class="text-muted">—</span>';
                }

                const isNegative = amount.startsWith('-');
                const isZero = /^-?0+(?:\.0+)?$/.test(amount);
                const tone = isNegative ? 'text-danger' : (isZero ? 'text-muted' : 'text-body');
                return `<span class="statement-amount ${tone}">${escapeHtml(amountText(amount))}</span>`;
            }

            function notify(message, type = 'success') {
                if (window.toastr) {
                    toastr.options = {
                        closeButton: true,
                        progressBar: true,
                        positionClass: 'toast-top-right',
                        timeOut: 4000,
                        rtl: true
                    };
                    toastr[type === 'error' ? 'error' : 'success'](message);
                    return;
                }

                if (window.Swal) {
                    Swal.fire(type === 'error' ? 'خطا' : 'عملیات موفق', message, type);
                    return;
                }

                window.alert(message);
            }

            function responseError(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    const firstError = Object.values(errors).flat()[0];
                    if (firstError) return firstError;
                }

                return xhr.responseJSON?.message || 'عملیات انجام نشد. لطفاً دوباره تلاش کنید.';
            }

            function markInvalidFields($form, xhr) {
                $form.find('.is-invalid').removeClass('is-invalid');
                const errors = xhr.responseJSON?.errors || {};
                Object.keys(errors).forEach(field => {
                    const control = $form[0]?.elements?.namedItem(field);
                    if (control) $(control).addClass('is-invalid');
                });
            }

            const table = $(tableElement).DataTable({
                processing: true,
                serverSide: true,
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50],
                order: [[1, 'desc'], [0, 'asc']],
                ajax: {
                    url: indexUrl,
                    data: function (request) {
                        request.project_id = $('#statementProjectFilter').val();
                        request.year = $('#statementYearFilter').val();
                    }
                },
                columns: [
                    {
                        data: 'company_name',
                        name: 'company_name',
                        render: function (value, type, row) {
                            if (type !== 'display') return value;
                            const company = String(value || 'بدون نام');
                            const project = row.project_title && row.project_title !== company ? row.project_title : '';
                            const initial = company.trim().charAt(0) || 'ش';

                            return `<div class="statement-company">
                                <span class="statement-company__avatar">${escapeHtml(initial)}</span>
                                <span><strong>${escapeHtml(company)}</strong>${project ? `<small class="statement-company__project" title="${escapeHtml(project)}">${escapeHtml(project)}</small>` : ''}</span>
                            </div>`;
                        }
                    },
                    {
                        data: 'period',
                        name: 'period_sort',
                        searchable: false,
                        render: function (value, type) {
                            if (type !== 'display') return value;
                            return `<span class="statement-period"><i class="mdi mdi-calendar-month-outline"></i>${escapeHtml(value)}</span>`;
                        }
                    },
                    {data: 'net_sales', name: 'f.net_sales', orderable: false, searchable: false, className: 'statement-col-medium', render: renderAmount},
                    {data: 'gross_profit', name: 'f.gross_profit', orderable: false, searchable: false, className: 'statement-col-wide', render: renderAmount},
                    {data: 'operating_loss', name: 'f.operating_loss', orderable: false, searchable: false, className: 'statement-col-wide', render: renderAmount},
                    {data: 'net_profit', name: 'f.net_profit', orderable: false, searchable: false, render: renderAmount},
                    {data: 'total_assets', name: 'f.total_assets', orderable: false, searchable: false, className: 'statement-col-medium', render: renderAmount},
                    {data: 'total_liabilities', name: 'f.total_liabilities', orderable: false, searchable: false, className: 'statement-col-wide', render: renderAmount},
                    {data: 'total_equity', name: 'f.total_equity', orderable: false, searchable: false, className: 'statement-col-wide', render: renderAmount},
                    {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
                ],
                language: {
                    url: @json(asset('assets/vendor/js/fa.json'))
                }
            });

            $('#statementProjectFilter, #statementYearFilter').on('change', function () {
                table.ajax.reload();
            });

            $('#clearStatementFilters').on('click', function () {
                $('#statementProjectFilter, #statementYearFilter').val('');
                table.search('');
                table.ajax.reload();
            });

            $(document).on('click', '.statement-view-btn', function () {
                const row = table.row($(this).closest('tr')).data();
                if (!row) return;

                $('#detailsModalLabel').text(`صورت مالی ${row.company_name || row.project_title || ''}`);
                $('#detailsModalPeriod').text(`دوره مالی ${row.period} — تمام ارقام به ریال`);

                const groupsHtml = Object.entries(fieldGroups).map(([groupTitle, fields]) => {
                    const items = Object.entries(fields).map(([field, label]) => {
                        const amount = normalizedAmount(row[field]);
                        const tone = amount?.startsWith('-') ? 'text-danger' : '';
                        return `<div class="statement-detail-item">
                            <span class="text-muted">${escapeHtml(label)}</span>
                            <strong class="${tone}">${escapeHtml(amountText(row[field]))}</strong>
                        </div>`;
                    }).join('');

                    return `<div class="col-12 col-lg-4">
                        <section class="statement-detail-group h-100">
                            <h6 class="statement-detail-group__title">${escapeHtml(groupTitle)}</h6>
                            <div>${items}</div>
                        </section>
                    </div>`;
                }).join('');

                $('#detailsModalBody').html(`<div class="row g-3">${groupsHtml}</div>`);
                detailsModal.show();
            });

            $(document).on('click', '.edit-btn', function () {
                const url = $(this).data('url');
                $('#editModalBody').html('<div class="text-center text-muted py-5"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>در حال بارگذاری...</div>');
                editModal.show();

                $.get(url)
                    .done(html => $('#editModalBody').html(html))
                    .fail(xhr => $('#editModalBody').html(`<div class="alert alert-danger m-0">${escapeHtml(responseError(xhr))}</div>`));
            });

            $(document).on('click', '.delete-btn', function () {
                const row = table.row($(this).closest('tr')).data();
                pendingDelete = {
                    url: $(this).data('url'),
                    company: row?.company_name || row?.project_title || '',
                    period: row?.period || ''
                };
                $('#deleteStatementName').text(`صورت مالی ${pendingDelete.company} در دوره ${pendingDelete.period} حذف شود؟`);
                deleteModal.show();
            });

            $('#confirmDelete').on('click', function () {
                if (!pendingDelete?.url) return;

                const button = this;
                const originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>در حال حذف...';

                $.ajax({
                    url: pendingDelete.url,
                    method: 'DELETE',
                    data: {_token: csrfToken},
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    deleteModal.hide();
                    pendingDelete = null;
                    table.ajax.reload(null, false);
                    notify(response.message || 'صورت مالی با موفقیت حذف شد.');
                }).fail(xhr => {
                    notify(responseError(xhr), 'error');
                }).always(() => {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                });
            });

            $(document).on('submit', '#addform, form[data-type="update"]', function (event) {
                event.preventDefault();
                const $form = $(this);
                const $button = $form.find('button[type="submit"]').first();
                const originalHtml = $button.html();
                const isCreate = $form.data('type') === 'create';

                $form.find('.is-invalid').removeClass('is-invalid');
                $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>در حال ذخیره...');

                $.ajax({
                    url: $form.attr('action'),
                    method: 'POST',
                    data: $form.serialize(),
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    const modalElement = $form.closest('.modal')[0];
                    bootstrap.Modal.getInstance(modalElement)?.hide();
                    if (isCreate) this.reset();
                    table.ajax.reload(null, false);
                    notify(response.message || 'اطلاعات با موفقیت ذخیره شد.');
                }).fail(xhr => {
                    markInvalidFields($form, xhr);
                    notify(responseError(xhr), 'error');
                }).always(() => {
                    $button.prop('disabled', false).html(originalHtml);
                });
            });

            $(document).on('input change', '#addform .form-control, #addform .form-select, form[data-type="update"] .form-control, form[data-type="update"] .form-select', function () {
                $(this).removeClass('is-invalid');
            });

            $(document).on('blur', 'input.number-input', function () {
                const amount = normalizedAmount(this.value);
                if (amount !== null) {
                    this.value = amountText(amount);
                }
            });

            editModalElement.addEventListener('hidden.bs.modal', function () {
                $('#editModalBody').html('<div class="text-center text-muted py-5">در حال بارگذاری...</div>');
            });

            deleteModalElement.addEventListener('hidden.bs.modal', function () {
                pendingDelete = null;
            });
        });
    </script>
@endsection
