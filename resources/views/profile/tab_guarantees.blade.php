<div class="tab-pane fade" id="navs-guarantee-card" role="tabpanel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h5 class="mb-1 fw-bold">تعهدات پرونده</h5><small class="text-muted">تعهدات تعریف‌شده و وضعیت زمان‌بندی اجرای آن‌ها</small></div>
        <span class="badge bg-label-primary px-3 py-2">{{ isset($commitments) ? $commitments->count() : 0 }} مورد</span>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            @if(isset($commitments) && $commitments->isNotEmpty())
                <div class="row g-3">
                    @foreach($commitments as $item)
                        @php
                            $schedule = ($projectCommitmentSchedules ?? collect())->get($item->id);
                            $statusMeta = match ($schedule?->status) {
                                'completed' => ['label' => 'انجام شده', 'class' => 'success'],
                                'waived' => ['label' => 'مختومه', 'class' => 'secondary'],
                                'pending' => ['label' => 'در انتظار', 'class' => 'warning'],
                                default => ['label' => 'زمان‌بندی نشده', 'class' => 'secondary'],
                            };
                        @endphp
                        <div class="col-xl-4 col-md-6">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-primary fw-bold" style="width:36px;height:36px">{{ $loop->iteration }}</span>
                                    <div class="text-end"><span class="badge bg-label-{{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>@if($schedule?->due_date)<div class="small text-muted mt-1">سررسید {{ $schedule->due_date }}</div>@endif</div>
                                </div>
                                <div class="fw-semibold" style="line-height:1.9">{{ $item->title }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted py-5"><i class="mdi mdi-clipboard-text-outline mdi-36px d-block mb-2"></i>تعهدی برای این پرونده ثبت نشده است.</div>
            @endif
        </div>
    </div>
</div>
