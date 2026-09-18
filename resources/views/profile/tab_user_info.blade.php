@php($profileUser = auth()->user())
<div class="tab-pane fade show active" id="navs-user-card" role="tabpanel">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 760px">
        <div class="card-body p-4 text-start">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h5 class="mb-1">اطلاعات کاربری</h5><small class="text-muted">اطلاعات هویتی و تماس حساب کاربری شما</small></div>
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#profileUserForm" aria-controls="profileUserForm" aria-expanded="false">
                    <i class="mdi mdi-account-edit-outline me-1"></i>ویرایش اطلاعات
                </button>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><span class="text-muted">نام و نام خانوادگی:</span> <strong>{{ $profileUser->name ?: '—' }}</strong></div>
                <div class="col-md-6"><span class="text-muted">کد ملی:</span> <strong>{{ $profileUser->national_id ?: '—' }}</strong></div>
                <div class="col-md-6"><span class="text-muted">موبایل:</span> <strong dir="ltr">{{ $profileUser->phone ?: '—' }}</strong></div>
                <div class="col-md-6"><span class="text-muted">ایمیل:</span> <strong>{{ $profileUser->email ?: '—' }}</strong></div>
            </div>
            <div class="collapse" id="profileUserForm">
                <hr>
                <form class="row g-3 investee-ajax-form" action="{{ route('profile.user.update') }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="col-md-6"><label class="form-label">نام و نام خانوادگی</label><input class="form-control" name="name" value="{{ $profileUser->name }}" required></div>
                    <div class="col-md-6"><label class="form-label">نام پدر</label><input class="form-control" name="father_name" value="{{ $profileUser->father_name }}"></div>
                    <div class="col-md-6"><label class="form-label">کد ملی</label><input class="form-control" name="national_id" value="{{ $profileUser->national_id }}"></div>
                    <div class="col-md-6"><label class="form-label">موبایل</label><input class="form-control" name="phone" value="{{ $profileUser->phone }}" required></div>
                    <div class="col-md-6"><label class="form-label">ایمیل</label><input type="email" class="form-control" name="email" value="{{ $profileUser->email }}" required></div>
                    <div class="col-md-6"><label class="form-label">جنسیت</label><select class="form-select" name="gender"><option value="">انتخاب کنید</option><option value="1" @selected((int)$profileUser->gender===1)>مرد</option><option value="2" @selected((int)$profileUser->gender===2)>زن</option></select></div>
                    <div class="col-md-6"><label class="form-label">کد پستی</label><input class="form-control" name="postalcode" value="{{ $profileUser->postalcode }}"></div>
                    <div class="col-12"><label class="form-label">آدرس</label><textarea class="form-control" name="address" rows="2">{{ $profileUser->address }}</textarea></div>
                    <div class="col-12 text-end"><button class="btn btn-primary" type="submit">ذخیره تغییرات</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
