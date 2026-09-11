{{-- resources/views/demo/flow_dt.blade.php --}}
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دمو: DataTable + Modal Flow (بدون رفرش)</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap + DataTables + Toastr (CDN برای دمو) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

    <style>
        body {
            background: #f7f7f9;
        }

        .list-group-item.active {
            background: #e9f2ff;
            border-color: #b6d3ff;
        }

        .step-badge {
            width: 26px;
            height: 26px;
            display: inline-grid;
            place-items: center;
        }

        .now-badge {
            margin-right: auto;
        }

        .step-list .list-group-item {
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .step-panel {
            animation: fade .2s ease-in;
        }

        @keyframes fade {
            from {
                opacity: 0
            }
            to {
                opacity: 1
            }
        }
    </style>
</head>
<body class="container py-4">

<h4 class="fw-bold mb-3">لیست پروژه‌ها + مودال گردش مراحل</h4>
<div class="card shadow-sm">
    <div class="card-body">
        <table id="projectsTable" class="table table-striped table-hover w-100">
            <thead>
            <tr>
                <th>عملیات</th>
                <th>شناسه</th>
                <th>شرکت</th>
                <th>عنوان</th>
                <th>CEO</th>
                <th>مرحله فعلی</th>
            </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Modal: Flow --}}
<div class="modal fade" id="flowModal" tabindex="-1" aria-labelledby="flowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="flowModalLabel">گردش مراحل</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <div id="flowModalBody" class="row g-4">
                    {{-- اینجا با AJAX ساخته می‌شود --}}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

