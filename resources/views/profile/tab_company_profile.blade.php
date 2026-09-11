<div class="tab-pane fade" id="navs-company-card" role="tabpanel">
    <div class="card border-0 shadow-sm">
        <div class="card-body text-start">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h5 class="mb-1">اطلاعات شرکت و طرح</h5><small class="text-muted">این اطلاعات متعلق به پرونده جاری شما است.</small></div>
                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#investeeCompanyForm">ویرایش</button>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><span class="text-muted">عنوان طرح:</span> <strong>{{ $project->title ?: '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">نام شرکت:</span> <strong>{{ $project->company_name ?: '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">شناسه ملی:</span> <strong>{{ $project->national_id ?: '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">شماره ثبت:</span> <strong>{{ $project->registration_number ?: '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">تلفن:</span> <strong>{{ $project->tel ?: '—' }}</strong></div>
                <div class="col-md-4"><span class="text-muted">وب‌سایت:</span> <strong>{{ $project->website ?: '—' }}</strong></div>
            </div>
            <div class="collapse mt-4" id="investeeCompanyForm">
                <hr>
                <form class="row g-3 investee-ajax-form" action="{{ route('profile.company-project.update') }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="col-md-6"><label class="form-label">عنوان طرح</label><input class="form-control" name="title" value="{{ $project->title }}" required></div>
                    <div class="col-md-6"><label class="form-label">نام شرکت</label><input class="form-control" name="company_name" value="{{ $project->company_name }}" required></div>
                    <div class="col-md-4"><label class="form-label">شماره ثبت</label><input class="form-control" name="registration_number" value="{{ $project->registration_number }}"></div>
                    <div class="col-md-4"><label class="form-label">تاریخ ثبت</label><input class="form-control" data-jdp name="registration_date" value="{{ $project->registration_date }}"></div>
                    <div class="col-md-4"><label class="form-label">شناسه ملی</label><input class="form-control" name="national_id" value="{{ $project->national_id }}"></div>
                    <div class="col-md-4"><label class="form-label">کد اقتصادی</label><input class="form-control" name="economic_code" value="{{ $project->economic_code }}"></div>
                    <div class="col-md-4"><label class="form-label">نوع حقوقی</label><input class="form-control" name="legal_type" value="{{ $project->legal_type }}"></div>
                    <div class="col-md-4"><label class="form-label">تلفن شرکت</label><input class="form-control" name="tel" value="{{ $project->tel }}"></div>
                    <div class="col-md-4"><label class="form-label">ایمیل شرکت</label><input type="email" class="form-control" name="email" value="{{ $project->email }}"></div>
                    <div class="col-md-4"><label class="form-label">وب‌سایت</label><input class="form-control" name="website" value="{{ $project->website }}"></div>
                    <div class="col-md-4"><label class="form-label">کد پستی</label><input class="form-control" name="postal_code" value="{{ $project->postal_code }}"></div>
                    <div class="col-md-6"><label class="form-label">استان</label><select class="form-select" name="state" id="investeeState"><option value="">انتخاب کنید</option>@foreach($states as $state)<option value="{{ $state->id }}" @selected((int)$project->state===(int)$state->id)>{{ $state->title }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">شهر</label><select class="form-select" name="city" id="investeeCity"><option value="">انتخاب کنید</option>@foreach($cities as $city)<option value="{{ $city->id }}" @selected((int)$project->city===(int)$city->id)>{{ $city->title }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">آدرس</label><textarea class="form-control" name="address" rows="2">{{ $project->address }}</textarea></div>
                    <div class="col-12 text-end"><button class="btn btn-primary" type="submit">ذخیره اطلاعات</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
