@extends('layouts.base')
@section('title', 'مدیریت منوی داشبورد')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
    <style> table{margin: 0 auto;width: 100% !important;clear: both;border-collapse: collapse;table-layout: auto;word-wrap:break-word;} .dt-layout-start{margin-right: 0 !important;} .dt-layout-end{margin-left: 0 !important;}</style>

    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">{{$thispage['list']}}</h5>
                @if(Gate::allows('can-access', ['menupanel', 'insert']))
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{$thispage['add']}}</a>
                @endif
            </div>

            <div class="table-responsive">
                <table id="sample1" class="table table-striped table-bordered yajra-datatable">
                    <thead>
                    <tr class="table-light">
                        <th>اولویت نمایش</th>
                        <th>نام صفحه</th>
                        <th>نام صفحه فارسی</th>
                        <th>آدرس صفحه</th>
                        <th>کلاس</th>
                        <th>کنترلر</th>
                        <th>وضعیت</th>
                        <th>تغییر</th>
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
                    <form id="addform" data-type="create" method="POST" class="row g-4 mb-4" action="{{ route('menupanel.store') }}">
                        {{csrf_field()}}
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input required type="text" class="form-control" id="label" name="label"
                                       placeholder="نام  منو داشبورد فارسی" >
                                <label for="label">نام  منو داشبورد فارسی</label>
                                <div class="invalid-feedback" id="labelFeedback">نام  منو داشبورد فارسی اجباری می باشد.</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input required type="text" class="form-control" id="title" name="title"
                                       placeholder="نام  منو داشبورد" >
                                <label for="title">نام  منو داشبورد</label>
                                <div class="invalid-feedback" id="titleFeedback">نام  منو داشبورد اجباری می باشد.</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <select name="submenu" id="submenu" class="form-control">
                                    <option value="1" >دارد</option>
                                    <option value="0" >ندارد</option>
                                </select>
                                <label for="submenu">زیر  منو داشبورد</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input required type="text" class="form-control" id="class" name="class"
                                       placeholder="کلاس داشبورد">
                                <label for="class">کلاس داشبورد</label>
                                <div class="invalid-feedback" id="classFeedback">کلاس داشبورد اجباری می باشد.</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input required type="text" class="form-control" id="controller" name="controller"
                                       placeholder="کنترلر داشبورد" >
                                <label for="controller">کنترلر داشبورد</label>
                                <div class="invalid-feedback" id="controllerFeedback">کنترلر داشبورد اجباری می باشد.</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating form-floating-outline">
                                <select name="status" id="status" class="form-control">
                                    <option value="1" >نمایش</option>
                                    <option value="0">عدم نمایش</option>
                                </select>
                                <label for="status">نمایش/عدم نمایش</label>
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
    <script src="{{asset('assets/vendor/js/dataTables.min.js')}}"></script>

    <script src="{{asset('assets/vendor/js/formhandler.js')}}"></script>

    <script type="text/javascript">
        $(function () {

            var table = $('.yajra-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{route(request()->segment(2).'.index')}}",
                columns: [
                    {data: 'id'             , name: 'menu_panels.id',className:'text-center'        },
                    {data: 'title'          , name: 'menu_panels.title'     },
                    {data: 'label'          , name: 'menu_panels.label'     },
                    {data: 'slug'           , name: 'menu_panels.slug'      },
                    {data: 'class'          , name: 'menu_panels.class'     },
                    {data: 'controller'     , name: 'menu_panels.controller'},
                    {data: 'status'         , name: 'menu_panels.status',className:'text-center'    },
                    {data: 'action'         , name: 'action', orderable: false, searchable: false,className:'text-center'},
                ],
                language: {
                    url: "{{asset('assets/vendor/js/fa.json')}}"
                }
            });

        });
    </script>
@endsection
