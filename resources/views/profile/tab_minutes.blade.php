<div class="tab-pane fade" id="navs-minutes-card" role="tabpanel">
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>عنوان</th><th>تاریخ</th><th>نوع</th><th>فایل</th></tr></thead><tbody>
        @forelse($minutes as $minute)
            <tr><td>{{ $minute->title }}</td><td>{{ $minute->date ?: '—' }}</td><td>{{ $minute->type ?: '—' }}</td><td>@if($minute->file_path)<a href="{{ asset('storage/'.$minute->file_path) }}" target="_blank" rel="noopener">مشاهده</a>@else—@endif</td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-4">صورتجلسه‌ای برای این پرونده ثبت نشده است.</td></tr>@endforelse
        </tbody>
    </table></div></div></div>
</div>
