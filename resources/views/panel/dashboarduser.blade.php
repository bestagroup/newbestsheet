@extends('layouts.base')
@section('title', 'مدیریت کاربران داشبورد')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">لیست کاربران داشبورد</h5>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">افزودن کاربر جدید</a>
            </div>

            <div class="table-responsive">
                <table id="usersTable" class="table table-bordered text-center">
                    <thead class="table-light">
                    <tr>
                        <th>ردیف</th>
                        <th>نام و نام خانوادگی</th>
                        <th>نام کاربری</th>
                        <th>آدرس ایمیل</th>
                        <th>موبایل</th>
                        <th>وضعیت</th>
                        <th>تغییر</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>1</td>
                        <td>محمد حسینی</td>
                        <td>hosseindbk</td>
                        <td>hosseindbk@gmail.com</td>
                        <td>09123456789</td>
                        <td><span class="badge bg-success">در حال نمایش</span></td>
                        <td>
                            <button class="btn btn-sm btn-icon btn-outline-primary" onclick="editUser(this)"><i class="mdi mdi-pencil-outline"></i></button>
                            <button class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete(this)"><i class="mdi mdi-delete-outline"></i></button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">ثبت اطلاعات کاربر جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">نام و نام خانوادگی</label>
                                <input type="text" class="form-control" placeholder="نام و نام خانوادگی را وارد کنید">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نام کاربری</label>
                                <input type="text" class="form-control" placeholder="نام کاربری را وارد کنید">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">آدرس ایمیل</label>
                                <input type="email" class="form-control" placeholder="آدرس ایمیل را وارد کنید">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">شماره موبایل</label>
                                <input type="text" class="form-control" placeholder="شماره موبایل را وارد کنید">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">رمز عبور</label>
                                <input type="password" class="form-control" placeholder="رمز عبور را وارد کنید">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">تکرار رمز عبور</label>
                                <input type="password" class="form-control" placeholder="تکرار رمز عبور را وارد کنید">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">نوع کاربر</label>
                                <select class="form-select">
                                    <option>ادمین</option>
                                    <option>مدیر</option>
                                    <option>مشاور</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">وضعیت</label>
                                <select class="form-select">
                                    <option selected>در حال نمایش</option>
                                    <option>عدم نمایش</option>
                                </select>
                            </div>
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

@push('scripts')
    <script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            new DataTable('#usersTable', {
                language: {
                    url: "{{ asset('assets/vendor/js/fa.json') }}"
                }
            });
        });

        function confirmDelete(button) {
            Swal.fire({
                title: 'حذف کاربر',
                text: 'آیا از حذف این کاربر اطمینان دارید؟',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'بله، حذف شود!',
                cancelButtonText: 'لغو'
            }).then((result) => {
                if (result.isConfirmed) {
                    button.closest('tr').remove();
                    Swal.fire('حذف شد!', 'کاربر موردنظر حذف شد.', 'success');
                }
            });
        }

        function editUser(button) {
            // مقداردهی فرم و نمایش مدال
            const row = button.closest('tr');
            const cells = row.querySelectorAll('td');
            const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
            modal.show();
            // نمونه برای مقداردهی
            // document.querySelector('[name=\'name\']').value = cells[1].innerText;
        }
    </script>
@endpush
