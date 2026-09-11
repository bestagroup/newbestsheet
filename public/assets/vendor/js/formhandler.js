document.addEventListener('DOMContentLoaded', () => {

    /* -------------------------------------------
     *  تابع Toast عمومی
     * ------------------------------------------- */
    function showToast(message, type = 'success') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 4000,
            rtl: true,
        };

        switch (type) {
            case 'error':
                toastr.error(message || '❌ خطایی رخ داده است.');
                break;
            case 'warning':
                toastr.warning(message || '⚠️ توجه!');
                break;
            case 'info':
                toastr.info(message || 'ℹ️ اطلاعات');
                break;
            default:
                toastr.success(message || '✅ عملیات با موفقیت انجام شد.');
        }
    }

    function getModal($modal) {
        const element = $modal?.[0];
        if (!element || !window.bootstrap?.Modal) {
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(element);
    }

    function showModal($modal) {
        const modal = getModal($modal);
        modal?.show();
        return modal;
    }

    function hideModal($modal) {
        getModal($modal)?.hide();
    }


    /* -------------------------------------------
     *  هندل کردن CREATE
     * ------------------------------------------- */
    function handleCreate(form) {
        const $form = $(form);
        const $btn = $form.find('[type="submit"]');
        const originalHtml = $btn.html();
        const modalEl = $form.closest('.modal')[0];
        const modal = modalEl ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)) : null;

        // جدول هدف
        const targetTable = $form.data('table-target');

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ارسال...');

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },

            success: function (data) {
                if (data.success) {
                    if (modalEl && modal) {
                        modal.hide();
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }

                    // ✅ فقط جدول مرتبط reload شود
                    if (targetTable && $.fn.DataTable.isDataTable(targetTable)) {
                        $(targetTable).DataTable().ajax.reload(null, false);
                    }

                    showToast(data.message || '✅ آیتم با موفقیت افزوده شد!', 'success');
                } else {
                    showToast(data.message || '❌ عملیات انجام نشد.', 'error');
                }
            },

            error: function (xhr) {
                let message = '❌ مشکلی پیش آمد. لطفاً دوباره تلاش کنید.';
                if (xhr.responseJSON?.message) message = xhr.responseJSON.message;
                showToast(message, 'error');
            },

            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }
    window.handleCreate = handleCreate;

    /* -------------------------------------------
     *  لود فرم ویرایش به صورت داینامیک
     * ------------------------------------------- */

    $(document).on('click', '.edit-btn', function () {
        const url = $(this).data('url');
        const $modal = $('#editModal');
        const $body = $('#editModalBody');

        $body.html('<div class="text-center text-muted py-5">در حال بارگذاری...</div>');
        showModal($modal);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (html) {
                $body.html(html);
                window.FormEnhancements?.init($body[0]);
            },
            error: function () {
                $body.html('<div class="alert alert-danger m-3">خطا در بارگذاری فرم ویرایش</div>');
            }
        });
    });

    $(document).on('submit', 'form[data-type="update"]', function (e) {
        e.preventDefault();
        handleUpdate(this);
    });

    /* -------------------------------------------
     *  هندل کردن UPDATE
     * ------------------------------------------- */
    function handleUpdate(form) {
        const $form = $(form);
        const $btn = $form.find('[type="submit"]');
        const originalHtml = $btn.html();
        const url = $form.attr('action');
        const id = $form.data('id') || '';
        const modalEl = $form.closest('.modal')[0];
        const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

        // جدول هدف (هر فرم باید data-table-target داشته باشه)
        const targetTable = $form.data('table-target') || '.yajra-datatable';

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ارسال...');

        $.ajax({
            url: url,
            method: 'PATCH',
            data: $form.serialize(),
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},

            success: function (data) {
                if (data.success) {
                    if (modalEl && modal) {
                        modal.hide();
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('padding-right', '');
                    }

                    // ✅ فقط جدول مرتبط reload شود
                    if ($.fn.DataTable && targetTable && $.fn.DataTable.isDataTable(targetTable)) {
                        $(targetTable).DataTable().ajax.reload(null, false);
                    }

                    showToast(data.message || 'آیتم با موفقیت ویرایش شد!', 'success');
                } else {
                    showToast(data.message || 'خطا در عملیات ویرایش', 'error');
                }
            },

            error: function (xhr) {
                let message = 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.';
                if (xhr.responseJSON?.message) message = xhr.responseJSON.message;
                showToast(message, 'error');
            },

            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }




    /* -------------------------------------------
     *  هندل کردن DELETE
     * ------------------------------------------- */
    function handleDelete(id) {
        const $btn = $('#confirmDelete');
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال حذف...'
        );

        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        const baseUrl = `/${pathParts[0]}/${pathParts[1]}`;
        const deleteUrl = `${baseUrl}/${id}`;

        $.ajax({
            url: deleteUrl,
            method: 'DELETE',
            data: {"_token": csrfToken},
            success: function (data) {
                hideModal($('#deleteModal'));
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
                $('.yajra-datatable').DataTable().ajax.reload(null, false);
                showToast(data.message || 'آیتم با موفقیت حذف شد!', 'success');
            },
            error: function (xhr) {
                let message = 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                hideModal($('#deleteModal'));
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
                showToast(message, 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }


    /* -------------------------------------------
     *  اتصال رویدادها به فرم‌ها
     * ------------------------------------------- */
    $('form[data-type="create"]').on('submit', function (e) {
        e.preventDefault();
        handleCreate(this);
    });

    /* -------------------------------------------
     *  رویدادهای حذف
     * ------------------------------------------- */
    let deleteId = null;

    $(document).on('click', '.delete-btn', function () {
        deleteId = $(this).data('id');
        showModal($('#deleteModal'));
    });

    $('#confirmDelete').on('click', function () {
        if (deleteId) handleDelete(deleteId);
    });

    /* -------------------------------------------
 *  نمایش پروفایل شرکت
 * ------------------------------------------- */
    $(document).on('click', '.show-btn', function () {
        const url = $(this).data('url');
        const $modal = $('#showModal');
        const $body = $('#showModalBody');
        const $title = $('#showModalLabel');

        $title.text('پروفایل شرکت');
        $body.html('<div class="text-center text-muted py-5">در حال بارگذاری...</div>');
        showModal($modal);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                $body.html(response);
                window.FormEnhancements?.init($body[0]);
                const companyName = String($body.find('[data-company-profile-name]').first().attr('data-company-profile-name') || '').trim();
                $title.text(companyName ? `پروفایل شرکت — ${companyName}` : 'پروفایل شرکت');
            },
            error: function () {
                $body.html('<div class="text-center text-danger py-5">خطا در بارگذاری اطلاعات</div>');
            }
        });
    });

    // --- آپلود فایل Dropzone ---
// 🔹 Dropzone تنظیمات اولیه
    Dropzone.autoDiscover = false;

    document.addEventListener("DOMContentLoaded", function () {
        const fileFormSelector = "#fileUploadZone";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const uploadUrl = document.querySelector(fileFormSelector)?.getAttribute('action') || '/storemedia';

        const dz = new Dropzone(fileFormSelector, {
            url: uploadUrl,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            maxFilesize: 100,
            parallelUploads: 20,
            uploadMultiple: false,
            acceptedFiles: `image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,
            application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.openxmlformats-officedocument.presentationml.presentation`,
            dictDefaultMessage: "فایل‌ها را اینجا رها کنید یا کلیک کنید برای انتخاب",
            init: function () {
                this.on("sending", function (file, xhr, formData) {
                    const recordId = document.getElementById('recordIdInput')?.value || '';
                    formData.append("record_id", recordId);
                });

                this.on("success", function (file, response) {
                    const extension = file.name.split('.').pop().toLowerCase();
                    if (response?.file_path) {
                        previewFile(response.file_path.replace(/^\/+/, ''), extension);
                    }
                    showToast("✅ فایل با موفقیت آپلود شد", "success");
                    this.removeFile(file);
                });

                this.on("error", function () {
                    showToast("❌ خطا در آپلود فایل", "danger");
                });
            }
        });

        // 🔹 دکمه باز کردن مودال
        $(document).on('click', '.upload-btn', function () {
            const currentRecordId = $(this).data('id') || '';
            $('#recordIdInput').val(currentRecordId);
            dz.removeAllFiles(true);
            showModal($('#uploadModal'));
        });
    });

// 🔹 نمایش فایل پس از آپلود
    function previewFile(fileUrl, ext) {
        const url = `/${fileUrl}`;
        const map = {
            img: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'],
            vid: ['mp4', 'webm', 'ogg'],
            pdf: ['pdf'],
            office: ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx']
        };

        let html =
            map.img.includes(ext) ? `<img src="${url}" class="img-fluid">` :
                map.vid.includes(ext) ? `<video controls style="width:100%;max-height:500px"><source src="${url}" type="video/${ext}"></video>` :
                    map.pdf.includes(ext) ? `<iframe src="${url}" style="width:100%;height:500px;border:none;"></iframe>` :
                        map.office.includes(ext) ? `<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${location.origin}/${fileUrl}" style="width:100%;height:500px;border:none;"></iframe>` :
                            `<p class="text-center">پیش‌نمایش برای این نوع فایل در دسترس نیست.</p>`;

        document.getElementById('previewContent').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
    }

// 🔹 تابع Toast ساده
    function showToast(msg, type = "success") {
        const el = document.createElement("div");
        el.className = `toast text-bg-${type} border-0 show position-fixed bottom-0 end-0 m-4`;
        el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

//نمایش تقویم جلالی
    if (window.jalaliDatepicker && typeof window.jalaliDatepicker.startWatch === "function") {
        window.jalaliDatepicker.startWatch();
    }

    var jalaliInput = document.querySelector("[data-jdp-miladi-input]");
    if (jalaliInput) {
        jalaliInput.addEventListener("jdp:change", function () {
            var miladiInput = document.getElementById(this.getAttribute("data-jdp-miladi-input"));
            if (!miladiInput) return;
            if (!this.value) {
                miladiInput.value = "";
                return;
            }
            var date = this.value.split("/");
            miladiInput.value = jalali_to_gregorian(date[0], date[1], date[2]).join("/")
        });
    }

    function jalali_to_gregorian(jy, jm, jd) {
        jy = Number(jy);
        jm = Number(jm);
        jd = Number(jd);
        var gy = (jy <= 979) ? 621 : 1600;
        jy -= (jy <= 979) ? 0 : 979;
        var days = (365 * jy) + ((parseInt(jy / 33)) * 8) + (parseInt(((jy % 33) + 3) / 4))
            + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        gy += 400 * (parseInt(days / 146097));
        days %= 146097;
        if (days > 36524) {
            gy += 100 * (parseInt(--days / 36524));
            days %= 36524;
            if (days >= 365) days++;
        }
        gy += 4 * (parseInt((days) / 1461));
        days %= 1461;
        gy += parseInt((days - 1) / 365);
        if (days > 365) days = (days - 1) % 365;
        var gd = days + 1;
        var sal_a = [0, 31, ((gy % 4 == 0 && gy % 100 != 0) || (gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        var gm
        for (gm = 0; gm < 13; gm++) {
            var v = sal_a[gm];
            if (gd <= v) break;
            gd -= v;
        }
        return [gy, gm, gd];
    }


// ✅ اطمینان از اجرا بعد از لود کامل صفحه و مودال‌ها
    $(document).ready(function () {

        // وقتی کاربر در input تایپ می‌کند
        $(document).on('input', '.input-number', function (e) {
            let value = e.target.value.replace(/,/g, ''); // حذف کاماهای قبلی

            // فقط اگر مقدار عددی بود
            if (/^\d*$/.test(value)) {
                // تبدیل به عدد و فرمت سه‌رقمی
                const formatted = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                e.target.value = formatted;
            } else {
                // اگر کاربر حروف زد، حذفش کن
                e.target.value = value.replace(/\D/g, '');
            }
        });

        // قبل از ارسال هر فرم، کاماها را حذف می‌کنیم
        $(document).on('submit', 'form', function () {
            $(this).find('.input-number').each(function () {
                this.value = this.value.replace(/,/g, '');
            });
        });
    });


});
