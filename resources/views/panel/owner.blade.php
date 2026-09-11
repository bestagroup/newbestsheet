@extends('layouts.base')

@section('title', 'مدیریت اطلاعات کارفرما')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
    <style>
        table { margin: 0 auto; width: 100% !important; clear: both; border-collapse: collapse; table-layout: auto; word-wrap: break-word; }
        .dt-layout-start { margin-right: 0 !important; }
        .dt-layout-end { margin-left: 0 !important; }
    </style>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{ $thispage['list'] }}</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{ $thispage['add'] }}</button>
            </div>

            <div class="table-responsive">
                <table id="sample1" class="table table-striped table-bordered yajra-datatable">
                    <thead>
                    <tr class="table-light">
                        <th>عنوان شرکت</th>
                        <th>تلفن</th>
                        <th>موبایل</th>
                        <th>ایمیل</th>
                        <th>مدیرعامل</th>
                        <th>شناسه ملی</th>
                        <th>کد اقتصادی</th>
                        <th>تاریخ ثبت</th>
                        <th>شبکه اجتماعی</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title w-100">{{ $thispage['delete'] }}</h5>
                    <button type="button" class="btn-close position-absolute start-0 mx-3" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">آیا از حذف این اطلاعات مطمئن هستید؟</div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">حذف</button>
                </div>
            </div>
        </div>
    </div>

    @foreach(['add' => 'addModal', 'edit' => 'editModal'] as $mode => $modalId)
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $mode === 'add' ? $thispage['add'] : $thispage['edit'] }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                        <form id="{{ $mode }}form" method="POST" @if($mode === 'add') action="{{ route('owner.store') }}" @endif>
                            @csrf
                            @if($mode === 'edit') @method('PATCH') @endif
                            @include('panel.partials.owner-form', ['prefix' => $mode.'_'])
                            <div class="text-end mt-3">
                                <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('script')
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    <script>
        $(function () {
            const baseUrl = @json(url('panel/owner'));
            const csrfToken = @json(csrf_token());
            const table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: @json(route('owner.index')),
                columns: [
                    {data: 'title', name: 'owners.title'},
                    {data: 'tel', name: 'owners.tel'},
                    {data: 'mobile', name: 'owners.mobile'},
                    {data: 'email', name: 'owners.email'},
                    {data: 'ceo', name: 'owners.ceo'},
                    {data: 'meli_code', name: 'owners.meli_code'},
                    {data: 'eghtesadi_code', name: 'owners.eghtesadi_code'},
                    {data: 'date_sabt', name: 'owners.date_sabt'},
                    {data: 'social', name: 'owners.social'},
                    {data: 'summery', name: 'owners.summery'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {url: @json(asset('assets/vendor/js/fa.json'))}
            });

            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            let deleteId = null;

            function showError(xhr) {
                Swal.fire('خطا', xhr.responseJSON?.message || 'عملیات انجام نشد.', 'error');
            }

            function setLoading(button, loading, text) {
                if (loading) {
                    button.dataset.originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = `<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ${text}`;
                } else {
                    button.disabled = false;
                    button.innerHTML = button.dataset.originalHtml || button.innerHTML;
                }
            }

            function socialForInput(value) {
                if (!value) return '';
                try {
                    const parsed = JSON.parse(value);
                    return Array.isArray(parsed) ? parsed.join(', ') : value;
                } catch (_) {
                    return value;
                }
            }

            function populate(owner) {
                const fields = ['title', 'tel', 'mobile', 'email', 'ceo', 'meli_code', 'eghtesadi_code', 'date_sabt', 'address', 'summery'];
                fields.forEach(field => {
                    const element = document.getElementById('edit_' + field);
                    if (element) element.value = owner[field] ?? '';
                });
                document.getElementById('edit_social').value = socialForInput(owner.social);
            }

            $(document).on('click', '.edit-btn', function () {
                const id = Number($(this).data('id'));
                $.get(`${baseUrl}/${id}/edit`).done(response => {
                    populate(response.data);
                    document.getElementById('editform').dataset.id = id;
                    editModal.show();
                }).fail(showError);
            });

            $(document).on('click', '.delete-btn', function () {
                deleteId = Number($(this).data('id'));
                deleteModal.show();
            });

            $('#addform, #editform').on('submit', function (event) {
                event.preventDefault();
                const form = this;
                const isEdit = form.id === 'editform';
                const id = isEdit ? Number(form.dataset.id) : null;
                const button = form.querySelector('button[type="submit"]');
                setLoading(button, true, 'در حال ذخیره...');

                $.ajax({
                    url: isEdit ? `${baseUrl}/${id}` : baseUrl,
                    method: isEdit ? 'PATCH' : 'POST',
                    data: $(form).serialize(),
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    if (isEdit) editModal.hide(); else bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();
                    if (!isEdit) form.reset();
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message || 'اطلاعات ذخیره شد.', 'success');
                }).fail(showError).always(() => setLoading(button, false));
            });

            $('#confirmDelete').on('click', function () {
                if (!deleteId) return;
                const button = this;
                setLoading(button, true, 'در حال حذف...');
                $.ajax({
                    url: `${baseUrl}/${deleteId}`,
                    method: 'DELETE',
                    data: {_token: csrfToken},
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    deleteModal.hide();
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message || 'اطلاعات حذف شد.', 'success');
                }).fail(showError).always(() => setLoading(button, false));
            });
        });
    </script>
@endsection
