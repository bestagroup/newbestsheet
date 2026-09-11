@extends('layouts.base')

@section('title', 'مدیریت دسترسی های داشبورد')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{ $thispage['list'] }}</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">{{ $thispage['add'] }}</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered text-center yajra-datatable" id="rolesTable">
                    <thead class="table-light">
                    <tr>
                        <th>تغییرات</th>
                        <th>عنوان فارسی</th>
                        <th>شناسه نقش</th>
                        <th>تعداد دسترسی‌ها</th>
                        <th>وضعیت</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $thispage['add'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoleForm" action="{{ route('useraccess.store') }}" method="POST">
                        @csrf
                        @include('panel.partials.role-basic-form', ['prefix' => 'add_access_'])
                        <div class="text-end mt-3"><button type="submit" class="btn btn-primary">ذخیره اطلاعات</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $thispage['edit'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="editRoleForm" method="POST">
                        @csrf
                        @method('PATCH')
                        @include('panel.partials.role-basic-form', ['prefix' => 'edit_access_'])
                        <div class="text-end mt-3"><button type="submit" class="btn btn-primary">ذخیره تغییرات</button></div>
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
            const baseUrl = @json(url('panel/useraccess'));
            const csrfToken = @json(csrf_token());
            const editModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
            const table = $('#rolesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: @json(route('useraccess.index')),
                columns: [
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                    {data: 'title_fa', name: 'roles.title_fa'},
                    {data: 'title', name: 'roles.title'},
                    {data: 'permissions_count', name: 'permissions_count', orderable: false, searchable: false},
                    {data: 'status', name: 'roles.status'}
                ],
                language: {url: @json(asset('assets/vendor/js/fa.json'))}
            });

            function showError(xhr) {
                const errors = xhr.responseJSON?.errors;
                Swal.fire('خطا', errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'عملیات ناموفق بود.'), 'error');
            }

            $(document).on('click', '.edit-btn', function () {
                const id = Number($(this).data('id'));
                $.get(`${baseUrl}/${id}/edit`).done(response => {
                    ['title_fa', 'title', 'status'].forEach(field => {
                        document.getElementById('edit_access_' + field).value = response.data[field] ?? '';
                    });
                    document.getElementById('editRoleForm').action = `${baseUrl}/${id}`;
                    editModal.show();
                }).fail(showError);
            });

            $('#addRoleForm, #editRoleForm').on('submit', function (event) {
                event.preventDefault();
                const form = this;
                $.ajax({
                    url: form.action,
                    method: form.id === 'editRoleForm' ? 'PATCH' : 'POST',
                    data: $(form).serialize(),
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                    if (form.id === 'addRoleForm') form.reset();
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message, 'success');
                }).fail(showError);
            });

            $(document).on('click', '.delete-btn', function () {
                const id = Number($(this).data('id'));
                Swal.fire({
                    title: 'حذف نقش', text: 'آیا از حذف این نقش اطمینان دارید؟', icon: 'warning',
                    showCancelButton: true, confirmButtonText: 'حذف', cancelButtonText: 'انصراف'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({url: `${baseUrl}/${id}`, method: 'DELETE', data: {_token: csrfToken}})
                        .done(response => {
                            table.ajax.reload(null, false);
                            Swal.fire('موفق', response.message, 'success');
                        }).fail(showError);
                });
            });
        });
    </script>
@endsection
