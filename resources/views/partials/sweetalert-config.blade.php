<script>
(function () {
    if (!window.Swal || typeof window.Swal.mixin !== 'function') {
        return;
    }

    window.Swal = window.Swal.mixin({
        buttonsStyling: false,
        heightAuto: false,
        reverseButtons: true,
        confirmButtonText: 'تأیید',
        cancelButtonText: 'انصراف',
        customClass: {
            popup: 'app-swal-popup',
            title: 'app-swal-title',
            htmlContainer: 'app-swal-text',
            confirmButton: 'btn btn-primary px-4',
            cancelButton: 'btn btn-outline-secondary px-4 me-2'
        }
    });

    window.AppAlert = Object.freeze({
        success(message, title = 'عملیات موفق') {
            return window.Swal.fire({title, text: message || 'عملیات با موفقیت انجام شد.', icon: 'success'});
        },
        error(message, title = 'خطا') {
            return window.Swal.fire({title, text: message || 'عملیات انجام نشد.', icon: 'error'});
        },
        info(message, title = 'اطلاع') {
            return window.Swal.fire({title, text: message || '', icon: 'info'});
        },
        confirm(message, title = 'تأیید عملیات') {
            return window.Swal.fire({
                title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'تأیید',
                cancelButtonText: 'انصراف'
            });
        }
    });
})();
</script>
