{{-- resources/views/demo/flow.blade.php --}}
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دموی Flow بدون رفرش</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap 5 + Toastr + jQuery (CDN برای دمو) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
    <style>
        body { background:#f7f7f9; }
        .list-group-item.active { background:#e9f2ff; border-color:#b6d3ff; }
        .step-list .list-group-item { display:flex; align-items:center; gap:.5rem; }
        .step-badge { width:28px; height:28px; display:inline-grid; place-items:center; }
        .step-panel { animation: fade .25s ease-in; }
        @keyframes fade { from{opacity:0} to{opacity:1} }
        .now-badge { margin-right:auto; }
    </style>
</head>
<body class="container py-4">

<div class="mb-4">
    <h4 class="fw-bold mb-1">دموی گردش مراحل (بدون رفرش)</h4>
    <div class="text-muted">پروژه: <strong>{{ $project->title }}</strong> — مرحله جاری: <strong>{{ $project->invest_step }}</strong></div>
</div>

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
                            {{-- تیک برای مراحل گذشته --}}
                            <span class="ms-auto text-success">✔</span>
                        @elseif($isActive)
                            {{-- نشانگر «اکنون» --}}
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

                        {{-- محتوای الکی هر مرحله --}}
                        <div class="mb-3 text-muted">
                            <small>این متن صرفاً برای دموست. می‌توانید هر UI مرتبط با مرحله {{ $step->id }} را اینجا بگذارید.</small>
                        </div>

                        {{-- فرم Flow --}}
                        <form action="{{ route('demo.flow.store') }}" method="POST" class="flow-form">
                            @csrf
                            <input type="hidden" name="project_id" value="{{ $project->id }}">
                            <input type="hidden" name="step_id" value="{{ $step->id }}">
                            <input type="hidden" name="step_title" value="{{ $step->title }}">
                            <input type="hidden" name="status" class="status-input">

                            <div class="mb-3">
                                <label class="form-label">توضیحات (اختیاری)</label>
                                <textarea name="description" class="form-control" rows="3"
                                          placeholder="توضیحی برای این مرحله بنویسید…"></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success approve-btn">تائید مرحله</button>
                                <button type="button" class="btn btn-outline-danger reject-btn">رد مرحله</button>
                                <button type="submit" class="btn-submit d-none">send</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- JS --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    (function () {
        function toast(msg, type='success') {
            toastr.options = { closeButton:true, progressBar:true, positionClass:'toast-top-center', timeOut:2000, rtl:true };
            (toastr[type] || toastr.success)(msg);
        }

        // دکمه‌های تایید/رد
        $(document).on('click', '.approve-btn', function(){
            const $form = $(this).closest('form');
            $form.find('.status-input').val('approved');
            $form.find('.btn-submit').trigger('click');
        });
        $(document).on('click', '.reject-btn', function(){
            const $form = $(this).closest('form');
            $form.find('.status-input').val('rejected');
            $form.find('.btn-submit').trigger('click');
        });

        // ارسال AJAX
        $(document).on('submit', '.flow-form', function (e) {
            e.preventDefault();

            const $form = $(this);
            const url   = $form.attr('action');
            const $btn  = $form.find('.btn-submit');
            const orig  = $btn.html();

            const current = parseInt($form.find('input[name="step_id"]').val(), 10);
            const status  = $form.find('input[name="status"]').val();
            const next    = current + 1;

            $btn.prop('disabled', true).html('...');

            $.ajax({
                url: url,
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            }).done(function(resp){
                if (!resp || resp.success !== true) {
                    toast('خطا در ذخیره مرحله', 'error');
                    return;
                }

                // اگر رد شد: فقط نشانه‌گذاری و جلو نرو
                if (status === 'rejected') {
                    const $item = $('#step-item-' + current);
                    $item.removeClass('active');
                    if ($item.find('.text-danger').length === 0) {
                        $item.append('<span class="ms-auto text-danger">✖</span>');
                    }
                    toast('مرحله رد شد', 'warning');
                    return;
                }

                // تایید شد → تیک مرحله فعلی
                const $curItem  = $('#step-item-' + current);
                const $nextItem = $('#step-item-' + next);

                $curItem.removeClass('active');
                $curItem.find('.now-badge').remove();
                if ($curItem.find('.text-success').length === 0) {
                    $curItem.append('<span class="ms-auto text-success">✔</span>');
                }

                // فعال کردن بعدی
                if ($nextItem.length) {
                    $nextItem.addClass('active');
                    $nextItem.find('.now-badge').remove();
                    $nextItem.append('<span class="badge text-bg-primary now-badge">اکنون</span>');
                }

                // سوییچ پنل سمت راست
                $('#step-panel-' + current).hide();
                if ($('#step-panel-' + next).length) {
                    $('#step-panel-' + next).show();
                    toast('مرحله با موفقیت تائید شد');
                } else {
                    toast('همه مراحل تکمیل شد');
                }
            }).fail(function () {
                toast('اشکال ارتباط با سرور', 'error');
            }).always(function(){
                $btn.prop('disabled', false).html(orig);
            });
        });

        // دکمه ریست برای تست
        $('#resetFlow').on('click', function () {
            // فقط برای دمو: با یک درخواست رد مرحله 0، Session را به 1 برمی‌گردانیم
            $.post("{{ route('demo.flow.store') }}", {
                _token: $('meta[name="csrf-token"]').attr('content'),
                project_id: {{ $project->id }},
                step_id: 0,
                step_title: 'reset',
                status: 'approved',
                description: 'reset to 1'
            }).always(function(){
                // ری‌لود تا از سرور مرحله 1 بیاید (برای دمو OK است)
                window.location.reload();
            });
        });
    })();
</script>
</body>
</html>
