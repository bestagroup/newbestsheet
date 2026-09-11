<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">بارگذاری فایل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('storemedia') }}" enctype="multipart/form-data" class="dropzone" id="fileUploadZone" style="min-height: 200px; border: 2px dashed #ccc; border-style: dashed; padding: 20px;">
                    @csrf
                    <input type="hidden" name="record_id" id="recordIdInput">
                    <input type="hidden" name="subject_id" id="subjectIdInput">
                    <input type="hidden" name="title" id="fileTitleInput">

                    <div class="dz-message text-center text-muted">
                        <i class="bi bi-cloud-arrow-up" style="font-size: 3rem;"></i>
                        <h5 class="fw-bold my-2">برای آپلود فایل کلیک کنید یا فایل را بکشید</h5>
                        <p class="small text-secondary">فرمت‌های مجاز: JPG, PNG, PDF, MP4, DOCX (حداکثر 40 مگابایت)</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
