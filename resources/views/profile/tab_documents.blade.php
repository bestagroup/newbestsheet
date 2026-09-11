<div class="tab-pane fade" id="navs-documents-card" role="tabpanel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h5 class="mb-1 fw-bold">اسناد پرونده</h5><small class="text-muted">مدارک بارگذاری‌شده و نتیجه بررسی هر سند</small></div>
        <span class="badge bg-label-primary px-3 py-2">{{ $files->count() }} فایل</span>
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>موضوع</th><th>نام فایل</th><th>وضعیت</th><th>تاریخ</th><th class="text-end">عملیات</th></tr></thead>
            <tbody>
            @forelse($files as $file)
                <tr>
                    <td><span class="badge bg-label-secondary">{{ $file->subject?->title ?? '—' }}</span></td>
                    <td><div class="d-flex align-items-center gap-2"><i class="mdi mdi-file-document-outline text-primary mdi-20px"></i><span>{{ $file->original_name ?: $file->name }}</span></div></td>
                    <td>
                        @if((int)$file->status === 4)<span class="badge bg-label-success"><i class="mdi mdi-check-circle-outline me-1"></i>تأیید شده</span>
                        @elseif((int)$file->status === 5)<span class="badge bg-label-danger"><i class="mdi mdi-close-circle-outline me-1"></i>رد شده</span>
                        @else<span class="badge bg-label-warning"><i class="mdi mdi-clock-outline me-1"></i>در انتظار بررسی</span>@endif
                    </td>
                    <td>{{ $file->created_at ? jdate($file->created_at)->format('Y/m/d') : '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="{{ $file->url }}" target="_blank" rel="noopener"><i class="mdi mdi-eye-outline me-1"></i>مشاهده</a>
                        @if((int)$file->user_id === (int)auth()->id() && (int)$file->status !== 4)
                            <button type="button" class="btn btn-sm btn-outline-danger investee-document-delete" data-id="{{ $file->id }}"><i class="mdi mdi-delete-outline"></i></button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-5"><i class="mdi mdi-folder-open-outline mdi-36px d-block mb-2"></i>سندی ثبت نشده است. اسناد موردنیاز هر مرحله را از تب «مراحل سرمایه‌گذاری» بارگذاری کنید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div></div>
</div>

<div class="modal fade" id="investeeUploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title mb-1" id="investeeUploadTitle">بارگذاری سند</h5><small class="text-muted" id="investeeUploadHint">فایل مرتبط با مرحله جاری پرونده</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="investeeDocumentForm" action="{{ route('profile.documents.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <input type="hidden" name="subject_id" id="investeeDocumentSubject">
                <input type="hidden" name="document_requirement_id" id="investeeDocumentRequirement">
                <label class="form-label" for="investeeDocumentFiles">انتخاب فایل یا فایل‌ها</label>
                <input type="file" class="form-control" name="files[]" id="investeeDocumentFiles" multiple required>
                <div class="form-text" id="investeeSelectedFiles">هنوز فایلی انتخاب نشده است.</div>
                <div class="alert alert-info border-0 mt-3 mb-0 small"><i class="mdi mdi-information-outline me-1"></i>در هر بار تا ۲۰ فایل و برای هر فایل حداکثر 50MB؛ PDF، تصویر، Word، Excel، PowerPoint و ZIP. فایل‌ها پس از کنترل امنیتی در دسترس قرار می‌گیرند.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button><button type="submit" class="btn btn-primary"><i class="mdi mdi-upload-outline me-1"></i>بارگذاری</button></div>
        </form>
    </div></div>
</div>
