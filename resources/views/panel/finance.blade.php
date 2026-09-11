@extends('layouts.base')
@section('title', 'لیست دریافت و پرداخت')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
    <style> table{margin: 0 auto;width: 100% !important;clear: both;border-collapse: collapse;table-layout: auto;word-wrap:break-word;} .dt-layout-start{margin-right: 0 !important;} .dt-layout-end{margin-left: 0 !important;}</style>


@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{$thispage['list']}}</h5>

                @if (auth()->user()->can('can-access', ['finance', 'insert']))
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{$thispage['add']}}</a>
                @endif

            </div>

            <div class="table-responsive">
                <table id="sample1" class="table table-striped table-bordered yajra-datatable">
                    <thead>
                    <tr class="table-light">
                        <th>نام شرکت</th>
                        <th>نام تجاری طرح</th>
                        <th>مبلغ کل قرارداد</th>
                        <th>تاریخ قرارداد</th>
                        <th>شماره سند</th>
                        <th>مرحله پرداخت</th>
                        <th>تاریخ پرداخت</th>
                        <th>مبلغ پرداخت</th>
                        <th>تغییرات</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">{{$thispage['add']}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form id="addform" data-type="create" method="POST" class="row g-4 mb-4" action="{{route(request()->segment(2).'.'.'store')}}">
                        @csrf
                            <div class="col-6 col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <select required name="project_id" id="project_id" class="form-control select-lg select2">
                                        <option value="" selected>انتخاب کنید</option>
                                        @foreach($projects as $project)
                                            <option value="{{$project->id}}">{{$project->company_name .'-'. $project->title}}</option>
                                        @endforeach
                                    </select>
                                    <label for="serial">نام شرکت</label>
                                    <div class="invalid-feedback" id="serialFeedback">نام شرکت اجباری می باشد.</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <label for="serial">شماره مرحله پرداخت</label>
                                    <select name="serial" id="serial" class="form-control select-lg select2">
                                        <option value="" selected>انتخاب کنید</option>
                                        <option value="1" >1</option>
                                        <option value="2" >2</option>
                                        <option value="3" >3</option>
                                        <option value="4" >4</option>
                                        <option value="5" >5</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <input required type="text" class="form-control" id="docserial" name="docserial" placeholder="شماره سند بایگانی مالی" >
                                    <label for="docserial">شماره سند بایگانی مالی</label>
                                    <div class="invalid-feedback" id="docserialFeedback">شماره سند بایگانی مالی اجباری می باشد.</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <input required type="text" class="form-control number-input" id="amount" name="amount" placeholder="مبلغ پرداختی" >
                                    <label for="amount">مبلغ پرداختی</label>
                                    <div class="invalid-feedback" id="amountFeedback">مبلغ پرداختی اجباری می باشد.</div>
                                </div>
                            </div>
                        <div class="col-6 col-md-3">
                            <div class="form-floating form-floating-outline">
                                <input required type="text" data-jdp class="form-control" autocomplete="off" id="date" name="date" placeholder="تاریخ واریز" >
                                <label for="date">تاریخ واریز</label>
                                <div class="invalid-feedback" id="dateFeedback">تاریخ واریز اجباری می باشد.</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-9">
                            <div class="form-floating form-floating-outline">
                                <textarea name="description" id="description" required cols="30" rows="10" class="form-control" placeholder="توضیحات"></textarea>
                                <label for="description">توضیحات</label>
                                <div class="invalid-feedback" id="dateFeedback">توضیحات اجباری می باشد.</div>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">ویرایش اطلاعات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editModalBody">
                    <div class="text-center text-muted py-5">در حال بارگذاری...</div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/js/formhandler.js') }}"></script>

    <script type="text/javascript">
        $(function () {
            $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route(request()->segment(2).'.index') }}",
                columns: [
                    {data: 'company_name', name: 'p.company_name'},
                    {data: 'title', name: 'p.title'},
                    {data: 'contract_amount', name: 'p.amount_request_accept', className:'text-center'},
                    {data: 'contract_date', name: 'p.start_date', className:'text-center'},
                    {data: 'docserial', name: 'f.docserial', className:'text-center'},
                    {data: 'serial', name: 'f.serial', className:'text-center'},
                    {data: 'date', name: 'f.date', className:'text-center'},
                    {data: 'amount', name: 'f.amount', className:'text-center'},
                    {data: 'action'         , name: 'action', orderable: false, searchable: false, className:'text-center'},
                ],
                language: {
                    url: "{{ asset('assets/vendor/js/fa.json') }}"
                }
            });
        });
    </script>
@endsection
