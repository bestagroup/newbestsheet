@extends('layouts.base')

@section('title', 'مدیریت شرکت ها')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
    <style>
        table { margin: 0 auto; width: 100% !important; clear: both; border-collapse: collapse; table-layout: auto !important; white-space: nowrap; }
        .dt-layout-start { margin-right: 0 !important; }
        .dt-layout-end { margin-left: 0 !important; }
    </style>
@endsection

@php($companyRoutePrefix = request()->routeIs('panel.company.*') ? 'panel.company' : 'company')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{ $thispage['list'] }}</h5>
                @if (auth()->user()->can('can-access', ['company', 'insert']))
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        {{ $thispage['add'] }}
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table id="companyTable" class="table table-striped table-bordered yajra-datatable">
                    <thead>
                    <tr class="table-light">
                        <th>تغییرات</th>
                        <th>نام تجاری شرکت</th>
                        <th>نام شرکت</th>
                        <th>نام مدیرعامل / نماینده</th>
                        <th>شماره ثبت</th>
                        <th>شناسه ملی</th>
                        <th>تاریخ ثبت</th>
                        <th>کد اقتصادی</th>
                        <th>نوع شرکت</th>
                        <th>وب‌سایت</th>
                    </tr>
                    </thead>
                </table>
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
                <div class="modal-body">آیا از حذف این شرکت مطمئن هستید؟</div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">حذف</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">{{ $thispage['add'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="addform" method="POST" action="{{ route($companyRoutePrefix.'.store') }}">
                        @csrf
                        @include('panel.partials.company-form', ['prefix' => 'add_', 'users' => $users, 'showUserSelector' => auth()->user()->level === 'admin'])
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">{{ $thispage['edit'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="editform" method="POST">
                        @csrf
                        @method('PATCH')
                        @include('panel.partials.company-form', ['prefix' => 'edit_', 'users' => $users, 'showUserSelector' => auth()->user()->level === 'admin'])
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const baseUrl = @json(request()->routeIs('panel.company.*') ? url('panel/company') : url('company'));
            const csrfToken = @json(csrf_token());
            const editModalElement = document.getElementById('editModal');
            const deleteModalElement = document.getElementById('deleteModal');
            const editModal = new bootstrap.Modal(editModalElement);
            const deleteModal = new bootstrap.Modal(deleteModalElement);
            let deleteId = null;

            const table = $('#companyTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                ajax: @json(route($companyRoutePrefix.'.index')),
                columns: [
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                    {data: 'commercial_name', name: 'companies.commercial_name'},
                    {data: 'company_name', name: 'companies.company_name'},
                    {data: 'ceo_name', name: 'companies.ceo_name'},
                    {data: 'registration_number', name: 'companies.registration_number'},
                    {data: 'national_id', name: 'companies.national_id'},
                    {data: 'registration_date', name: 'companies.registration_date'},
                    {data: 'economic_code', name: 'companies.economic_code'},
                    {data: 'legal_type', name: 'companies.legal_type'},
                    {data: 'website', name: 'companies.website'}
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

            function setField(prefix, name, value) {
                const element = document.getElementById(prefix + name);
                if (element) {
                    element.value = value ?? '';
                }
            }

            function populateEditForm(company) {
                [
                    'company_name', 'commercial_name', 'ceo_name', 'registration_number', 'registration_date',
                    'national_id', 'economic_code', 'legal_type', 'phone', 'email', 'website', 'province',
                    'city', 'address', 'postal_code', 'ceo_national_code', 'user_id'
                ].forEach(field => setField('edit_', field, company[field]));
            }

            $(document).on('click', '.edit-btn', function () {
                const id = Number($(this).data('id'));
                const form = document.getElementById('editform');

                $.get(`${baseUrl}/${id}/edit`)
                    .done(response => {
                        populateEditForm(response.data);
                        form.action = `${baseUrl}/${id}`;
                        editModal.show();
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
                    }
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message, 'success');
                }).fail(showError).always(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                });
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
                    Swal.fire('موفق', response.message || 'شرکت حذف شد.', 'success');
                }).fail(showError).always(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        });
    </script>
@endsection
