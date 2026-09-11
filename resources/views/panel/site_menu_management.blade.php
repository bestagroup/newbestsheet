@extends('layouts.base')

@section('title', 'مدیریت منوی سایت')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">لیست منو سایت</h5>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">افزودن منو سایت</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                    <tr class="table-light">
                        <th>اولویت نمایش</th>
                        <th>نام صفحه</th>
                        <th>آدرس صفحه</th>
                        <th>زیر منو</th>
                        <th>وضعیت</th>
                        <th>تغییر</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>۰</td>
                        <td>صفحه دمو</td>
                        <td>demo</td>
                        <td>ندارد</td>
                        <td><span class="badge bg-secondary">عدم نمایش</span></td>
                        <td>
                            <button class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"><i class="mdi mdi-pencil-outline"></i></button>
                            <button class="btn btn-sm btn-icon btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="mdi mdi-delete-outline"></i></button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title w-100" id="deleteModalLabel">حذف منو سایت</h5>
                    <button type="button" class="btn-close position-absolute start-0 mx-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    آیا از حذف این منو مطمئن هستید؟
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-danger">حذف</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit/Add Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">ویرایش اطلاعات منو سایت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">عنوان منو سایت</label>
                                <input type="text" class="form-control" placeholder="صفحه دمو">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">زیر منو سایت</label>
                                <select class="form-select">
                                    <option>ندارد</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نمایش / عدم نمایش</label>
                                <select class="form-select">
                                    <option>در حال نمایش</option>
                                    <option>عدم نمایش</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">کاربرد منو</label>
                                <input type="text" class="form-control" value="در سایت">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">سطح نمایش</label>
                                <select class="form-select">
                                    <option>انتخاب کنید</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">کلاس کنترلر</label>
                                <input type="text" class="form-control" value="demo">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">اولویت نمایش</label>
                                <input type="number" class="form-control" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نمایش به صورت مگامنو</label>
                                <select class="form-select">
                                    <option>خیر</option>
                                    <option>بله</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">کلمات کلیدی</label>
                            <input type="text" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">توضیحات</label>
                            <textarea class="form-control" rows="3"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">ذخیره اطلاعات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
