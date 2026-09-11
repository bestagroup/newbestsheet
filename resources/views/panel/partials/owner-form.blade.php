<div class="row g-3">
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}title">عنوان شرکت</label><input required type="text" name="title" id="{{ $prefix }}title" class="form-control"></div>
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}tel">شماره تماس</label><input type="text" name="tel" id="{{ $prefix }}tel" class="form-control"></div>
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}mobile">موبایل</label><input type="text" name="mobile" id="{{ $prefix }}mobile" class="form-control"></div>
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}email">ایمیل</label><input type="email" name="email" id="{{ $prefix }}email" class="form-control"></div>
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}ceo">نام مدیرعامل</label><input type="text" name="ceo" id="{{ $prefix }}ceo" class="form-control"></div>
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}meli_code">شناسه ملی</label><input type="text" name="meli_code" id="{{ $prefix }}meli_code" class="form-control"></div>
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}eghtesadi_code">کد اقتصادی</label><input type="text" name="eghtesadi_code" id="{{ $prefix }}eghtesadi_code" class="form-control"></div>
    <div class="col-md-3"><label class="form-label" for="{{ $prefix }}date_sabt">تاریخ ثبت</label><input type="text" name="date_sabt" id="{{ $prefix }}date_sabt" class="form-control" data-jdp autocomplete="off"></div>
    <div class="col-12"><label class="form-label" for="{{ $prefix }}social">شبکه‌های اجتماعی</label><input type="text" name="social" id="{{ $prefix }}social" class="form-control" placeholder="instagram:example, linkedin:example"></div>
    <div class="col-12"><label class="form-label" for="{{ $prefix }}address">آدرس</label><textarea name="address" id="{{ $prefix }}address" class="form-control" rows="2"></textarea></div>
    <div class="col-12"><label class="form-label" for="{{ $prefix }}summery">توضیحات</label><textarea name="summery" id="{{ $prefix }}summery" class="form-control" rows="4"></textarea></div>
</div>
