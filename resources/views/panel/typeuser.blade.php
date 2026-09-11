@extends('layouts.base')

@section('title', 'مدیریت نوع کاربران')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
@endsection

@php($resourceName = 'typeuser')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{ $thispage['list'] }}</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{ $thispage['add'] }}</button>
            </div>
            <div class="table-responsive">
                <table id="typeUserTable" class="table table-striped table-bordered yajra-datatable">
                    <thead><tr class="table-light"><th>عنوان فارسی</th><th>عنوان سیستمی</th><th>وضعیت</th><th>تغییرات</th></tr></thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ $thispage['add'] }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button></div>
            <div class="modal-body">
                <form id="addTypeUserForm" action="{{ route($resourceName.'.store') }}" method="POST">
                    @csrf
                    @include('panel.partials.typeuser-form', ['prefix' => 'add_type_'])
                    <div class="text-end mt-3"><button type="submit" class="btn btn-primary">ذخیره اطلاعات</button></div>
                </form>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">{{ $thispage['edit'] }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button></div>
            <div class="modal-body">
                <form id="editTypeUserForm" method="POST">
                    @csrf
                    @method('PATCH')
                    @include('panel.partials.typeuser-form', ['prefix' => 'edit_type_'])
                    <div class="text-end mt-3"><button type="submit" class="btn btn-primary">ذخیره تغییرات</button></div>
                </form>
            </div>
        </div></div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const resourceName = @json($resourceName);
            const baseUrl = @json(url('panel')).replace(/\/$/, '') + '/' + resourceName;
            const csrfToken = @json(csrf_token());
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const table = $('#typeUserTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: @json(route($resourceName.'.index')),
                columns: [
                    {data: 'title_fa', name: 'type_users.title_fa'},
                    {data: 'title', name: 'type_users.title'},
                    {data: 'status', name: 'type_users.status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
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
                    ['title_fa', 'title', 'status'].forEach(field => document.getElementById('edit_type_' + field).value = response.data[field] ?? '');
                    document.getElementById('editTypeUserForm').action = `${baseUrl}/${id}`;
                    editModal.show();
                }).fail(showError);
            });

            $('#addTypeUserForm, #editTypeUserForm').on('submit', function (event) {
                event.preventDefault();
                const form = this;
                $.ajax({
                    url: form.action,
                    method: form.id === 'editTypeUserForm' ? 'PATCH' : 'POST',
                    data: $(form).serialize(),
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                    if (form.id === 'addTypeUserForm') form.reset();
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message, 'success');
                }).fail(showError);
            });

            $(document).on('click', '.delete-btn', function () {
                const id = Number($(this).data('id'));
                Swal.fire({title: 'حذف', text: 'آیا از حذف این مورد مطمئن هستید؟', icon: 'warning', showCancelButton: true, confirmButtonText: 'حذف', cancelButtonText: 'انصراف'})
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({url: `${baseUrl}/${id}`, method: 'DELETE', data: {_token: csrfToken}})
                            .done(response => { table.ajax.reload(null, false); Swal.fire('موفق', response.message, 'success'); })
                            .fail(showError);
                    });
            });
        });
    </script>
@endsection
