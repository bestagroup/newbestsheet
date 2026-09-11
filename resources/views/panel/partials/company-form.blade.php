<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}company_name">نام شرکت</label>
        <input type="text" class="form-control" id="{{ $prefix }}company_name" name="company_name" required maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}commercial_name">نام تجاری شرکت</label>
        <input type="text" class="form-control" id="{{ $prefix }}commercial_name" name="commercial_name" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}ceo_name">مدیرعامل / نماینده</label>
        <input type="text" class="form-control" id="{{ $prefix }}ceo_name" name="ceo_name" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}registration_number">شماره ثبت</label>
        <input type="text" class="form-control" id="{{ $prefix }}registration_number" name="registration_number" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}registration_date">تاریخ ثبت</label>
        <input type="text" class="form-control" id="{{ $prefix }}registration_date" name="registration_date" data-jdp autocomplete="off" placeholder="1405/01/01">
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
        <select class="form-select" id="{{ $prefix }}legal_type" name="legal_type">
            <option value="">انتخاب کنید</option>
            <option value="مسئولیت محدود">مسئولیت محدود</option>
            <option value="سهامی خاص">سهامی خاص</option>
            <option value="سهامی عام">سهامی عام</option>
            <option value="تعاونی">تعاونی</option>
            <option value="موسسه غیر تجاری">موسسه غیر تجاری</option>
        </select>
    </div>
    @if($showUserSelector)
        <div class="col-md-4">
            <label class="form-label" for="{{ $prefix }}user_id">نماینده سرمایه‌پذیر</label>
            <select class="form-select" id="{{ $prefix }}user_id" name="user_id">
                <option value="">انتخاب کنید</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}phone">تلفن</label>
        <input type="text" class="form-control" id="{{ $prefix }}phone" name="phone" maxlength="50">
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
        <label class="form-label" for="{{ $prefix }}province">استان</label>
        <input type="text" class="form-control" id="{{ $prefix }}province" name="province" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}city">شهر</label>
        <input type="text" class="form-control" id="{{ $prefix }}city" name="city" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}postal_code">کد پستی</label>
        <input type="text" class="form-control" id="{{ $prefix }}postal_code" name="postal_code" maxlength="20">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}ceo_national_code">کد ملی مدیرعامل</label>
        <input type="text" class="form-control" id="{{ $prefix }}ceo_national_code" name="ceo_national_code" maxlength="255">
    </div>
    <div class="col-md-8">
        <label class="form-label" for="{{ $prefix }}address">نشانی</label>
        <textarea class="form-control" id="{{ $prefix }}address" name="address" rows="2"></textarea>
    </div>
</div>
