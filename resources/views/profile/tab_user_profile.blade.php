<div class="tab-pane fade justify-content-center" id="navs-co-profile-card" role="tabpanel">
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMinutesModal">
            <i class="mdi mdi-plus"></i>مدیریت صورتجلسات
        </button>
    </div>
    <div class="modal fade" id="addMinutesModal" tabindex="-1" aria-labelledby="addMinutesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">مدیریت صورتجلسات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div class="card-body">
                    <div class="modal-body">
                        <form id="addminuteform" method="POST" class="row g-4 mb-4" action="{{route('minute.store')}}">
                            @csrf
                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                            <div class="col-12 col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="title" name="title" placeholder="عنوان">
                                    <label for="title">عنوان</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input type="text" class="form-control" id="date" name="date" data-jdp autocomplete="off" placeholder="تاریخ برگزاری">
                                    <label for="date">تاریخ برگزاری</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <select name="type" id="type" class="form-control">
                                        <option value="" selected>انتخاب کنید</option>
                                        <option value="صورتجلسه هیئت مدیره" >صورتجلسه هیئت مدیره</option>
                                    </select>
                                    <label for="type">نوع شرکت</label>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="input-group">
                                    <input type="text" name="file_path" class="form-control" id="file_{{ $project->id }}" readonly placeholder="انتخاب فایل">
                                    <button class="btn btn-outline-secondary file-selector" type="button" data-record-id="{{ $project->id }}" data-input-id="file_{{ $project->id }}">
                                        انتخاب فایل
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 text-center">
                                <button type="button" class="btn btn-primary" id="submitaddminut">ذخیره</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php
        $hasProfile = true;
    @endphp
    <div id="userProfileCard" class="{{ $hasProfile ? '' : 'd-none' }}">
        <div class="card border-0 shadow-sm mb-4" style="max-width:480px; margin:0 auto; border-radius: 1.25rem;">
            <div class="card-body p-4">
{{--                <div class="d-flex align-items-center justify-content-between mb-3">--}}
{{--                    <div class="d-flex align-items-center gap-3">--}}
{{--                        <div class="rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width:56px; height:56px; background:#f2f3f6;">--}}
{{--                            <i class="mdi mdi-domain" style="font-size:2rem; color:#696cff"></i>--}}
{{--                        </div>--}}
{{--                        <div>--}}
{{--                            <div class="fw-bold mb-1" style="font-size:1.2rem;">{{ $project->company_name }}</div>--}}
{{--                            <div class="small text-secondary" dir="ltr" style="font-size:0.95rem;">{{ $project->website }}</div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleEdituserMode()" style="font-size:.98rem">--}}
{{--                        <i class="mdi mdi-pencil-outline"></i>--}}
{{--                        <span class="d-none d-md-inline">ویرایش</span>--}}
{{--                    </button>--}}
{{--                </div>--}}

                <dl class="row g-3 pt-3" style="font-size: 0.96rem;">
                    <div class="col-12 d-flex">
                        <dt class="col-5 text-start text-muted">شماره ثبت:</dt>
                        <dd id="company-registration-number" class="col-7 text-dark mb-0">{{ Auth::user()->name }}</dd>
                    </div>
                    <div class="col-12 d-flex border-top pt-3">
                        <dt class="col-5 text-start text-muted">شناسه ملی:</dt>
                        <dd id="company-national-id" class="col-7 text-dark mb-0">{{ Auth::user()->email }}</dd>
                    </div>
                    <div class="col-12 d-flex border-top pt-3">
                        <dt class="col-5 text-start text-muted">تلفن:</dt>
                        <dd id="company-phone" class="col-7 text-dark mb-0" dir="ltr" style="font-family: monospace">{{ Auth::user()->phone }}</dd>
                    </div>
                    <div class="col-12 d-flex border-top pt-3">
                        <dt class="col-5 text-start text-muted">ایمیل:</dt>
                        <dd id="company-email" class="col-7 text-dark mb-0" dir="ltr" style="font-family: monospace">{{Auth::user()->gender == 1 ? 'مرد' : 'زن' }}</dd>
                    </div>
                    <div class="col-12 d-flex border-top pt-3">
                        <dt class="col-5 text-start text-muted">آدرس:</dt>
                        <dd id="company-address" class="col-7 text-dark mb-0">{{ $project->address }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>



{{--<div class="tab-pane fade justify-content-center" id="navs-co-profile-card" role="tabpanel">--}}
{{--    --}}{{-- کارت اطلاعات شرکت --}}
{{--    <div id="userProfileCard" class="{{ $hasProfile ? '' : 'd-none' }}">--}}
{{--        <div class="card border-0 shadow-sm mb-4" style="max-width:480px; margin:0 auto; border-radius: 1.25rem;">--}}
{{--            <div class="card-body p-4">--}}
{{--                <div class="d-flex align-items-center justify-content-between mb-3">--}}
{{--                    <div class="d-flex align-items-center gap-3">--}}
{{--                        <div class="rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width:56px; height:56px; background:#f2f3f6;">--}}
{{--                            <i class="mdi mdi-domain" style="font-size:2rem; color:#696cff"></i>--}}
{{--                        </div>--}}
{{--                        <div>--}}
{{--                            <div class="fw-bold mb-1" style="font-size:1.2rem;">{{ $project->company_name }}</div>--}}
{{--                            <div class="small text-secondary" dir="ltr" style="font-size:0.95rem;">{{ $project->website }}</div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleEdituserMode()" style="font-size:.98rem">--}}
{{--                        <i class="mdi mdi-pencil-outline"></i>--}}
{{--                        <span class="d-none d-md-inline">ویرایش</span>--}}
{{--                    </button>--}}
{{--                </div>--}}

{{--                <dl class="row g-3 pt-3" style="font-size: 0.96rem;">--}}
{{--                    <div class="col-12 d-flex">--}}
{{--                        <dt class="col-5 text-start text-muted">شماره ثبت:</dt>--}}
{{--                        <dd id="company-registration-number" class="col-7 text-dark mb-0">{{ Auth::user()->name }}</dd>--}}
{{--                    </div>--}}
{{--                    <div class="col-12 d-flex border-top pt-3">--}}
{{--                        <dt class="col-5 text-start text-muted">شناسه ملی:</dt>--}}
{{--                        <dd id="company-national-id" class="col-7 text-dark mb-0">{{ Auth::user()->email }}</dd>--}}
{{--                    </div>--}}
{{--                    <div class="col-12 d-flex border-top pt-3">--}}
{{--                        <dt class="col-5 text-start text-muted">تلفن:</dt>--}}
{{--                        <dd id="company-phone" class="col-7 text-dark mb-0" dir="ltr" style="font-family: monospace">{{ Auth::user()->phone }}</dd>--}}
{{--                    </div>--}}
{{--                    <div class="col-12 d-flex border-top pt-3">--}}
{{--                        <dt class="col-5 text-start text-muted">ایمیل:</dt>--}}
{{--                        <dd id="company-email" class="col-7 text-dark mb-0" dir="ltr" style="font-family: monospace">{{Auth::user()->gender == 1 ? 'مرد' : 'زن' }}</dd>--}}
{{--                    </div>--}}
{{--                    <div class="col-12 d-flex border-top pt-3">--}}
{{--                        <dt class="col-5 text-start text-muted">آدرس:</dt>--}}
{{--                        <dd id="company-address" class="col-7 text-dark mb-0">{{ $project->address }}</dd>--}}
{{--                    </div>--}}
{{--                </dl>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}

{{--    --}}{{-- فرم ویرایش اطلاعات شرکت --}}
{{--    <div id="userEditForm" class="{{ $hasProfile ? 'd-none' : '' }}">--}}
{{--        @include('profile.user_profile_form', ['profile' => Auth::user()->id])--}}
{{--    </div>--}}
{{--</div>--}}
