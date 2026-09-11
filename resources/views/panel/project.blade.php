@extends('layouts.base')

@section('title', 'مدیریت پروژه ها')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
    <style>
        table { margin: 0 auto; width: 100% !important; clear: both; border-collapse: collapse; table-layout: auto !important; white-space: nowrap; }
        .dt-layout-start { margin-right: 0 !important; }
        .dt-layout-end { margin-left: 0 !important; }
        .project-detail-label { color: var(--bs-secondary-color); font-size: .8rem; margin-bottom: .25rem; }
        .project-detail-value { font-weight: 600; min-height: 1.5rem; }
    </style>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{ $thispage['list'] }}</h5>
                @if (auth()->user()->can('can-access', ['project', 'insert']))
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        {{ $thispage['add'] }}
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table id="projectTable" class="table table-striped table-bordered yajra-datatable">
                    <thead>
                    <tr class="table-light">
                        <th>تغییرات</th>
                        <th>نام تجاری طرح</th>
                        <th>مدیرعامل شرکت</th>
                        <th>وضعیت پورتفو</th>
                        <th>مرحله فرایند</th>
                        <th>درصد پیشرفت</th>
                        <th>وضعیت فعالیت</th>
                        <th>تاریخ شروع قرارداد</th>
                        <th>کل مبلغ درخواستی</th>
                        <th>مجموع مبلغ واریزی</th>
                        <th>مبلغ تعهد اول</th>
                        <th>مبلغ تعهد دوم</th>
                        <th>مبلغ تعهد سوم</th>
                        <th>مبلغ تعهد چهارم</th>
                        <th>مبلغ تعهد پنجم</th>
                        <th>واریز قسط اول</th>
                        <th>واریز قسط دوم</th>
                        <th>واریز قسط سوم</th>
                        <th>واریز قسط چهارم</th>
                        <th>واریز قسط پنجم</th>
                        <th>مانده مبلغ تعهدات</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">{{ $thispage['add'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="addform" method="POST" action="{{ route('project.store') }}">
                        @csrf
                        @include('panel.partials.project-form', [
                            'prefix' => 'add_',
                            'companies' => $companies,
                            'states' => $states,
                            'showWorkflowState' => false,
                        ])
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">{{ $thispage['edit'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="editform" method="POST">
                        @csrf
                        @method('PATCH')
                        @include('panel.partials.project-form', [
                            'prefix' => 'edit_',
                            'companies' => $companies,
                            'states' => $states,
                            'showWorkflowState' => true,
                        ])
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="projectDetailsModal" tabindex="-1" aria-labelledby="projectDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectDetailsModalLabel">جزئیات پروژه</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#projectOverviewTab" type="button">اطلاعات طرح</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#projectPaymentsTab" type="button">پرداخت‌ها</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#projectWorkflowTab" type="button">تاریخچه فرایند</button></li>
                    </ul>
                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="projectOverviewTab">
                            <div class="row g-3" id="projectOverview"></div>
                        </div>
                        <div class="tab-pane fade" id="projectPaymentsTab">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead><tr><th>قسط</th><th>مبلغ</th><th>تاریخ</th><th>توضیحات</th></tr></thead>
                                    <tbody id="projectPaymentsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="projectWorkflowTab">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead><tr><th>مرحله</th><th>عنوان</th><th>وضعیت</th><th>توضیحات</th><th>زمان ثبت</th></tr></thead>
                                    <tbody id="projectWorkflowBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title w-100" id="deleteModalLabel">{{ $thispage['delete'] }}</h5>
                    <button type="button" class="btn-close position-absolute start-0 mx-3" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">آیا از حذف این پروژه مطمئن هستید؟</div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">حذف</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/dataTables.fixedColumns.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/fixedColumns.dataTables.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const baseUrl = @json(url('panel/project'));
            const citiesBaseUrl = @json(url('panel/getcities'));
            const csrfToken = @json(csrf_token());
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const detailsModal = new bootstrap.Modal(document.getElementById('projectDetailsModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            let deleteId = null;

            const table = $('#projectTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollCollapse: true,
                fixedColumns: {start: 2},
                ajax: @json(route('project.index')),
                columns: [
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                    {data: 'commercial_name', name: 'p.title'},
                    {data: 'CEO', name: 'p.CEO'},
                    {data: 'portfo_status', name: 'p.portfo_status'},
                    {data: 'flow_level', name: 'current_step.title'},
                    {data: 'progress_percentage', name: 'p.progress_percentage'},
                    {data: 'activity_status', name: 'p.activity_status'},
                    {data: 'start_date', name: 'p.start_date'},
                    {data: 'amount_request_accept', name: 'p.amount_request_accept'},
                    {data: 'amount_deposited', name: 'amount_deposited', orderable: false, searchable: false},
                    {data: 'amount_commitment_first_stage', name: 'p.amount_commitment_first_stage'},
                    {data: 'amount_commitment_second_stage', name: 'p.amount_commitment_second_stage'},
                    {data: 'amount_commitment_third_stage', name: 'p.amount_commitment_third_stage'},
                    {data: 'amount_commitment_fourth_stage', name: 'p.amount_commitment_fourth_stage'},
                    {data: 'amount_commitment_fifth_stage', name: 'p.amount_commitment_fifth_stage'},
                    {data: 'first_stage_payment', name: 'first_stage_payment', orderable: false, searchable: false},
                    {data: 'second_stage_payment', name: 'second_stage_payment', orderable: false, searchable: false},
                    {data: 'third_stage_payment', name: 'third_stage_payment', orderable: false, searchable: false},
                    {data: 'fourth_stage_payment', name: 'fourth_stage_payment', orderable: false, searchable: false},
                    {data: 'fifth_stage_payment', name: 'fifth_stage_payment', orderable: false, searchable: false},
                    {data: 'commitment_balance', name: 'commitment_balance', orderable: false, searchable: false}
                ],
                language: {url: @json(asset('assets/vendor/js/fa.json'))}
            });

            function showError(xhr) {
                const errors = xhr.responseJSON?.errors;
                const message = errors
                    ? Object.values(errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'عملیات با خطا مواجه شد.');
                Swal.fire('خطا', message, 'error');
            }

            function formatMoney(value) {
                if (value === null || value === undefined || value === '') {
                    return '';
                }
                const normalized = String(value).replace(/,/g, '');
                const number = Number(normalized);
                return Number.isFinite(number) ? number.toLocaleString('en-US') : value;
            }

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            async function loadCities(stateId, targetId, selectedCity = null) {
                const target = document.getElementById(targetId);
                target.innerHTML = '<option value="">انتخاب کنید</option>';

                if (!stateId) {
                    return;
                }

                const cities = await $.get(`${citiesBaseUrl}/${stateId}`);
                cities.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.title;
                    option.selected = Number(city.id) === Number(selectedCity);
                    target.appendChild(option);
                });
            }

            $('.project-state-select').on('change', function () {
                loadCities(this.value, this.dataset.cityTarget).catch(() => {
                    Swal.fire('خطا', 'دریافت فهرست شهرها انجام نشد.', 'error');
                });
            });

            function setField(prefix, name, value) {
                const element = document.getElementById(prefix + name);
                if (!element) {
                    return;
                }
                element.value = value ?? '';
            }

            async function populateEditForm(project) {
                const fields = [
                    'title', 'company_id', 'company_name', 'CEO', 'ceo_national_code', 'ceo_phone', 'registration_number',
                    'registration_date', 'national_id', 'economic_code', 'legal_type', 'tel', 'email', 'website', 'postal_code',
                    'state', 'portfo_status', 'activity_status', 'percentageshare', 'start_date', 'logo', 'address', 'description'
                ];
                fields.forEach(field => setField('edit_', field, project[field]));

                [
                    'amount_request_accept', 'amount_commitment_first_stage', 'amount_commitment_second_stage',
                    'amount_commitment_third_stage', 'amount_commitment_fourth_stage', 'amount_commitment_fifth_stage'
                ].forEach(field => setField('edit_', field, formatMoney(project[field])));

                setField('edit_', 'flow_level_display', project.flow_level || '');
                setField('edit_', 'progress_percentage_display', `${Number(project.progress_percentage || 0)}%`);
                await loadCities(project.state, 'edit_city', project.city);
            }

            $(document).on('click', '.edit-btn', function () {
                const id = Number($(this).data('id'));
                const form = document.getElementById('editform');

                $.get(`${baseUrl}/${id}/edit`)
                    .done(async response => {
                        await populateEditForm(response.data);
                        form.action = `${baseUrl}/${id}`;
                        editModal.show();
                    })
                    .fail(showError);
            });

            function detailCard(label, value) {
                return `<div class="col-md-4"><div class="border rounded p-3 h-100"><div class="project-detail-label">${escapeHtml(label)}</div><div class="project-detail-value">${escapeHtml(value || '-')}</div></div></div>`;
            }

            function renderProjectDetails(project) {
                const companyName = project.company?.company_name || project.company_name || '-';
                const details = [
                    ['نام طرح', project.title],
                    ['شرکت', companyName],
                    ['مدیرعامل', project.CEO],
                    ['شماره تماس مدیرعامل', project.ceo_phone],
                    ['وضعیت پورتفو', project.portfo_status],
                    ['مرحله جاری', project.flow_level],
                    ['درصد پیشرفت', `${Number(project.progress_percentage || 0)}%`],
                    ['وضعیت فعالیت', project.activity_status],
                    ['تاریخ شروع', project.start_date],
                    ['مبلغ درخواستی تأییدشده', formatMoney(project.amount_request_accept)],
                    ['درصد سهام دریافتی', project.percentageshare ? `${project.percentageshare}%` : '-'],
                    ['شناسه ملی', project.national_id],
                    ['شماره ثبت', project.registration_number],
                    ['تاریخ ثبت', project.registration_date],
                    ['کد اقتصادی', project.economic_code]
                ];
                document.getElementById('projectOverview').innerHTML = details.map(item => detailCard(item[0], item[1])).join('');

                const payments = project.finances || [];
                document.getElementById('projectPaymentsBody').innerHTML = payments.length
                    ? payments.map(payment => `<tr><td>${escapeHtml(payment.serial ?? '-')}</td><td>${escapeHtml(formatMoney(payment.amount))}</td><td>${escapeHtml(payment.date || '-')}</td><td>${escapeHtml(payment.description || '-')}</td></tr>`).join('')
                    : '<tr><td colspan="4" class="text-center text-muted">پرداختی ثبت نشده است.</td></tr>';

                const steps = project.project_steps || [];
                document.getElementById('projectWorkflowBody').innerHTML = steps.length
                    ? steps.map(step => `<tr><td>${escapeHtml(step.step_number)}</td><td>${escapeHtml(step.title)}</td><td>${escapeHtml(step.status)}</td><td>${escapeHtml(step.description || '-')}</td><td>${escapeHtml(step.created_at || '-')}</td></tr>`).join('')
                    : '<tr><td colspan="5" class="text-center text-muted">سابقه‌ای ثبت نشده است.</td></tr>';
            }

            $(document).on('click', '.show-btn', function () {
                const id = Number($(this).data('id'));
                $.get(`${baseUrl}/${id}`)
                    .done(response => {
                        renderProjectDetails(response.data);
                        detailsModal.show();
                    })
                    .fail(showError);
            });

            $('#addform, #editform').on('submit', function (event) {
                event.preventDefault();
                const form = this;
                const submitButton = form.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> در حال ذخیره...';

                $.ajax({
                    url: form.action,
                    method: form.id === 'editform' ? 'PATCH' : 'POST',
                    data: $(form).serialize(),
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    if (!response.success) {
                        Swal.fire(response.subject || 'خطا', response.message || 'عملیات انجام نشد.', 'error');
                        return;
                    }
                    bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                    if (form.id === 'addform') {
                        form.reset();
                        document.getElementById('add_city').innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
                    }
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message, 'success');
                }).fail(showError).always(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                });
            });

            $(document).on('input', '.money-input', function () {
                const digits = this.value.replace(/[^0-9۰-۹٠-٩]/g, '');
                const latin = digits
                    .replace(/[۰-۹]/g, digit => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
                    .replace(/[٠-٩]/g, digit => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
                this.value = latin ? Number(latin).toLocaleString('en-US') : '';
            });

            $(document).on('click', '.delete-btn', function () {
                deleteId = Number($(this).data('id'));
                deleteModal.show();
            });

            $('#confirmDelete').on('click', function () {
                if (!deleteId) {
                    return;
                }
                const button = this;
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> در حال حذف...';

                $.ajax({
                    url: `${baseUrl}/${deleteId}`,
                    method: 'DELETE',
                    data: {_token: csrfToken, id: deleteId}
                }).done(response => {
                    deleteModal.hide();
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message || 'پروژه حذف شد.', 'success');
                }).fail(showError).always(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        });
    </script>
@endsection
