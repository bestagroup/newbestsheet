<div class="tab-pane fade" id="navs-contracts-card" role="tabpanel">
    <div class="alert alert-light border text-start">قراردادها در این سامانه به‌عنوان اسناد پرونده نگهداری می‌شوند. فقط اسناد مرتبط با قرارداد/توافق در این بخش نمایش داده می‌شوند.</div>
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>موضوع</th><th>فایل</th><th>وضعیت</th><th>تاریخ</th></tr></thead><tbody>
        @forelse($contractFiles as $file)
            <tr><td>{{ $file->subject?->title ?? 'قرارداد' }}</td><td><a href="{{ $file->url }}" target="_blank" rel="noopener">{{ $file->original_name ?: $file->name }}</a></td><td>{{ (int)$file->status===4 ? 'تأیید شده' : ((int)$file->status===5 ? 'رد شده' : 'در انتظار بررسی') }}</td><td>{{ $file->created_at ? jdate($file->created_at)->format('Y/m/d') : '—' }}</td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-4">سند قراردادی ثبت نشده است.</td></tr>@endforelse
        </tbody>
    </table></div></div></div>
</div>
