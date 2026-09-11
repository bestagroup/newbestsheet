document.addEventListener('DOMContentLoaded', () => {

    /* -------------------------------------------
     *  تابع Toast عمومی
     * ------------------------------------------- */
    function showToast(message, type = 'success') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000,
            rtl: true
        };
        if (toastr[type]) toastr[type](message);
        else toastr.success(message);
    }

    /* -------------------------------------------
     *  هندل کردن CREATE
     * ------------------------------------------- */
    function handleCreate(form) {
        const $form = $(form);
        const $btn = $form.find('[type="submit"]');
        const originalHtml = $btn.html();

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ارسال...');

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
            success: function (data) {
                if (data.success) {
                    const modalEl = document.querySelector('#addModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

                    modalEl.addEventListener('hidden.bs.modal', function handler() {
                        modalEl.removeEventListener('hidden.bs.modal', handler);
                        $('.yajra-datatable').DataTable().ajax.reload(null, false);
                    }, {once: true});

                    modal.hide();
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                    showToast('آیتم با موفقیت افزوده شد!', 'success');
                } else {
                    swal(data.subject || 'خطا', data.message || 'عملیات انجام نشد.', data.flag || 'error');
                }
            },
            error: function (xhr) {
                let message = 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                swal('خطا', message, 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /* -------------------------------------------
     *  لود فرم ویرایش به صورت داینامیک
     * ------------------------------------------- */
    $(document).on('click', '.edit-btn', function () {
        const url = $(this).data('url');
        const $modal = $('#editModal');
        const $body = $('#editModalBody');

        $body.html('<div class="text-center text-muted py-5">در حال بارگذاری...</div>');
        $modal.modal('show');

        $.ajax({
            url: url,
            method: 'GET',
            success: function (html) {
                $body.html(html);
            },
            error: function () {
                $body.html('<div class="alert alert-danger m-3">خطا در بارگذاری فرم ویرایش</div>');
            }
        });
    });

    $(document).on('submit', 'form[data-type="update"]', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> در حال ذخیره...');

        $.ajax({
            url: $form.attr('action'),
            method: 'PATCH',
            data: $form.serialize(),
            success: function (data) {
                if (data.success) {
                    $('#editModal').modal('hide');
                    $('.yajra-datatable').DataTable().ajax.reload(null, false);
                    showToast('اطلاعات با موفقیت به‌روزرسانی شد!', 'success');
                } else {
                    showToast(data.message || 'خطایی رخ داد', 'error');
                }
            },
            error: function () {
                showToast('مشکلی در ذخیره تغییرات رخ داد', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    /* -------------------------------------------
     *  هندل کردن UPDATE
     * ------------------------------------------- */
    function handleUpdate(form) {
        const $form = $(form);
        const $btn = $form.find('[type="submit"]');
        const originalHtml = $btn.html();
        const url = $form.attr('action');

        $btn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ارسال...');

        $.ajax({
            url: url,
            method: 'PATCH',
            data: $form.serialize(),
            headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
            success: function (data) {
                if (data.success) {
                    const id = $form.data('id') || '';
                    const modalEl = document.getElementById('showModal' + id);

                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

                        modalEl.addEventListener('hidden.bs.modal', function handler() {
                            modalEl.removeEventListener('hidden.bs.modal', handler);
                            $('.yajra-datatable').DataTable().ajax.reload(null, false);
                            showToast('آیتم با موفقیت ویرایش شد!', 'success');
                        }, {once: true});

                        modal.hide();
                    } else {
                        // در صورتی که مودال پیدا نشه (برای خطایابی)
                        console.error('Modal element not found for id:', id);
                        showToast('آیتم با موفقیت ویرایش شد! (مودال پیدا نشد)', 'success');
                        $('.yajra-datatable').DataTable().ajax.reload(null, false);
                    }
                }
            },
            error: function (xhr) {
                let message = 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                swal('خطا', message, 'error');
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        const baseUrl = `/${pathParts[0]}/${pathParts[1]}`;
        const deleteUrl = `${baseUrl}/${id}`;

        $.ajax({
            url: deleteUrl,
            method: 'DELETE',
            data: {"_token": csrfToken},
            success: function (data) {
                $('#deleteModal').modal('hide');
                $('.yajra-datatable').DataTable().ajax.reload(null, false);
                showToast(data.message || 'آیتم با موفقیت حذف شد!', 'success');
            },
            error: function (xhr) {
                let message = 'مشکلی پیش آمد. لطفاً دوباره تلاش کنید.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
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

    $('form[data-type="update"]').on('submit', function (e) {
        e.preventDefault();
        handleUpdate(this);
    });

    /* -------------------------------------------
     *  رویدادهای حذف
     * ------------------------------------------- */
    let deleteId = null;

    $(document).on('click', '.delete-btn', function () {
        deleteId = $(this).data('id');
        $('#deleteModal').modal('show');
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

        $body.html('<div class="text-center text-muted py-5">در حال بارگذاری...</div>');
        $modal.modal('show');

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                $body.html(response); // فرض: کنترلر HTML برمی‌گردونه
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
            maxFilesize: 20,
            acceptedFiles: 'image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
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
            $('#uploadModal').modal('show');
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


});
