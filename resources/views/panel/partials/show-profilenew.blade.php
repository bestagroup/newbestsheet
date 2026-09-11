<!-- Nav tabs -->
<ul class="nav nav-tabs" id="companyTabs{{ $project->id }}" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="profilecompany-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-profilecompany{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-profilecompany{{ $project->id }}" aria-selected="true">
            اطلاعات شرکت
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="profileproject-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-profileproject{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-profileproject{{ $project->id }}" aria-selected="true">
            اطلاعات طرح
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
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="workflow-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-workflow{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-workflow{{ $project->id }}" aria-selected="false">
            گردش کار
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="message-tab{{ $project->id }}" data-bs-toggle="tab" data-bs-target="#tab-message{{ $project->id }}"
                type="button" role="tab" aria-controls="tab-message{{ $project->id }}" aria-selected="false">
            پیام ها
        </button>
    </li>
</ul>
<!-- Tab Content -->
<div class="tab-content mt-3" id="companyTabsContent{{ $project->id }}">
    <!-- Profile Tab -->
    <div class="tab-pane fade show active" id="tab-profilecompany{{ $project->id }}" role="tabpanel" aria-labelledby="profilecompany-tab{{ $project->id }}">

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
                <tr>
                    <th style="width: 30%;">نام شرکت</th>
                    <td>{{ $project->company_name }}</td>
                </tr>
                <tr>
                    <th>معرفی شرکت</th>
                    <td>{{ $project->description }}</td>
                </tr>
                <tr>
                    <th>مدیرعامل</th>
                    <td>{{ $project->CEO }}</td>
                </tr>
                <tr>
                    <th>شماره موبایل</th>
                    <td>{{ $project->ceo_phone }}</td>
                </tr>
                <tr>
                    <th>وضعیت پروژه</th>
                    <td>{{ $project->activity_status }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Profile Tab -->
    <div class="tab-pane fade" id="tab-profileproject{{ $project->id }}" role="tabpanel" aria-labelledby="profileproject-tab{{ $project->id }}">
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
                <tr>
                    <th style="width: 30%;">نام شرکت</th>
                    <td>{{ $project->title }}</td>
                </tr>
                <tr>
                    <th>معرفی شرکت</th>
                    <td>{{ $project->description }}</td>
                </tr>
                <tr>
                    <th>مدیرعامل</th>
                    <td>{{ $project->CEO }}</td>
                </tr>
                <tr>
                    <th>شماره موبایل</th>
                    <td>{{ $project->ceo_phone }}</td>
                </tr>
                <tr>
                    <th>وضعیت پروژه</th>
                    <td>{{ $project->activity_status }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- investment Tab -->
    <div class="tab-pane fade" id="tab-investment{{ $project->id }}" role="tabpanel" aria-labelledby="investment-tab{{ $project->id }}">
        <div class="accordion" id="projectStepsAccordion{{ $project->id }}">
            <div class="row g-4">

                {{-- ستون مراحل (سمت چپ) --}}
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <span class="fw-bold">مراحل</span>
                        </div>

                        <div class="list-group list-group-flush step-list">
                            @foreach($investsteps as $step)
                                @php $isActive = ($step->id === $project->invest_step); @endphp
                                <div class="list-group-item {{ $isActive ? 'active' : '' }}"
                                     id="step-item-{{ $step->id }}"
                                     data-step="{{ $step->id }}">
                                    <span class="badge text-bg-primary step-badge">{{ $step->id }}</span>
                                    <span class="fw-semibold">{{ $step->title }}</span>

                                    @if($step->id < $project->invest_step)
                                        <span class="ms-auto text-success">✔</span>
                                    @elseif($isActive)
                                        <span class="badge text-bg-primary now-badge">اکنون</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="card-footer">
                            <button id="resetFlow" class="btn btn-sm btn-outline-secondary">
                                ریست به مرحله ۱ (برای تست)
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ستون محتوای مرحله (سمت راست) --}}
                <div class="col-md-8">
                    @foreach($investsteps as $step)
                        <div class="step-panel" id="step-panel-{{ $step->id }}"
                             @if($project->invest_step !== $step->id) style="display:none" @endif>
                            <div class="card shadow-sm">
                                <div class="card-header bg-white d-flex align-items-center gap-2">
                                    <span class="badge text-bg-primary step-badge">{{ $step->id }}</span>
                                    <h6 class="mb-0 fw-bold">{{ $step->title }}</h6>
                                </div>

                                <div class="card-body">

                                    <div class="mb-3 text-muted">
                                        <small>این متن صرفاً برای دموست. هر UI مرتبط با مرحله {{ $step->id }} را می‌توانید اینجا قرار دهید.</small>
                                    </div>

                                    {{-- فرم مرحله --}}
                                    <form action="{{ route('demo.flow.store') }}"
                                          method="POST"
                                          onsubmit="handleCreate(this); return false;">
                                        @csrf

                                        <input type="hidden" name="project_id" value="{{ $project->id }}">
                                        <input type="hidden" name="step_id" value="{{ $step->id }}">
                                        <input type="hidden" name="step_title" value="{{ $step->title }}">
                                        <input type="hidden" name="status" class="status-input" value="approved">

                                        <div class="mb-3">
                                            <label class="form-label">توضیحات (اختیاری)</label>
                                            <textarea name="description" class="form-control" rows="3" placeholder="توضیحی برای این مرحله بنویسید…"></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">ارسال فرم</button>
                                    </form>

                                </div>
                            </div>
                        </div>
                    @endforeach
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

        </ul>
    </div>
    <!-- Commitment Tab -->
    <div class="tab-pane fade" id="tab-commitment{{ $project->id }}" role="tabpanel" aria-labelledby="commitment-tab{{ $project->id }}">
        <table class="table align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ردیف </th>
                <th>تعهدات </th>
            </tr>
            </thead>
            <tbody>
            @forelse($commitments as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->title }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">موردی ثبت نشده است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <!-- Guaranty Tab -->
    <div class="tab-pane fade" id="tab-guaranty{{ $project->id }}" role="tabpanel" aria-labelledby="guaranty-tab{{ $project->id }}">
        <table class="table table-bordered mt-2">

        </table>
    </div>
    <!-- Workflow Tab -->
    <div class="tab-pane fade" id="tab-workflow{{ $project->id }}" role="tabpanel" aria-labelledby="workflow-tab{{ $project->id }}">
        <table class="table table-bordered mt-2">

        </table>
    </div>
    <!-- Message Tab -->
    <div class="tab-pane fade" id="tab-message{{ $project->id }}" role="tabpanel" aria-labelledby="message-tab{{ $project->id }}">
        <table class="table table-bordered mt-2">

        </table>
    </div>
</div>
