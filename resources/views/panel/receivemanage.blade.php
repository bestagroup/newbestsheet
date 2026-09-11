@extends('layouts.base')

@section('title', 'گزارش دریافت‌های مالی')
@section('style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
<style>
    table { margin: 0 auto; width: 100% !important; clear: both; border-collapse: collapse; table-layout: auto !important; white-space: nowrap; }
    .dt-layout-start { margin-right: 0 !important; }
    .dt-layout-end { margin-left: 0 !important; }
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0">{{ $thispage['list'] }}</h5>
        </div>

        <div class="table-responsive">
            <table id="receivesTable" class="table table-striped table-bordered yajra-datatable">
                <thead>
                <tr class="table-light">
                    <th>شناسه</th>
                    <th>شماره سند / مرحله</th>
                    <th>طرح / پروژه</th>
                    <th>مبلغ</th>
                    <th>تاریخ</th>
                    <th>توضیحات</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        new DataTable('#receivesTable', {
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: "{{ route('receivemanage.index') }}",
            columns: [
                {data: 'id', name: 'finances.id'},
                {data: 'serial', name: 'finances.serial'},
                {data: 'project_title', name: 'projects.title'},
                {data: 'amount', name: 'finances.amount'},
                {data: 'date', name: 'finances.date'},
                {data: 'description', name: 'finances.description'},
            ],
            language: {
                url: "{{ asset('assets/vendor/js/fa.json') }}"
            }
        });
    });
</script>
@endsection