{{-- JS --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    (function () {
        const ROUTES = {
            DATA: "{{ route('demo.flowdt.data') }}",
            SHOW: "{{ route('demo.flowdt.show', ['id'=>':id']) }}",
            STORE: "{{ route('demo.flowdt.store') }}",
        };

        function toast(msg, type = 'success') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-center',
                timeOut: 2000,
                rtl: true
            };
            (toastr[type] || toastr.success)(msg);
        }

        // --- DataTable init ---
        const table = $('#projectsTable').DataTable({
            processing: true,
            serverSide: false,      // دمو: سمت کلاینت
            ajax: {url: ROUTES.DATA, dataSrc: 'data'},
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (row) => (
                        `<button class="btn btn-sm btn-primary open-flow" data-id="${row.id}">
                 گردش مراحل
               </button>`
                    )
                },
                {data: 'id'},
                {data: 'company_name'},
                {data: 'title'},
                {data: 'CEO'},
                {
                    data: 'invest_step',
                    render: (v) => `<span class="badge text-bg-info">${v}</span>`
                }
            ],
            order: [[1, 'asc']],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/fa.json'
            }
        });

        // --- روی دکمه‌ی "گردش مراحل" کلیک شد ---
        $(document).on('click', '.open-flow', function () {
            const id = $(this).data('id');
            const url = ROUTES.SHOW.replace(':id', id);

            $('#flowModalLabel').text(`گردش مراحل — پروژه #${id}`);
            $('#flowModalBody').html('<div class="text-center p-5">در حال بارگذاری…</div>');

            $.get(url).done(function (resp) {
                if (!resp || !resp.success) {
                    toast('خطا در دریافت اطلاعات', 'error');
                    return;
                }
                // ساخت محتوای مودال
                const html = buildFlowModalHtml(resp.project, resp.steps);
                $('#flowModalBody').html(html);
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('flowModal'));
                modal.show();
            }).fail(function () {
                toast('اشکال ارتباط با سرور', 'error');
            });
        });

        // --- ساخت HTML مودال از JSON ---
        function buildFlowModalHtml(project, steps) {
            const left = [
                '<div class="col-md-4">',
                '<div class="card shadow-sm">',
                '<div class="card-header bg-white"><span class="fw-bold">مراحل</span></div>',
                '<div class="list-group list-group-flush step-list">'
            ];
            steps.forEach(s => {
                const isActive = s.id === project.invest_step;
                left.push(
                    `<div class="list-group-item ${isActive ? 'active' : ''}" id="step-item-${s.id}" data-step="${s.id}">`,
                    `<span class="badge text-bg-primary step-badge">${s.id}</span>`,
                    `<span class="fw-semibold">${s.title}</span>`,
                    (s.id < project.invest_step) ? '<span class="ms-auto text-success">✔</span>'
                        : (isActive ? '<span class="badge text-bg-primary now-badge">اکنون</span>' : ''),
                    `</div>`
                );
            });
            left.push('</div></div></div>');

            const right = ['<div class="col-md-8">'];
            steps.forEach(s => {
                right.push(
                    `<div class="step-panel" id="step-panel-${s.id}" style="${(project.invest_step !== s.id) ? 'display:none' : ''}">`,
                    '<div class="card shadow-sm">',
                    `<div class="card-header bg-white d-flex align-items-center gap-2">`,
                    `<span class="badge text-bg-primary step-badge">${s.id}</span>`,
                    `<h6 class="mb-0 fw-bold">${s.title}</h6>`,
                    `</div>`,
                    `<div class="card-body">`,
                    `<div class="mb-3 text-muted"><small>— محتوای تست برای مرحله ${s.id}</small></div>`,
                    `<form class="flow-form" action="${ROUTES.STORE}" method="POST">`,
                    `@csrf`,
                    `<input type="hidden" name="project_id" value="${project.id}">`,
                    `<input type="hidden" name="step_id" value="${s.id}">`,
                    `<input type="hidden" name="status" class="status-input">`,
                    `<div class="mb-3">`,
                    `<label class="form-label">توضیحات</label>`,
                    `<textarea name="description" class="form-control" rows="3"></textarea>`,
                    `</div>`,
                    `<div class="d-flex gap-2">`,
                    `<button type="button" class="btn btn-success approve-btn">تأیید مرحله</button>`,
                    `<button type="button" class="btn btn-outline-danger reject-btn">رد مرحله</button>`,
                    `<button type="submit" class="btn-submit d-none">send</button>`,
                    `</div>`,
                    `</form>`,
                    `</div>`,
                    `</div>`,
                    `</div>`
                );
            });
            right.push('</div>');

            return left.join('') + right.join('');
        }

        // --- Approve / Reject (داخل مودال) ---
        $(document).on('click', '.approve-btn', function () {
            const $form = $(this).closest('form');
            $form.find('.status-input').val('approved');
            $form.find('.btn-submit').trigger('click');
        });
        $(document).on('click', '.reject-btn', function () {
            const $form = $(this).closest('form');
            $form.find('.status-input').val('rejected');
            $form.find('.btn-submit').trigger('click');
        });

        // --- Submit AJAX (داخل مودال) ---
        $(document).on('submit', '.flow-form', function (e) {
            e.preventDefault();
            const $form = $(this);
            const url = $form.attr('action');

            const pid = parseInt($form.find('input[name="project_id"]').val(), 10);
            const cur = parseInt($form.find('input[name="step_id"]').val(), 10);
            const stat = $form.find('input[name="status"]').val();
            const next = cur + 1;

            $.ajax({
                url: url,
                method: 'POST',
                data: $form.serialize(),
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
            }).done(function (resp) {
                if (!resp || resp.success !== true) {
                    toast('خطا در ثبت', 'error');
                    return;
                }

                // حالت رد: فقط نشانه‌گذاری
                if (stat === 'rejected') {
                    const $item = $('#step-item-' + cur);
                    $item.removeClass('active');
                    if ($item.find('.text-danger').length === 0) {
                        $item.append('<span class="ms-auto text-danger">✖</span>');
                    }
                    toast('مرحله رد شد', 'warning');
                    return;
                }

                // حالت تأیید: تیک مرحله فعلی + رفتن به بعدی
                const $curItem = $('#step-item-' + cur);
                const $nextItem = $('#step-item-' + next);

                $curItem.removeClass('active');
                $curItem.find('.now-badge').remove();
                if ($curItem.find('.text-success').length === 0) {
                    $curItem.append('<span class="ms-auto text-success">✔</span>');
                }

                if ($nextItem.length) {
                    $nextItem.addClass('active');
                    $nextItem.find('.now-badge').remove();
                    $nextItem.append('<span class="badge text-bg-primary now-badge">اکنون</span>');
                }

                // سوییچ پنل‌ها
                $('#step-panel-' + cur).hide();
                if ($('#step-panel-' + next).length) {
                    $('#step-panel-' + next).show();
                    toast('مرحله تأیید شد');
                } else {
                    toast('همه‌ی مراحل تکمیل شد');
                }

                // آپدیت همان ردیف دیتاتیبل (ستون invest_step) بدون رفرش کل جدول
                const rowIdx = table.rows().eq(0).filter(function (idx) {
                    return table.cell(idx, 1).data() === pid; // ستون 1 = id
                });
                if (rowIdx.length) {
                    table.cell(rowIdx[0], 5).data(`<span class="badge text-bg-info">${resp.invest_step}</span>`).draw(false);
                }
            }).fail(function () {
                toast('اشکال ارتباط با سرور', 'error');
            });
        });

    })();
</script>
</body>
</html>
