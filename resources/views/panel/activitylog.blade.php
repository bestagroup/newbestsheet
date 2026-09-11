@extends('layouts.base')

@section('title', 'ردیابی فعالیت‌های عملیاتی')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-1">ردیابی فعالیت‌های عملیاتی</h5>
            <small class="text-muted">ورودها و عملیات مهم مکاتبات، تقویم، صورتجلسات و سایر بخش‌های ثبت‌شده</small>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="activityLogTable" class="table table-striped table-bordered w-100">
                    <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>سمت</th>
                        <th>عملیات</th>
                        <th>توضیحات</th>
                        <th>IP</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#activityLogTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('activitylog.index') }}',
        columns: [
            {data: 'user', name: 'user.name', orderable: false},
            {data: 'user_role', name: 'user_role', orderable: false, searchable: false},
            {data: 'action', name: 'action'},
            {data: 'description', name: 'description', orderable: false},
            {data: 'ip_address', name: 'ip_address'},
            {data: 'status', name: 'status'},
            {data: 'created_at', name: 'created_at'}
        ],
        order: [[6, 'desc']],
        language: {url: '{{ asset('assets/vendor/js/fa.json') }}'}
    });
});
</script>
@endsection
