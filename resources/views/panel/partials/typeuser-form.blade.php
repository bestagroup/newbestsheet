<div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}title_fa">عنوان فارسی</label><input type="text" class="form-control" id="{{ $prefix }}title_fa" name="title_fa" required maxlength="255"></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}title">عنوان سیستمی</label><input type="text" class="form-control" id="{{ $prefix }}title" name="title" required maxlength="255"></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}status">وضعیت</label><select class="form-select" id="{{ $prefix }}status" name="status" required><option value="4">فعال</option><option value="0">غیرفعال</option></select></div>
</div>
