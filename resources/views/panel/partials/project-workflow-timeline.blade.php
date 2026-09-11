@php
    $statusMeta = [
        'locked' => ['آتی', 'secondary', 'mdi-lock-outline'],
        'awaiting_assignment' => ['در انتظار تخصیص', 'warning', 'mdi-account-clock-outline'],
        'awaiting_documents' => ['در انتظار مدارک', 'info', 'mdi-file-clock-outline'],
        'under_review' => ['در حال بررسی', 'primary', 'mdi-magnify'],
        'approved' => ['تأیید شده', 'success', 'mdi-check-circle-outline'],
        'rejected' => ['رد شده', 'danger', 'mdi-close-circle-outline'],
    ];
@endphp

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="mb-1">گردش‌کار کامل پرونده</h6>
            <div class="text-muted small">هر ردیف نمونه مستقل یک مرحله ثابت برای همین طرح است.</div>
        </div>
        <span class="badge bg-label-primary">{{ $projectStageInstances->count() }} مرحله</span>
    </div>

    <div class="vstack gap-2">
        @forelse($projectStageInstances as $stage)
            @php
                $status = $stage->status instanceof \BackedEnum ? $stage->status->value : (string) $stage->status;
                [$label, $color, $icon] = $statusMeta[$status] ?? [$status, 'secondary', 'mdi-circle-outline'];
            @endphp
            <div class="border rounded-3 p-3 bg-white">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="badge rounded-pill bg-label-{{ $color }}">{{ $stage->sequence }}</span>
                        <div>
                            <div class="fw-semibold">{{ $stage->title_snapshot }}</div>
                            <div class="small text-muted">کد: {{ $stage->stage_code }}</div>
                        </div>
                    </div>
                    <span class="badge bg-{{ $color }}"><i class="mdi {{ $icon }} me-1"></i>{{ $label }}</span>
                </div>

                <div class="row g-2 mt-2 small">
                    <div class="col-md-6">
                        <span class="text-muted">مسئول بررسی:</span>
                        @forelse($stage->activeAssignments as $assignment)
                            <span class="badge bg-label-dark ms-1">{{ $assignment->user?->name }} — {{ $assignment->role?->title_fa }}</span>
                        @empty
                            <span class="text-warning">تخصیص نیافته</span>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <span class="text-muted">مهلت:</span>
                        <span>{{ $stage->due_at ? jdate($stage->due_at)->format('Y/m/d H:i') : 'تعریف نشده' }}</span>
                    </div>
                </div>

                @if($stage->comments->isNotEmpty())
                    <div class="mt-3 pt-2 border-top">
                        @foreach($stage->comments as $comment)
                            <div class="small mb-2">
                                <span class="fw-semibold">{{ $comment->author?->name ?? 'کاربر سامانه' }}:</span>
                                <span>{{ $comment->body }}</span>
                                <span class="text-muted ms-1">{{ jdate($comment->created_at)->format('Y/m/d H:i') }}</span>
                                @if($comment->visibility === 'internal')<span class="badge bg-label-secondary ms-1">داخلی</span>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="alert alert-warning mb-0">نمونه مراحل پرونده هنوز ایجاد نشده است.</div>
        @endforelse
    </div>
</div>
