<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}title">نام تجاری طرح</label>
        <input type="text" class="form-control" id="{{ $prefix }}title" name="title" required maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}company_id">شرکت پورتفو</label>
        <select class="form-select" id="{{ $prefix }}company_id" name="company_id">
            <option value="">پروژه مستقل</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->company_name ?: $company->commercial_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}company_name">نام شرکت برای پروژه مستقل</label>
        <input type="text" class="form-control" id="{{ $prefix }}company_name" name="company_name" maxlength="255">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}CEO">مدیرعامل شرکت</label>
        <input type="text" class="form-control" id="{{ $prefix }}CEO" name="CEO" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}ceo_national_code">کد ملی مدیرعامل</label>
        <input type="text" class="form-control" id="{{ $prefix }}ceo_national_code" name="ceo_national_code" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}ceo_phone">شماره تماس مدیرعامل</label>
        <input type="text" class="form-control" id="{{ $prefix }}ceo_phone" name="ceo_phone" maxlength="50">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}registration_number">شماره ثبت</label>
        <input type="text" class="form-control" id="{{ $prefix }}registration_number" name="registration_number" maxlength="255">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}registration_date">تاریخ ثبت</label>
        <input type="text" class="form-control" id="{{ $prefix }}registration_date" name="registration_date" maxlength="50" data-jdp autocomplete="off" placeholder="1405/01/01">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}national_id">شناسه ملی</label>
        <input type="text" class="form-control" id="{{ $prefix }}national_id" name="national_id" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}economic_code">کد اقتصادی</label>
        <input type="text" class="form-control" id="{{ $prefix }}economic_code" name="economic_code" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}legal_type">نوع شرکت</label>
        <input type="text" class="form-control" id="{{ $prefix }}legal_type" name="legal_type" maxlength="255">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}tel">تلفن</label>
        <input type="text" class="form-control" id="{{ $prefix }}tel" name="tel" maxlength="50">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}email">ایمیل</label>
        <input type="email" class="form-control" id="{{ $prefix }}email" name="email" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}website">وب‌سایت</label>
        <input type="text" class="form-control" id="{{ $prefix }}website" name="website" maxlength="255">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}postal_code">کد پستی</label>
        <input type="text" class="form-control" id="{{ $prefix }}postal_code" name="postal_code" maxlength="20">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}state">استان</label>
        <select class="form-select project-state-select" id="{{ $prefix }}state" name="state" data-city-target="{{ $prefix }}city">
            <option value="">انتخاب کنید</option>
            @foreach($states as $state)
                <option value="{{ $state->id }}">{{ $state->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}city">شهر</label>
        <select class="form-select" id="{{ $prefix }}city" name="city">
            <option value="">ابتدا استان را انتخاب کنید</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}portfo_status">وضعیت پورتفو</label>
        <input type="text" class="form-control" id="{{ $prefix }}portfo_status" name="portfo_status" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}activity_status">وضعیت فعالیت</label>
        <input type="text" class="form-control" id="{{ $prefix }}activity_status" name="activity_status" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}percentageshare">درصد سهام دریافتی</label>
        <input type="text" inputmode="decimal" class="form-control" id="{{ $prefix }}percentageshare" name="percentageshare" placeholder="مثال: 15.5">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}start_date">تاریخ شروع قرارداد</label>
        <input type="text" class="form-control" id="{{ $prefix }}start_date" name="start_date" data-jdp autocomplete="off" placeholder="1405/01/01">
    </div>

    @if($showWorkflowState)
        <div class="col-md-4">
            <label class="form-label" for="{{ $prefix }}flow_level_display">مرحله جاری فرایند</label>
            <input type="text" class="form-control" id="{{ $prefix }}flow_level_display" disabled>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="{{ $prefix }}progress_percentage_display">درصد پیشرفت</label>
            <input type="text" class="form-control" id="{{ $prefix }}progress_percentage_display" disabled>
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}amount_request_accept">مبلغ درخواستی تأییدشده</label>
        <input type="text" inputmode="numeric" class="form-control money-input" id="{{ $prefix }}amount_request_accept" name="amount_request_accept">
    </div>
    @foreach([
        'amount_commitment_first_stage' => 'مبلغ تعهد مرحله اول',
        'amount_commitment_second_stage' => 'مبلغ تعهد مرحله دوم',
        'amount_commitment_third_stage' => 'مبلغ تعهد مرحله سوم',
        'amount_commitment_fourth_stage' => 'مبلغ تعهد مرحله چهارم',
        'amount_commitment_fifth_stage' => 'مبلغ تعهد مرحله پنجم',
    ] as $field => $label)
        <div class="col-md-4">
            <label class="form-label" for="{{ $prefix }}{{ $field }}">{{ $label }}</label>
            <input type="text" inputmode="numeric" class="form-control money-input" id="{{ $prefix }}{{ $field }}" name="{{ $field }}">
        </div>
    @endforeach

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}logo">مسیر لوگو</label>
        <input type="text" class="form-control" id="{{ $prefix }}logo" name="logo" maxlength="2048">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}address">نشانی</label>
        <textarea class="form-control" id="{{ $prefix }}address" name="address" rows="2"></textarea>
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $prefix }}description">معرفی طرح</label>
        <textarea class="form-control" id="{{ $prefix }}description" name="description" rows="4"></textarea>
    </div>
</div>
