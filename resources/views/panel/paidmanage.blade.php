@extends('layouts.base')

@section('title', 'مدیریت پرداخت ها')
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
                @if(Gate::allows('can-access', ['finance', 'insert']))
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{ $thispage['add'] }}</button>
                @endif
            </div>

            <div class="table-responsive">
                <table id="sample1" class="table table-striped table-bordered yajra-datatable">
                    <thead>
                    <tr class="table-light">
                        <th>نام پروژه</th>
                        <th>مبلغ پرداختی</th>
                        <th>شماره چک/سند</th>
                        <th>تاریخ پرداخت</th>
                        <th>علت پرداخت</th>
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
                <div class="modal-body">آیا از حذف این پرداخت مطمئن هستید؟</div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">حذف</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $thispage['add'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="addform" action="{{ route('paidmanage.store') }}" method="POST">
                        @csrf
<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        @include('panel.partials.payment-form', ['prefix' => 'add_', 'projects' => $projects])
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $thispage['edit'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="editform" method="POST">
                        @csrf
<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        @method('PATCH')
                        @include('panel.partials.payment-form', ['prefix' => 'edit_', 'projects' => $projects])
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ asset('js/payment-idempotency.js') }}"></script>
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    <script>
        $(function () {
            const baseUrl = @json(url('panel/paidmanage'));
            const csrfToken = @json(csrf_token());
            const table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: @json(route('paidmanage.index')),
                columns: [
                    {data: 'title', name: 'projects.title'},
                    {data: 'amount', name: 'finances.amount'},
                    {data: 'serial', name: 'finances.serial'},
                    {data: 'date', name: 'finances.date'},
                    {data: 'description', name: 'finances.description'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {url: @json(asset('assets/vendor/js/fa.json'))}
            });

            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            let deleteId = null;

            function showError(xhr) {
                const message = xhr.responseJSON?.message || 'عملیات انجام نشد. لطفاً مجدداً تلاش کنید.';
                Swal.fire('خطا', message, 'error');
            }

            function setButtonLoading(button, loading, text) {
                if (!button) return;
                if (loading) {
                    button.dataset.originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = `<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> ${text}`;
                } else {
                    button.disabled = false;
                    button.innerHTML = button.dataset.originalHtml || button.innerHTML;
                }
            }

            function populate(prefix, finance) {
                document.getElementById(prefix + 'project_id').value = finance.project_id ?? '';
                document.getElementById(prefix + 'amount').value = finance.amount ? Number(finance.amount).toLocaleString('en-US') : '';
                document.getElementById(prefix + 'serial').value = finance.serial ?? '';
                document.getElementById(prefix + 'docserial').value = finance.docserial ?? '';
                document.getElementById(prefix + 'date').value = finance.date ?? '';
                document.getElementById(prefix + 'description').value = finance.description ?? '';
            }

            $(document).on('click', '.edit-btn', function () {
                const id = Number($(this).data('id'));
                $.get(`${baseUrl}/${id}/edit`)
                    .done(response => {
                        populate('edit_', response.data);
                        document.getElementById('editform').dataset.id = id;
                        editModal.show();
                    })
                    .fail(showError);
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
                setButtonLoading(button, true, 'در حال ذخیره...');

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
                }).fail(showError).always(() => setButtonLoading(button, false));
            });

            $('#confirmDelete').on('click', function () {
                if (!deleteId) return;
                const button = this;
                setButtonLoading(button, true, 'در حال حذف...');

                $.ajax({
                    url: `${baseUrl}/${deleteId}`,
                    method: 'DELETE',
                    data: {_token: csrfToken},
                    headers: {'X-CSRF-TOKEN': csrfToken}
                }).done(response => {
                    deleteModal.hide();
                    table.ajax.reload(null, false);
                    Swal.fire('موفق', response.message || 'پرداخت حذف شد.', 'success');
                }).fail(showError).always(() => setButtonLoading(button, false));
            });

            $(document).on('input', '.money-input', function () {
                const digits = this.value.replace(/[^0-9۰-۹٠-٩]/g, '')
                    .replace(/[۰-۹]/g, digit => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
                    .replace(/[٠-٩]/g, digit => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
                this.value = digits ? Number(digits).toLocaleString('en-US') : '';
            });
        });
    </script>
@endsection
