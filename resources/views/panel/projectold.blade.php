@extends('layouts.base')
@section('title', 'مدیریت پروژه ها')
@section('style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dropzone.min.css') }}"/>
<style> table{margin: 0 auto;width: 100% !important;clear: both;border-collapse: collapse;table-layout: auto !important;word-wrap:break-word;white-space: nowrap;} .dt-layout-start{margin-right: 0 !important;} .dt-layout-end{margin-left: 0 !important;}</style>
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{$thispage['list']}}</h5>
                @if (auth()->user()->can('can-access', ['project', 'insert']))
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{$thispage['add']}}</a>
                @endif
            </div>
            <div class="table-responsive">
                <table id="sample1" class="table table-striped table-bordered yajra-datatable">
                <thead>
                    <tr class="table-light">
                        <th>تغییرات</th>
                        <th>نام تجاری طرح</th>
                        <th>مدیرعامل شرکت</th>
                        <th>وضعیت پرتفو</th>
                        <th>مرحله فرایند شرکت</th>
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
                        <th>تغییرات</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title w-100" id="deleteModalLabel">{{ $thispage['delete'] }}</h5>
                    <button type="button" class="btn-close position-absolute start-0 mx-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    آیا از حذف این زیر منو مطمئن هستید؟
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">حذف</button>
                </div>
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
                        <form id="addform" method="POST" action="{{ route(request()->segment(2).'.store') }}">
                            @csrf
                        <div class="row mb-3">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نام تجاری طرح</label>
                                <input type="text" name="title" id="title" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">نام شرکت</label>
                                <input type="text" name="company_name" id="company_name" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مدیرعامل شرکت</label>
                                <input type="text" name="CEO" id="CEO" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">وضعیت پرتفو</label>
                                <input type="text" name="portfo_status" id="portfo_status" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مرحله فرایند شرکت</label>
                                <input type="text" name="flow_level" id="flow_level" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">درصد پیشرفت</label>
                                <input type="text" name="progress_percentage" id="progress_percentage" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">وضعیت فعالیت</label>
                                <input type="text" name="activity_status" id="activity_status" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">تاریخ شروع قرارداد</label>
                                <input type="text" name="start_date" id="start_date" class="form-control" data-jdp autocomplete="off" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مبلغ درخواستی تایید شده</label>
                                <input type="text" name="amount_request_accept" id="amount_request_accept" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مبلغ تعهد مرحله اول</label>
                                <input type="text" name="amount_commitment_first_stage" id="amount_commitment_first_stage" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مبلغ تعهد مرحله دوم</label>
                                <input type="text" name="amount_commitment_second_stage" id="amount_commitment_second_stage" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مبلغ تعهد مرحله سوم</label>
                                <input type="text" name="amount_commitment_third_stage" id="amount_commitment_third_stage" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مبلغ تعهد مرحله چهارم</label>
                                <input type="text" name="amount_commitment_fourth_stage" id="amount_commitment_fourth_stage" class="form-control" />
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">مبلغ تعهد مرحله پنجم</label>
                                <input type="text" name="amount_commitment_fifth_stage" id="amount_commitment_fifth_stage" class="form-control" />
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">معرفی طرح</label>
                                <textarea name="description" id="description" cols="30" rows="10" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" id="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Modal -->
    @foreach($projects as $project)
        <div class="modal fade" id="editModal{{$project->id}}" tabindex="-1" aria-labelledby="editModalLabel{{$project->id}}" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel{{$project->id}}">{{$thispage['edit']}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                    </div>
                    <div class="modal-body">
                            <form id="editform_{{ $project->id }}" method="POST" action="{{ route(request()->segment(2).'.update', $project->id) }}">
                                @csrf
                                @method('PATCH')
                            <input type="hidden" name="menu_id" id="menu_id_{{$project->id}}" value="{{$project->id}}" />
                            <div class="row mb-3 ">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">نام تجاری طرح</label>
                                    <input type="text" name="title" id="title_{{$project->id}}" value="{{$project->title}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">نام شرکت</label>
                                    <input type="text" name="company_name" id="company_name_{{$project->id}}" value="{{$project->company_name}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مدیرعامل شرکت</label>
                                    <input type="text" name="CEO" id="CEO_{{$project->id}}" value="{{$project->CEO}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">وضعیت پرتفو</label>
                                    <input type="text" name="portfo_status" id="portfo_status_{{$project->id}}" value="{{$project->portfo_status}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مرحله فرایند شرکت</label>
                                    <input type="text" name="flow_level" id="flow_level_{{$project->id}}" value="{{$project->flow_level}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">درصد پیشرفت</label>
                                    <input type="text" name="progress_percentage" id="progress_percentage_{{$project->id}}" value="{{$project->progress_percentage}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">وضعیت فعالیت</label>
                                    <input type="text" name="activity_status" id="activity_status_{{$project->id}}" value="{{$project->activity_status}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">تاریخ شروع قرارداد</label>
                                    <input type="text" name="start_date" id="start_date_{{$project->id}}" value="{{$project->start_date}}" class="form-control" data-jdp autocomplete="off" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مبلغ درخواستی تایید شده</label>
                                    <input type="text" name="amount_request_accept" id="amount_request_accept_{{$project->id}}" value="{{number_format($project->amount_request_accept)}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مبلغ تعهد مرحله اول</label>
                                    <input type="text" name="amount_commitment_first_stage" id="amount_commitment_first_stage_{{$project->id}}" value="{{number_format($project->amount_commitment_first_stage)}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مبلغ تعهد مرحله  دوم</label>
                                    <input type="text" name="amount_commitment_second_stage" id="amount_commitment_second_stage_{{$project->id}}" value="{{number_format($project->amount_commitment_second_stage)}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مبلغ تعهد مرحله  سوم</label>
                                    <input type="text" name="amount_commitment_third_stage" id="amount_commitment_third_stage_{{$project->id}}" value="{{number_format($project->amount_commitment_third_stage)}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مبلغ تعهد مرحله چهارم </label>
                                    <input type="text" name="amount_commitment_fourth_stage" id="amount_commitment_fourth_stage_{{$project->id}}" value="{{number_format($project->amount_commitment_fourth_stage)}}" class="form-control" />
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">مبلغ تعهد مرحله  پنجم</label>
                                    <input type="text" name="amount_commitment_fifth_stage" id="amount_commitment_fifth_stage_{{$project->id}}" value="{{number_format($project->amount_commitment_fifth_stage)}}" class="form-control" />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">لوگو شرکت</label>
                                    <div class="input-group mb-3">
                                        <input type="text" name="logo" class="form-control" value="{{ $project->logo }}" id="logo_{{ $project->id }}" readonly placeholder="انتخاب فایل برای پروژه {{ $project->id }}">
                                        <button class="btn btn-outline-secondary file-selector" type="button" data-record-id="{{ $project->id }}" data-input-id="logo_{{ $project->id }}">
                                            انتخاب فایل
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">معرفی طرح</label>
                                    <textarea name="description" id="description_{{$project->id}}" cols="30" rows="10" class="form-control">{{$project->description}}</textarea>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" id="editsubmit_{{$project->id}}" class="btn btn-primary" >ذخیره اطلاعات</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <!-- Profile Modal -->
    @foreach($projects as $project)
        <div class="modal fade" id="showModal{{ $project->id }}" tabindex="-1" aria-labelledby="editModalLabel{{$project->id}}" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    <!-- Header -->
                    <div class="modal-header">
                        <h5 class="modal-title">اطلاعات شرکت: {{ $project->company_name ?? '---' }} </h5>
                        <button  type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" id="companyTabs{{ $project->id }}" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-profile{{ $project->id }}"
                                        type="button" role="tab" aria-controls="tab-profile{{ $project->id }}" aria-selected="true">
                                    پروفایل
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="investment-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-investment{{ $project->id }}"
                                        type="button" role="tab" aria-controls="tab-investment{{ $project->id }}" aria-selected="false">
                                    سرمایه‌گذاری
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="payments-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-payments{{ $project->id }}"
                                        type="button" role="tab" aria-controls="tab-payments{{ $project->id }}" aria-selected="false">
                                    پرداخت‌ها
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="kpi-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-kpi{{ $project->id }}"
                                        type="button" role="tab" aria-controls="tab-kpi{{ $project->id }}" aria-selected="false">
                                    KPI
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="commitment-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-commitment{{ $project->id }}"
                                        type="button" role="tab" aria-controls="tab-commitment{{ $project->id }}" aria-selected="false">
                                    تعهدات
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="guaranty-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-guaranty{{ $project->id }}"
                                        type="button" role="tab" aria-controls="tab-guaranty{{ $project->id }}" aria-selected="false">
                                    تضامین
                                </button>
                            </li>

                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content mt-3" id="companyTabsContent{{ $project->id }}">
                            <!-- Profile Tab -->
                            <div class="tab-pane fade show active" id="tab-profile{{ $project->id }}" role="tabpanel" aria-labelledby="profile-tab{{ $project->id }}">
                                <img src="@if($project->logo) {{asset('storage/'.$project->logo)  }} @endif"  class="rounded-circle mb-3" width="80" height="80"  alt="لوگو">
                                <p><strong>نام تجاری:</strong>    {{ $project->company_name }}  </p>
                                <p><strong>معرفی طرح:</strong>    {{ $project->description }}   </p>
                                <p><strong>مدیرعامل:</strong>     {{ $project->CEO }}           </p>
                                <p><strong>شماره موبایل:</strong> {{ $project->ceo_phone }}     </p>
                                <p><strong>وضعیت پروژه:</strong>  {{ $project->activity_status }}</p>
                            </div>

                            <!-- Investment Tab -->
                            <style>
                                input[type="checkbox"].status-green:checked {
                                    accent-color: #28a745;
                                }
                                input[type="checkbox"].status-red:checked {
                                    accent-color: #dc3545;
                                }
                            </style>
                            <div class="tab-pane fade" id="tab-investment{{ $project->id }}" role="tabpanel" aria-labelledby="investment-tab{{ $project->id }}">
                                <div class="accordion" id="projectStepsAccordion{{ $project->id }}">
                                    <!-- مرحله 1 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading1">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }} status-green" {{ $project->step_1 ? 'checked' : '' }}checked disabled>
                                                بررسی اولیه طرح
                                            </button>
                                        </h2>
                                        <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="heading1" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- مرحله 2 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading2">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                غربالگری
                                            </button>
                                        </h2>
                                        <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- مرحله 3 -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading3">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                ارزیابی اولیه
                                            </button>
                                        </h2>
                                        <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading4">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                ارزیابی موشکافانه
                                            </button>
                                        </h2>
                                        <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="heading4" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading5">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                تاییدیه مدیرعامل و هیئت مدیره سینا وی سی
                                            </button>
                                        </h2>
                                        <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="heading5" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading6">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                تاییدیه مدیرعامل و هیئت مدیره دانشمند
                                            </button>
                                        </h2>
                                        <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading7">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7" aria-expanded="false" aria-controls="collapse7">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                تصویب هیئت مدیره سینا وی سی
                                            </button>
                                        </h2>
                                        <div id="collapse7" class="accordion-collapse collapse" aria-labelledby="heading7" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading8">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8" aria-expanded="false" aria-controls="collapse8">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                ارزش گذاری
                                            </button>
                                        </h2>
                                        <div id="collapse8" class="accordion-collapse collapse" aria-labelledby="heading8" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading9">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse9" aria-expanded="false" aria-controls="collapse9">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                ارائه طرح در کمیته ارزش گذاری
                                            </button>
                                        </h2>
                                        <div id="collapse9" class="accordion-collapse collapse" aria-labelledby="heading9" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading10">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse10" aria-expanded="false" aria-controls="collapse10">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                توافق قراردادی
                                            </button>
                                        </h2>
                                        <div id="collapse10" class="accordion-collapse collapse" aria-labelledby="heading10" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading11">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse11" aria-expanded="false" aria-controls="collapse11">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                تصویب قرارداد در هیئت میره سینا وی سی
                                            </button>
                                        </h2>
                                        <div id="collapse11" class="accordion-collapse collapse" aria-labelledby="heading11" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading12">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse12" aria-expanded="false" aria-controls="collapse12">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                عقد قرارداد
                                            </button>
                                        </h2>
                                        <div id="collapse12" class="accordion-collapse collapse" aria-labelledby="heading12" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading13">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse13" aria-expanded="false" aria-controls="collapse13">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                پایان دوره ارزش آفرینی
                                            </button>
                                        </h2>
                                        <div id="collapse13" class="accordion-collapse collapse" aria-labelledby="heading13" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading10">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse10" aria-expanded="false" aria-controls="collapse10">
                                                <input type="checkbox" class="me-2 {{ $project->step_1 === 'rejected' ? 'status-red' : ($project->step_1 ? 'status-green' : '') }}" {{ $project->step_1 ? 'checked' : '' }} disabled>
                                                خروج از طرح
                                            </button>
                                        </h2>
                                        <div id="collapse10" class="accordion-collapse collapse" aria-labelledby="heading10" data-bs-parent="#projectStepsAccordion{{ $project->id }}">
                                            <div class="accordion-body">
                                                <form>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۱</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۲</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ورودی ۳</label>
                                                        <input type="text" class="form-control">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">توضیحات</label>
                                                        <textarea class="form-control" rows="3"></textarea>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Payments Tab  -->
                            <div class="tab-pane fade" id="tab-payments{{ $project->id }}" role="tabpanel" aria-labelledby="payments-tab{{ $project->id }}">
                                <table class="table table-bordered mt-2">
                                    <thead>
                                    <tr>
                                        <th>مبلغ</th>
                                        <th>شماره قسط</th>
                                        <th>تاریخ پرداخت</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($finances as $payment)
                                        @if($payment->project_id == $project->id)
                                        <tr>
                                            <td>{{ number_format($payment->amount) }} تومان</td>
                                            <td>{{ $payment->serial }}</td>
                                            <td>{{ $payment->date }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- KPI Tab -->
                            <div class="tab-pane fade" id="tab-kpi{{ $project->id }}" role="tabpanel" aria-labelledby="kpi-tab{{ $project->id }}">
                                <ul class="list-group">
                                    @foreach($finances as $payment)
                                        @if($payment->project_id == $project->id)
                                            <tr>
                                                <td>{{ number_format($payment->amount) }} تومان</td>
                                                <td>{{ $payment->serial }}</td>
                                                <td>{{ $payment->date }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>

                            <div class="tab-pane fade" id="tab-commitment{{ $project->id }}" role="tabpanel" aria-labelledby="commitment-tab{{ $project->id }}">
                                <table class="table table-bordered mt-2">
                                    <thead>
                                    <tr>
                                        <th>مبلغ</th>
                                        <th>شماره قسط</th>
                                        <th>تاریخ پرداخت</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($finances as $payment)
                                        @if($payment->project_id == $project->id)
                                            <tr>
                                                <td>{{ number_format($payment->amount) }} تومان</td>
                                                <td>{{ $payment->serial }}</td>
                                                <td>{{ $payment->date }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="tab-pane fade" id="tab-guaranty{{ $project->id }}" role="tabpanel" aria-labelledby="guaranty-tab{{ $project->id }}">
                                <table class="table table-bordered mt-2">
                                    <thead>
                                    <tr>
                                        <th>مبلغ</th>
                                        <th>شماره قسط</th>
                                        <th>تاریخ پرداخت</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($finances as $payment)
                                        @if($payment->project_id == $project->id)
                                            <tr>
                                                <td>{{ number_format($payment->amount) }} تومان</td>
                                                <td>{{ $payment->serial }}</td>
                                                <td>{{ $payment->date }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>


                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <!-- Media Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $thispage['add'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="{{ route('storemedia') }}" enctype="multipart/form-data"
                          class="dropzone" id="fileUploadZone" style="min-height: 200px; border-style: dashed; border: 2px dashed #ccc; padding: 20px; margin-bottom: 30px;">
                        @csrf
                        <input type="hidden" name="record_id" id="recordIdInput">
                        <div class="dz-message text-center text-muted">
                            <div class="mb-3">
                                <i class="bi bi-cloud-arrow-up" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="fw-bold mb-2">برای آپلود فایل، کلیک کنید یا فایل را بکشید اینجا</h5>
                            <p class="small text-secondary mb-0">فرمت‌های مجاز: JPG, PNG, PDF, MP4, DOCX (حداکثر 10 مگابایت)</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- مودال پیش نمایش عمومی -->
    <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">پیش نمایش فایل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body text-center" id="previewContent">
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')
    <script src="{{asset('assets/vendor/js/dataTables.min.js')}}"></script>
    <script src="{{'https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js'}}"></script>
    <script src="{{'https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js'}}"></script>
    <script src="{{'https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js'}}"></script>

    <script type="text/javascript">
        $(function () {
            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                order: [[0, 'desc']],
                scrollX: true,
                scrollCollapse: true,
                fixedColumns: {
                    start: 3
                },
                ajax: "{{ route(request()->segment(2) . '.index') }}",
                columns: [
                    {data: 'action'                         , name: 'action', orderable: true, searchable: true},
                    {data: 'commercial_name'                , name: 'commercial_name'},
                    {data: 'CEO'                            , name: 'CEO'},
                    {data: 'portfo_status'                  , name: 'portfo_status'},
                    {data: 'flow_level'                     , name: 'flow_level'},
                    {data: 'invest_step'                    , name: 'invest_step'},
                    {data: 'activity_status'                , name: 'activity_status'},
                    {data: 'start_date'                     , name: 'start_date'},
                    {data: 'amount_request_accept'          , name: 'amount_request_accept'},
                    {data: 'amount_deposited'               , name: 'amount_deposited'},
                    {data: 'amount_commitment_first_stage'  , name: 'amount_commitment_first_stage'},
                    {data: 'amount_commitment_second_stage' , name: 'amount_commitment_second_stage'},
                    {data: 'amount_commitment_third_stage'  , name: 'amount_commitment_third_stage'},
                    {data: 'amount_commitment_fourth_stage' , name: 'amount_commitment_fourth_stage'},
                    {data: 'amount_commitment_fifth_stage'  , name: 'amount_commitment_fifth_stage'},
                    {data: 'first_stage_payment'            , name: 'first_stage_payment'},
                    {data: 'second_stage_payment'           , name: 'second_stage_payment'},
                    {data: 'third_stage_payment'            , name: 'third_stage_payment'},
                    {data: 'fourth_stage_payment'           , name: 'fourth_stage_payment'},
                    {data: 'fifth_stage_payment'            , name: 'fifth_stage_payment'},
                    {data: 'commitment_balance'             , name: 'commitment_balance'},
                    {data: 'action'                         , name: 'action', orderable: true, searchable: true},
                ],
                language: {
                    url: "{{asset('assets/vendor/js/fa.json')}}"
                }
            });
        });
    </script>

    <script>
        jQuery(function($){
            function showToast(message, type = 'success') {
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    timeOut: 3000,
                    rtl: true
                };

                if (toastr[type]) {
                    toastr[type](message);
                } else {
                    toastr.success(message);
                }
            }
            $('#submit').on('click', function(e){
                e.preventDefault();
                var $btn  = $(this);
                var $form = $('#addform');
                var originalHtml = $btn.html();

                $btn.prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ارسال...');

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });

                $.ajax({
                    url: "{{ route(request()->segment(2).'.store') }}",
                    method: 'POST',
                    data: $form.serialize(),
                    success: function (data) {
                        if (data.success) {
                            const modalEl = document.getElementById('addModal');
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

                            modalEl.addEventListener('hidden.bs.modal', function handler(){
                                modalEl.removeEventListener('hidden.bs.modal', handler);
                                $('.yajra-datatable').DataTable().ajax.reload(null, false);
                            }, { once: true });

                            modal.hide();
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open');
                            $('body').css('padding-right', '');
                            showToast('آیتم با موفقیت افزوده شد!', 'success');
                        } else {
                            swal(data.subject, data.message, data.flag);
                        }
                    },
                    error: function () {
                        swal('خطا', 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.', 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });
    </script>

    <script>
        jQuery(function($){
            function showToast(message, type = 'success') {
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-center",
                    timeOut: 3000,
                    rtl: true
                };

                if (toastr[type]) {
                    toastr[type](message);
                } else {
                    toastr.success(message);
                }
            }

            $(document).on('click', '[id^=editsubmit_]', function(e){
                e.preventDefault();
                const $btn = $(this);
                const id = this.id.split('_')[1];
                const $form = $('#editform_' + id);

                if (!$form.length) {
                    console.error('فرم editform_' + id + ' پیدا نشد!');
                    return;
                }

                const url = $form.attr('action'); // استفاده از URL داینامیک
                const originalHtml = $btn.html();
                disableBtnWithSpinner($btn);

                $.ajax({
                    url: url,
                    method: 'PATCH',
                    data: $form.serialize(),
                    success: function (data) {
                        if (data.success) {
                            const modalEl = document.getElementById('editModal' + id);
                            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                            modalEl.addEventListener('hidden.bs.modal', function handler(){
                                modalEl.removeEventListener('hidden.bs.modal', handler);
                                $('.yajra-datatable').DataTable().ajax.reload(null, false);
                                showToast('آیتم با موفقیت ویرایش شد!', 'success');
                            }, { once: true });
                            modal.hide();
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('padding-right', '');
                        } else {
                            swal(data.subject, data.message, data.flag);
                        }
                    },
                    error: function () {
                        swal('خطا', 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.', 'error');
                    },
                    complete: function () {
                        restoreBtn($btn, originalHtml);
                    }
                });
            });

            function disableBtnWithSpinner($btn){
                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ارسال...'
                );
            }
            function restoreBtn($btn, html){
                $btn.prop('disabled', false).html(html);
            }
        });
    </script>

    <script>
        jQuery(function ($) {
            let deleteId = null;

            function showToast(message, type = 'success') {
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    timeOut: 3000,
                    rtl: true
                };
                toastr[type] ? toastr[type](message) : toastr.success(message);
            }

            // وقتی روی دکمه حذف کلیک شد
            $(document).on('click', '.delete-btn', function () {
                deleteId = $(this).data('id');
                $('#deleteModal').modal('show');
            });

            // وقتی تایید حذف کلیک شد
            $('#confirmDelete').on('click', function (e) {
                const $btn = $(this);
                const originalHtml = $btn.html();

                $btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال حذف...'
                );

                $.ajax({
                    url: "{{ route(request()->segment(2).'.destroy', 0) }}",
                    method: 'DELETE',
                    data: { "_token": "{{ csrf_token() }}", id: deleteId },
                    success: function () {
                        $('#deleteModal').modal('hide');
                        $('.yajra-datatable').DataTable().ajax.reload(null, false);
                        showToast('آیتم با موفقیت حذف شد!', 'success');
                    },
                    error: function () {
                        showToast('مشکلی پیش آمد. لطفاً دوباره تلاش کنید.', 'error');
                    },
                    complete: function () {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });
        });

    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('input', function (e) {
                    let value = e.target.value.replace(/,/g, '');
                    if (!isNaN(value) && value.length > 0) {
                        e.target.value = Number(value).toLocaleString('en-US');
                    } else {
                        e.target.value = '';
                    }
                });
            }
        });
    </script>

    <script>
        Dropzone.autoDiscover = false;

        document.addEventListener("DOMContentLoaded", function () {
            const fileFormSelector = "#fileUploadZone";
            let currentRecordId = null;

            const dz = new Dropzone(fileFormSelector, {
                url: "{{ route('storemedia') }}",
                headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
                maxFilesize: 20,
                acceptedFiles: 'image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                dictDefaultMessage: "فایل‌ها را اینجا رها کنید یا کلیک کنید برای انتخاب",
                init: function () {
                    this.on("sending", function (file, xhr, formData) {

                        formData.append("record_id", currentRecordId || document.getElementById('recordIdInput').value);
                    });
                    this.on("success", function (file, response) {
                        const extension = file.name.split('.').pop().toLowerCase();
                        previewFile(response.file_path.replace(/^\/+/, ''), extension);
                        showToast("✅ فایل با موفقیت آپلود شد");
                        this.removeFile(file);
                    });
                    this.on("error", function (file, response) {
                        showToast("❌ خطا در آپلود فایل", "danger");
                    });
                }
            });

            $(document).on('click', '.upload-btn', function () {
                currentRecordId = $(this).data('id');
                $('#recordIdInput').val(currentRecordId);

                dz.removeAllFiles(true);

                $('#uploadModal').modal('show');
            });
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let activeInputId = null;
            document.querySelectorAll('.file-selector').forEach(input => {
                input.addEventListener('click', function () {
                    const recordId = this.dataset.recordId;
                    activeInputId = this.dataset.inputId;

                    window.open(`{{ route('selectfile') }}?record_id=${recordId}`, 'FileManager', 'width=800,height=600');
                });
            });
            window.setFileUrl = function (url) {
                if (activeInputId) {
                    document.getElementById(activeInputId).value = url;
                }
            };
        });
    </script>
@endsection
