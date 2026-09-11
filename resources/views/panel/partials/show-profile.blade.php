<div class="company-profile-content"
     data-company-profile-name="{{ $project->company?->company_name ?: ($project->company_name ?: $project->title) }}">
<!-- Nav tabs -->
<ul class="nav nav-tabs" id="companyTabs{{ $project->id }}" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="profilecompany-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-profilecompany{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-profilecompany{{ $project->id }}" aria-selected="true">
            اطلاعات شرکت
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="profileproject-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-profileproject{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-profileproject{{ $project->id }}" aria-selected="true">
            اطلاعات طرح
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="members-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-members{{ $project->id }}" type="button" role="tab"
                aria-controls="tab-members{{ $project->id }}" aria-selected="false">
            اعضای تیم
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="investment-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-investment{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-investment{{ $project->id }}" aria-selected="false">
            سرمایه‌گذاری
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="payments-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-payments{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-payments{{ $project->id }}" aria-selected="false">
            پرداخت‌ها
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="kpi-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-kpi{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-kpi{{ $project->id }}" aria-selected="false">
            KPI
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="commitment-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-commitment{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-commitment{{ $project->id }}" aria-selected="false">
            تعهدات و تضامین
        </button>
    </li>
    {{--    <li class="nav-item" role="presentation">--}}
    {{--        <button class="nav-link" id="guaranty-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-guaranty{{ $project->id }}"--}}
    {{--                type="button" role="tab" aria-controls="tab-guaranty{{ $project->id }}" aria-selected="false">--}}
    {{--            تضامین--}}
    {{--        </button>--}}
    {{--    </li>--}}
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="workflow-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-workflow{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-workflow{{ $project->id }}" aria-selected="false">
            گردش کار
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="message-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-message{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-message{{ $project->id }}" aria-selected="false">
            پیام ها
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="file-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-file{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-file{{ $project->id }}" aria-selected="false">
            فایل ها
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="report-tab{{ $project->id }}" data-bs-toggle="tab"
                data-bs-target="#tab-report{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-report{{ $project->id }}" aria-selected="false">
            گزارشات
        </button>
    </li>
