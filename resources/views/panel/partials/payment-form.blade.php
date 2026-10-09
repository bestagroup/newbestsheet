<div class="row g-3">
<div class="col-md-3"><label class="form-label">شماره سند مالی</label><input required name="docserial" id="{{ $prefix }}docserial" class="form-control"></div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}project_id">نام پروژه</label>
        <select name="project_id" id="{{ $prefix }}project_id" class="form-control" required>
            <option value="">انتخاب کنید</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}">{{ $project->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}amount">مبلغ پرداختی</label>
        <input type="text" name="amount" id="{{ $prefix }}amount" class="form-control money-input" inputmode="numeric">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}serial">شماره مرحله پرداخت (۱ تا ۵)</label>
        <input type="text" name="serial" id="{{ $prefix }}serial" class="form-control">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="{{ $prefix }}date">تاریخ پرداخت</label>
        <input type="text" name="date" id="{{ $prefix }}date" class="form-control" data-jdp autocomplete="off">
    </div>
    <div class="col-12">
        <label class="form-label" for="{{ $prefix }}description">علت پرداخت</label>
        <textarea name="description" id="{{ $prefix }}description" class="form-control" rows="4"></textarea>
    </div>
</div>