</ul>
<!-- Tab Content -->
<div class="tab-content mt-3" id="companyTabsContent{{ $project->id }}">
    <!-- Profile Tab -->
    <div class="tab-pane fade show active" id="tab-profilecompany{{ $project->id }}" role="tabpanel"
         aria-labelledby="profilecompany-tab{{ $project->id }}">

        @if($project->logo)
            <div class="text-center mb-3">
                <img src="{{ asset('storage/'.$project->logo) }}"
                     class="lazy rounded-circle" width="80" height="80" alt="لوگو">
            </div>
        @endif

        <div style="overflow-x: auto;">
            <table class="table table-bordered table-striped"
                   style="table-layout: fixed; width: 100%; word-wrap: break-word; white-space: normal;">
                <tbody>
                <tr><th style="width: 30%;">نام شرکت</th><td>{{ $project->company?->company_name ?? $project->company_name ?: '—' }}</td></tr>
                <tr><th>نام تجاری</th><td>{{ $project->company?->commercial_name ?: '—' }}</td></tr>
                <tr><th>مدیرعامل</th><td>{{ $project->company?->ceo_name ?? $project->CEO ?: '—' }}</td></tr>
                <tr><th>تلفن</th><td>{{ $project->company?->phone ?? $project->tel ?: '—' }}</td></tr>
                <tr><th>شناسه ملی</th><td>{{ $project->company?->national_id ?? $project->national_id ?: '—' }}</td></tr>
                <tr><th>شماره ثبت</th><td>{{ $project->company?->registration_number ?? $project->registration_number ?: '—' }}</td></tr>
                <tr><th>تاریخ ثبت</th><td>{{ $project->company?->registration_date ?? $project->registration_date ?: '—' }}</td></tr>
                <tr><th>نوع حقوقی</th><td>{{ $project->company?->legal_type ?? $project->legal_type ?: '—' }}</td></tr>
                <tr><th>وب‌سایت</th><td>{{ $project->company?->website ?? $project->website ?: '—' }}</td></tr>
                <tr><th>نشانی</th><td>{{ $project->company?->address ?? $project->address ?: '—' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Profile Tab -->
    <div class="tab-pane fade" id="tab-profileproject{{ $project->id }}" role="tabpanel"
         aria-labelledby="profileproject-tab{{ $project->id }}">
        @if($project->logo)
            <div class="text-center mb-3">
                <img src="{{ asset('storage/'.$project->logo) }}"
                     class="lazy rounded-circle" width="80" height="80" alt="لوگو">
            </div>
        @endif

        <div style="overflow-x: auto;">
            <table class="table table-bordered table-striped"
                   style="table-layout: fixed; width: 100%; word-wrap: break-word; white-space: normal;">
                <tbody>
                <tr><th style="width: 30%;">عنوان طرح</th><td>{{ $project->title }}</td></tr>
                <tr><th>معرفی طرح</th><td>{{ $project->description ?: '—' }}</td></tr>
                <tr><th>مدیرعامل</th><td>{{ $project->CEO ?: '—' }}</td></tr>
                <tr><th>شماره تماس مدیرعامل</th><td>{{ $project->ceo_phone ?: $project->tel ?: '—' }}</td></tr>
                <tr><th>وضعیت پورتفو</th><td>{{ $project->portfo_status ?: '—' }}</td></tr>
                <tr><th>وضعیت فعالیت</th><td>{{ $project->activity_status ?: '—' }}</td></tr>
                <tr><th>درصد سهام دریافتی</th><td>{{ $project->percentageshare !== null ? $project->percentageshare.'%' : '—' }}</td></tr>
                <tr><th>تاریخ شروع قرارداد</th><td>{{ $project->start_date ?: '—' }}</td></tr>
                <tr><th>مبلغ درخواستی تأییدشده</th><td>{{ number_format((float) ($project->amount_request_accept ?? 0)) }} ریال</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Members Tab -->
    <div class="tab-pane fade" id="tab-members{{ $project->id }}" role="tabpanel" aria-labelledby="members-tab{{ $project->id }}">
        @include('panel.partials.project-members')
    </div>
    <!-- investment Tab -->
    <div class="tab-pane fade" id="tab-investment{{ $project->id }}" role="tabpanel"
         aria-labelledby="investment-tab{{ $project->id }}">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h6 class="mb-1 fw-bold">عوامل فرایند سرمایه‌گذاری</h6>
                    <small class="text-muted">تخصیص کارشناس، ناظر و ارزیاب به کل پروژه یا یک مرحله مشخص</small>
                </div>
                <span class="badge bg-label-primary">{{ $projectAssignments->where('is_active', true)->count() }} تخصیص فعال</span>
            </div>
            <div class="card-body">
                @if($canManageAssignments)
                    <form action="{{ route('flow.assignments.store', $project->id) }}" method="POST"
                          class="project-assignment-form row g-3 align-items-end mb-4"
                          data-project-id="{{ $project->id }}">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label">نقش عملیاتی</label>
                            <select name="role_id" class="form-select assignment-role-select" required>
                                <option value="">انتخاب نقش</option>
                                @foreach($assignableRoles as $assignmentRole)
                                    <option value="{{ $assignmentRole->id }}">{{ $assignmentRole->title_fa }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">کاربر</label>
                            <select name="user_id" class="form-select assignment-user-select" required>
                                <option value="">انتخاب کاربر</option>
                                @foreach($assignableUsers as $assignmentUser)
                                    <option value="{{ $assignmentUser->id }}"
                                            data-role-ids="{{ $assignmentUser->roles->pluck('id')->implode(',') }}">
                                        {{ $assignmentUser->name }}{{ $assignmentUser->email ? ' - '.$assignmentUser->email : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">محدوده تخصیص</label>
                            <select name="invest_step_id" class="form-select">
                                <option value="">کل پروژه</option>
                                @foreach($investsteps as $assignmentStep)
                                    <option value="{{ $assignmentStep->id }}" @selected((int) $project->invest_step === (int) $assignmentStep->id)>
                                        مرحله {{ $assignmentStep->id }} - {{ $assignmentStep->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">یادداشت</label>
                            <input type="text" name="notes" class="form-control" maxlength="2000" placeholder="اختیاری">
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary assignment-submit-btn">
                                <i class="mdi mdi-account-plus-outline me-1"></i> ثبت تخصیص
                            </button>
                        </div>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>کاربر</th>
                            <th>نقش</th>
                            <th>محدوده</th>
                            <th>تخصیص‌دهنده</th>
                            <th>وضعیت</th>
                            @if($canManageAssignments)<th class="text-end">عملیات</th>@endif
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($projectAssignments as $assignment)
                            <tr>
                                <td>{{ $assignment->user?->name ?? '—' }}</td>
                                <td>{{ $assignment->role?->title_fa ?? '—' }}</td>
                                <td>{{ $assignment->investStep?->title ?? 'کل پروژه' }}</td>
                                <td>{{ $assignment->assignedBy?->name ?? '—' }}</td>
                                <td>
                                    @if($assignment->is_active)
                                        <span class="badge bg-label-success">فعال</span>
                                    @else
                                        <span class="badge bg-label-secondary">پایان یافته</span>
                                    @endif
                                </td>
                                @if($canManageAssignments)
                                    <td class="text-end">
                                        @if($assignment->is_active)
                                            <form action="{{ route('flow.assignments.destroy', [$project->id, $assignment->id]) }}"
                                                  method="POST" class="assignment-end-form d-inline"
                                                  data-project-id="{{ $project->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">پایان تخصیص</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canManageAssignments ? 6 : 5 }}" class="text-center text-muted py-3">هنوز تخصیص عملیاتی ثبت نشده است.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="accordion" id="projectStepsAccordion{{ $project->id }}">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="list-group shadow-sm rounded" style="overflow-y:auto; max-height:620px;">
                        @foreach($investsteps as $step)
                            <div
                                class="list-group-item d-flex align-items-center py-2 {{ $step->id === ($project->invest_step) ? 'active' : '' }}"
                                style="cursor: default; border-right: 5px solid {{ $step->id < $project->invest_step ? '#4caf50' : ($step->id === $project->invest_step ? '#7367f0' : '#ddd') }};">
                                <span
                                    class="me-2 d-inline-flex justify-content-center align-items-center rounded-circle"
                                    style="width: 28px; height: 28px; background: {{ $step->id < $project->invest_step ? '#c8e6c9' : ($step->id === $project->invest_step ? '#ede7f6' : '#f1f1f1') }}; color: {{ $step->id < $project->invest_step ? '#2e7d32' : ($step->id === $project->invest_step ? '#5e35b1' : '#aaa') }}; font-weight: bold;">
                                    {{ $step->id }}
                                </span>
                                <div class="flex-grow-1">
                                    <div
                                        class="fw-bold {{ $step->id === $project->invest_step ? 'text-dark' : 'text-muted' }}">{{ $step->title }}</div>
                                    <small class="text-muted">{{ $step->description }}</small>
                                    <div class="small text-muted mt-1">وزن: {{ rtrim(rtrim(number_format((float) $step->weight, 3, '.', ''), '0'), '.') }}</div>
                                </div>
                                @if($step->id === $project->invest_step)
                                    <span class="badge bg-primary ms-auto">اکنون</span>
                                @elseif($step->id < $project->invest_step)
                                    <i class="mdi mdi-check-circle-outline text-success ms-auto"></i>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @foreach($investsteps as $step)
                    @if($project->invest_step === $step->id)
                        @php
                            $isRejectedStep = $project->is_rejected == 1 && $project->reject_step == $step->id;
                        @endphp
                        <div class="col-md-8">
                            <div class="card border shadow-sm">
                                <div class="card-header d-flex align-items-center border-1">
                                    <span class="badge bg-primary me-2"
                                          style="width:26px;">{{ $project->invest_step }}</span>
                                    <h6 class="mb-0 fw-bold">{{ $step->title }}</h6>
                                </div>
                                <div class="card-body"
                                     data-rejected="{{ $isRejectedStep ? '1' : '0' }}"
                                     data-workflow-locked="{{ (!$canTransitionCurrentStep || $isRejectedStep) ? '1' : '0' }}">
                                    <p class="text-muted">{{ $step->description }}</p>
                                    @if($isRejectedStep)
                                        <div class="alert alert-danger mb-3" role="alert">
                                            این مرحله رد شده است؛ امکان اقدام مجدد وجود ندارد.
                                        </div>
                                    @elseif(!$canTransitionCurrentStep)
                                        <div class="alert alert-warning mb-3" role="alert">
                                            برای ثبت تصمیم در مرحله جاری، تخصیص فعال و مجاز ندارید.
                                        </div>
                                    @endif
                                    @include('panel.partials.step-document-requirements')
                                    @if($step->id == 1)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [4]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}">
                                                        <span>فایل <a href="{{ $file->url }}"
                                                                      target="_blank"> {{$file->original_name}} </a> در تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده -</span>
                                                        <div class="record-actions">
                                                            <button class="send-btn btn btn-primary"
                                                                    data-id="{{ $file->id }}" data-status="4">تایید
                                                            </button>
                                                            <button class="send-btn btn btn-delete"
                                                                    data-id="{{ $file->id }}" data-status="5">رد
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <form action="{{ route('flow.store') }}" method="POST" class="flow-form w-100">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <div class="flow-action-box p-3 p-md-4 mb-3">
                                                <div
                                                    class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                                                    <div class="flow-action-meta">
                                                        <span
                                                            class="badge bg-primary text-white px-3 py-2 rounded-pill">مرحله {{ $step->id }}</span>
                                                        <span class="d-inline-flex align-items-center gap-1">
                                                                <i class="mdi mdi-file-document-edit-outline text-primary"></i>
                                                                ثبت نظر و تصمیم
                                                            </span>
                                                    </div>
                                                    <span class="badge bg-label-secondary text-dark">فرآیند فعال</span>
                                                </div>

                                                <label for="description-step-{{ $step->id }}"
                                                       class="form-label small text-muted mb-2">توضیحات
                                                    (اختیاری)</label>
                                                <textarea id="description-step-{{ $step->id }}" name="description"
                                                          class="form-control soft-input mb-3"
                                                          rows="4" {{ $isRejectedStep ? 'disabled' : '' }}></textarea>

                                                <div class="d-flex gap-2 flex-wrap justify-content-center text-center">
                                                    <button type="button"
                                                            class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                            style="min-width:170px;" {{ $isRejectedStep ? 'disabled' : '' }}>
                                                        <i class="mdi mdi-check-circle-outline"></i>
                                                        تایید مرحله
                                                    </button>

                                                    <button type="button"
                                                            class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                            style="min-width:170px;" {{ $isRejectedStep ? 'disabled' : '' }}>
                                                        <i class="mdi mdi-close-circle-outline"></i>
                                                        رد مرحله
                                                    </button>
                                                </div>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 2)
                                        <form action="{{ route('flow.store') }}" method="POST" class="flow-form">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-2" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center mt-4">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>

                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>

                                    @elseif($step->id == 3)
                                        <div class="alert alert-warning text-center"><a
                                                href="{{asset('storage/uploads/sinavc/Screening.docx')}}"
                                                target="_blank" }}> قالب فایل غربالگری جهت بارگذاری </a></div>
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [1]) && $file->project_id == $project->id)
                                                <div class="alert alert-info record-box" id="record-{{ $file->id }}">
                                                    فایل <a href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                    تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده -
                                                </div>
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="1" data-title="غربالگری">
                                            <i class="mdi mdi-file-document-multiple-outline"></i>
                                            غربالگری
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST" class="flow-form">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>

                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 4)
                                        <div class="alert alert-warning text-center"><a
                                                href="{{asset('storage/uploads/sinavc/Screening.docx')}}"
                                                target="_blank" }}> قالب فایل ارزیابی اولیه جهت بارگذاری </a></div>
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [2]) && $file->project_id == $project->id)
                                                <div class="alert alert-info record-box" id="record-{{ $file->id }}">
                                                    فایل <a href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                    تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده -
                                                </div>
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="2" data-title="ارزیابی اولیه">
                                            <i class="mdi mdi-file-document-multiple-outline"></i>
                                            ارزیابی اولیه
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST" class="flow-form">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>

                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 5)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [29,30]) && $file->project_id == $project->id)
                                                <div class="alert alert-info record-box" id="record-{{ $file->id }}">
                                                    فایل <a href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                    تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده -
                                                </div>
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="29" data-title="صورتجلسه کمیته ریسک">
                                            <i class="mdi mdi-file-document-multiple-outline"></i>
                                            صورتجلسه کمیته ریسک
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="30" data-title="صورتجلسه کمیته سرمایه گذاری">
                                            <i class="mdi mdi-file-document-multiple-outline"></i>
                                            صورتجلسه کمیته سرمایه گذاری
                                        </button>
                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-2" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>

                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 6)
                                        <div class="alert alert-warning text-center"><a
                                                href="{{asset('storage/uploads/sinavc/Screening.docx')}}"
                                                target="_blank" }}> قالب فایل ارزیابی موشکافانه جهت بارگذاری </a></div>
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [3,6,7,8,9,10,11,12,13,14,15,16]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="3" data-title="ارزیابی موشکافانه">
                                            <i class="mdi mdi-file-document-multiple-outline"></i>
                                            ارزیابی موشکافانه
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 7)

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>
                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 8)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [25]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="25" data-title="کاربرگ تایید سرمایه پذیر">
                                            <i class="mdi mdi-file-document-multiple-outline"></i>
                                            کاربرگ تایید سرمایه پذیر
                                        </button>
                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>
                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 9)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [19,31,32,33]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="31" data-title="قرارداد با شرکت ارزش گذار"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>قرارداد با شرکت ارزش
                                            گذار
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="32" data-title="فاکتور ارسالی ارزش گذار"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>فاکتور ارسالی ارزش
                                            گذار
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="33" data-title="نسخ گزارش ارزش گذاری"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>نسخ گزارش ارزش گذاری
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>
                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 10)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [27,30,34]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="27" data-title="صورتجلسه کمیته ارزش گذاری"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>صورتجلسه کمیته ارزش
                                            گذاری
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="34" data-title="فایل ارائه گزارش ارزش گذاری"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>فایل ارائه گزارش ارزش
                                            گذاری
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>

                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 11)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [38]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="38" data-title="فایل صورتجلسه هیئت مدیره"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>فایل صورتجلسه هیئت
                                            مدیره
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>

                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 12)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [28 , 36]) && $file->project_id == $project->id)
                                                <div class="alert alert-info record-box" id="record-{{ $file->id }}">
                                                    فایل <a href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                    تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده -
                                                </div>
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="36" data-title="راستی آزمایی حقوقی"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>راستی آزمایی حقوقی
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="28" data-title="نسخ قرارداد"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>نسخ قرارداد
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 13)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [20]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="20" data-title="قرارداد نهایی"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>قرارداد نهایی
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-check-circle-outline"></i>


                                                    تایید مرحله


                                                </button>


                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-close-circle-outline"></i>


                                                    رد مرحله


                                                </button>


                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 14)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [18,21]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="21" data-title="مستندات شاخص کلیدی اول"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>مستندات شاخص کلیدی
                                            اول
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="18" data-title="صورتجلسات"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>صورتجلسات
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">
                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>


                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 15)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [18,22]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="22" data-title="مستندات شاخص کلیدی دوم"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>مستندات شاخص کلیدی
                                            دوم
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="18" data-title="صورتجلسات"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>صورتجلسات
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">

                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-check-circle-outline"></i>
                                                    تایید مرحله
                                                </button>
                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">
                                                    <i class="mdi mdi-close-circle-outline"></i>
                                                    رد مرحله
                                                </button>
                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 16)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [18,23]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="23" data-title="مستندات شاخص کلیدی سوم"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>مستندات شاخص کلیدی
                                            سوم
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="18" data-title="صورتجلسات"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>صورتجلسات
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-check-circle-outline"></i>


                                                    تایید مرحله


                                                </button>


                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-close-circle-outline"></i>


                                                    رد مرحله


                                                </button>


                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 17)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [18,24]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="24" data-title="مستندات شاخص کلیدی چهارم و سایر"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>مستندات شاخص کلیدی
                                            چهارم و سایر
                                        </button>
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="18" data-title="صورتجلسات"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>صورتجلسات
                                        </button>

                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-check-circle-outline"></i>


                                                    تایید مرحله


                                                </button>


                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-close-circle-outline"></i>


                                                    رد مرحله


                                                </button>


                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 18)
                                        @foreach($files as $file)
                                            @if(in_array($file->subject_id, [37]) && $file->project_id == $project->id)
                                                @if($file->status  == 4)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <span style="color: green; font-weight: bold;">✔ تایید شد</span>
                                                    </div>
                                                @elseif($file->status <> 5)
                                                    <div class="alert alert-info record-box"
                                                         id="record-{{ $file->id }}"> فایل <a
                                                            href="{{ $file->url }}"
                                                            target="_blank"> {{$file->original_name}} </a> در
                                                        تاریخ {{jdate($file->created_at)->format('d-m-Y')}} بارگذاری شده
                                                        -
                                                        <button class="send-btn btn btn-primary"
                                                                data-id="{{ $file->id }}" data-status="4">تایید
                                                        </button>
                                                        <button class="send-btn btn btn-delete"
                                                                data-id="{{ $file->id }}" data-status="5">رد
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                        <button class="btn upload-btn upload-wide" data-id="{{$project->id}}"
                                                data-subject="37" data-title="گزارش عملکرد"><i
                                                class="mdi mdi-file-document-multiple-outline"></i>گزارش عملکرد
                                        </button>
                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-check-circle-outline"></i>


                                                    تایید مرحله


                                                </button>


                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-close-circle-outline"></i>


                                                    رد مرحله


                                                </button>


                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 19)
                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-check-circle-outline"></i>


                                                    تایید مرحله


                                                </button>


                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-close-circle-outline"></i>


                                                    رد مرحله


                                                </button>


                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @elseif($step->id == 20)
                                        <form action="{{ route('flow.store') }}" method="POST"
                                              class="flow-form d-inline">
                                            @csrf
                                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                                            <input type="hidden" name="status" class="status-input">

                                            <textarea name="description" class="form-control mb-4" rows="4"></textarea>

                                            <div class="d-flex gap-2 flex-wrap justify-content-center">


                                                <button type="button"
                                                        class="btn btn-success approve-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-check-circle-outline"></i>


                                                    تایید مرحله


                                                </button>


                                                <button type="button"
                                                        class="btn btn-danger reject-btn d-flex align-items-center gap-1 px-3"
                                                        style="min-width:170px;">


                                                    <i class="mdi mdi-close-circle-outline"></i>


                                                    رد مرحله


                                                </button>


                                            </div>

                                            <button type="submit" class="d-none real-submit"></button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    <!-- Payments Tab  -->
    <div class="tab-pane fade" id="tab-payments{{ $project->id }}" role="tabpanel"
         aria-labelledby="payments-tab{{ $project->id }}">
        <table class="table table-bordered mt-2">
            <thead>
            <tr>
                <th>مبلغ</th>
                <th>شماره قسط</th>
                <th>تاریخ پرداخت</th>
            </tr>
            </thead>
            <tbody>
            @php
                $total = 0;
            @endphp
            @foreach($finances as $payment)
                @if($payment->project_id == $project->id)
                    @php
                        $total += $payment->amount;
                    @endphp
                    <tr>
                        <td>{{ number_format($payment->amount) }} ریال</td>
                        <td>{{ $payment->serial }}</td>
                        <td>{{ $payment->date }}</td>
                    </tr>
                @endif
            @endforeach
            <tr style="font-weight: bold;">
                <td>{{ number_format($total) }} ریال</td>
                <td>جمع</td>
                <td></td>
            </tr>
            </tbody>
        </table>
    </div>
    <!-- KPI Tab -->
    <div class="tab-pane fade" id="tab-kpi{{ $project->id }}" role="tabpanel" aria-labelledby="kpi-tab{{ $project->id }}">
        @include('panel.partials.project-kpis')
    </div>
    <!-- Commitment Tab -->
    <div class="tab-pane fade" id="tab-commitment{{ $project->id }}" role="tabpanel"
         aria-labelledby="commitment-tab{{ $project->id }}">
        @include('panel.partials.project-commitments')
    </div>
    <!-- Guaranty Tab -->
    <div class="tab-pane fade" id="tab-guaranty{{ $project->id }}" role="tabpanel"
         aria-labelledby="guaranty-tab{{ $project->id }}">
        <table class="table table-bordered mt-2">

        </table>
    </div>
    <!-- Workflow Tab -->
    <div class="tab-pane fade" id="tab-workflow{{ $project->id }}" role="tabpanel"
        aria-labelledby="workflow-tab{{ $project->id }}">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="mb-1">تاریخچه تأیید و رد مراحل</h6>
                    <div class="text-muted small">کاربر تصمیم‌گیرنده، زمان تصمیم و توضیحات هر مرحله</div>
                </div>
                <span class="badge bg-label-primary">{{ $project_steps->count() }} تصمیم</span>
            </div>
            <div class="row g-3" data-workflow-decision-history>
                @forelse ($project_steps as $step)
                    @php
                        $bg = $step->status === 'approved' ? '#e8f5e9' : ($step->status === 'rejected' ? '#ffebee' : '#f8f9fa');
                        $border = $step->status === 'approved' ? '#4caf50' : ($step->status === 'rejected' ? '#f44336' : '#9e9e9e');
                        $statusLabel = $step->status === 'approved' ? 'تایید شده' : ($step->status === 'rejected' ? 'رد شده' : 'در انتظار');
                        $statusBadgeClass = $step->status === 'approved' ? 'bg-success' : ($step->status === 'rejected' ? 'bg-danger' : 'bg-secondary');
                        $decisionTime = $step->decided_at ?? $step->created_at;
                        $time = $decisionTime ? (function($d){ try { return jdate($d)->format('Y/m/d H:i'); } catch(\Throwable $e) { return $d->format('Y/m/d H:i'); } })($decisionTime) : '—';
                    @endphp

                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100 shadow-sm" role="article" aria-label="مرحله {{ $step->step_number }}"
                             style="background-color: {{ $bg }}; border-left: 6px solid {{ $border }};">
                            <div class="card-header d-flex align-items-center justify-content-between py-2">
                                <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill text-dark"
                                  style="background: rgba(0,0,0,0.05); min-width:36px; height:36px; display:inline-flex; align-items:center; justify-content:center; font-weight:600;">
                                {{ $step->step_number }}
                            </span>
                                    <h6 class="mb-0 text-truncate" style="max-width: 160px;">{{ $step->title }}</h6>
                                </div>
                            </div>
                            <small class="text-nowrap">
                                <span class="badge {{ $statusBadgeClass }} text-white">{{ $statusLabel }}</span>
                            </small>
                            <div class="card-body d-flex flex-column">
                                <div class="card-text mb-2 small" style="flex:0 0 auto; max-height:90px; overflow:auto;">
                                    <span class="text-muted">توضیحات:</span>
                                    <span>{{ filled($step->description) ? $step->description : 'بدون توضیح' }}</span>
                                </div>

                                <div class="mt-auto">
                                    <ul class="list-unstyled mb-0 small text-secondary">
                                        <li class="d-flex align-items-center gap-2">
                                            <i class="bi bi-person-fill" aria-hidden="true"></i>
                                            <span class="text-truncate" style="max-width: 180px;">
                                                {{ $step->username ?? ($step->user->name ?? 'کارشناس') }}
                                                @if(!empty($step->actor_role_title))
                                                    <span class="text-muted">({{ $step->actor_role_title }})</span>
                                                @endif
                                            </span>
                                        </li>

                                        <li class="d-flex align-items-center gap-2 mt-1">
                                            <i class="bi bi-clock-fill" aria-hidden="true"></i>
                                            <span>{{ $time }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><div class="alert alert-light border text-muted mb-0">هنوز تصمیمی برای مراحل این طرح ثبت نشده است.</div></div>
                @endforelse
            </div>
        </div>

        <!-- اختیاری: فعال‌سازی tooltip های بوت‌استرپ اگر از آن استفاده می‌کنید -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (el) {
                    new bootstrap.Tooltip(el);
                });

                // فرم تصمیم‌گیری باید با Policy سمت سرور هم‌راستا باشد.
                document.querySelectorAll('[data-workflow-locked="1"]').forEach(function (container) {
                    container.querySelectorAll('.flow-form textarea, .flow-form button').forEach(function (ctrl) {
                        ctrl.setAttribute('disabled', 'disabled');
                        ctrl.classList.add('disabled');
                    });
                });
            });
        </script>

        <style>
            /* ظاهرسازی کمکی برای کارت‌ها */
            .card .text-truncate {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            @media (max-width: 575.98px) {
                .card-header h6 {
                    max-width: 110px;
                }
            }

            /* پنهان‌سازی دکمه‌های تأیید/رد در مرحله‌ی رد شده */
            [data-rejected="1"] .approve-btn,
            [data-rejected="1"] .reject-btn {
                display: none !important;
            }

            /* استایل فرم مرحله */
            .flow-action-box {
                background: linear-gradient(135deg, #f9fafb 0%, #f4f6fb 50%, #f9fafb 100%);
                border: 1px solid rgba(0, 0, 0, .06);
                border-radius: 12px;
                box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
            }

            .flow-action-meta {
                display: flex;
                align-items: center;
                gap: 10px;
                color: #6b7280;
                font-size: 13px;
            }
        </style>


    </div>
    <!-- Message Tab -->
    <div class="tab-pane fade" id="tab-message{{ $project->id }}" role="tabpanel"
         aria-labelledby="message-tab{{ $project->id }}">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="conversations" class="table table-striped table-bordered yajra-datatable">
                        <thead>
                        <tr class="table-light">
                            <th>شماره پیام</th>
                            <th>موضوع پیام</th>
                            <th>متن پیام</th>
                            <th>فایل ضمیمه</th>
                            <th>ارسال کننده</th>
                            <th>تاریخ پیام</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            $(function () {
                var table = $('#conversations.yajra-datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    order: [[0, 'desc']],
                    scrollX: true,
                    scrollCollapse: true,
                    ajax: "{{ route('correspondence.show', $project->id) }}",
                    columns: [
                        {data: 'id'         , name: 'id' ?? ''},
                        {data: 'subject'    , name: 'subject' ?? ''},
                        {data: 'body'       , name: 'body' ?? ''},
                        {data: 'file_path'  , name: 'file_path' ?? ''},
                        {data: 'user'       , name: 'user' ?? ''},
                        {data: 'date'       , name: 'date' ?? ''},
                    ],
                    language: {
                        url: "{{asset('assets/vendor/js/fa.json')}}"
                    }
                });
            });
        </script>
    </div>
    <!-- file Tab -->
    <div class="tab-pane fade" id="tab-file{{ $project->id }}" role="tabpanel"
         aria-labelledby="file-tab{{ $project->id }}">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="file" class="table table-striped table-bordered yajra-datatable">
                        <thead>
                        <tr class="table-light">
                            <th>فایل</th>
                            <th>نام فایل</th>
                            <th>نوع فایل</th>
                            <th>مرحله</th>
                            <th>سایز فایل</th>
                            <th>تاریخ آپلود</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            $(function () {
                var table = $('#file.yajra-datatable').DataTable({
                    processing: true,
                    serverSide: true,
                    order: [[0, 'desc']],
                    scrollX: true,
                    scrollCollapse: true,
                    ajax: "{{ route('filemanager.show', $project->id) }}",
                    columns: [
                        {data: 'file_path', name: 'file_path' ?? ''},
                        {data: 'name', name: 'name' ?? ''},
                        {data: 'type', name: 'type' ?? ''},
                        {data: 'step', name: 'step' ?? ''},
                        {data: 'size', name: 'size' ?? ''},
                        {data: 'date', name: 'date' ?? ''},
                    ],
                    language: {
                        url: "{{asset('assets/vendor/js/fa.json')}}"
                    }
                });
            });
        </script>
    </div>
    <!-- report Tab -->
    <div class="tab-pane fade" id="tab-report{{ $project->id }}" role="tabpanel" aria-labelledby="report-tab{{ $project->id }}">
        @include('panel.partials.project-financial-report')
    </div>
</div>
</div>
