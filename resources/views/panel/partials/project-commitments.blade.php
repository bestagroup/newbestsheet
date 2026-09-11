@php
    $assignedCommitmentIds = $projectCommitments->pluck('commitment_id');
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 bg-transparent border-bottom">
        <div>
            <h6 class="mb-1 fw-bold">تعهدات زمان‌بندی‌شده پروژه</h6>
            <small class="text-muted">سررسید تعهدات مبنای یادآوری و هشدارهای عملیاتی است.</small>
        </div>
        <span class="badge bg-label-primary">{{ $projectCommitments->count() }} تعهد</span>
    </div>
    <div class="card-body">
        @if($canManagePortfolioRecords)
            <form action="{{ route('flow.commitments.store', $project->id) }}" method="POST"
                  class="project-commitment-form row g-3 align-items-end mb-4"
                  data-project-id="{{ $project->id }}">
                @csrf
                <div class="col-lg-5 col-md-6">
                    <label class="form-label">تعهد</label>
                    <select name="commitment_id" class="form-select" required>
                        <option value="">انتخاب کنید</option>
                        @foreach($commitmentTemplates->whereNotIn('id', $assignedCommitmentIds) as $template)
                            <option value="{{ $template->id }}">{{ \Illuminate\Support\Str::limit($template->title, 110) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label">تاریخ سررسید</label>
                    <input type="text" name="due_date" class="form-control" data-jdp autocomplete="off" placeholder="1405/06/31" required>
                </div>
                <div class="col-lg-4 col-md-9">
                    <label class="form-label">توضیحات</label>
                    <input type="text" name="notes" class="form-control" maxlength="5000" placeholder="اختیاری">
                </div>
                <div class="col-lg-1 col-md-3">
                    <button type="submit" class="btn btn-primary w-100">ثبت</button>
                </div>
            </form>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>شرح تعهد</th>
                    <th style="width: 130px">سررسید</th>
                    <th style="width: 130px">وضعیت</th>
                    <th>یادداشت</th>
                    @if($canManagePortfolioRecords)
                        <th style="width: 280px">عملیات</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($projectCommitments as $item)
                    @php
                        $statusLabel = match ($item->status) {
                            'completed' => 'انجام شده',
                            'waived' => 'مختومه/منتفی',
                            default => 'در انتظار',
                        };
                        $statusClass = match ($item->status) {
                            'completed' => 'success',
                            'waived' => 'secondary',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td style="white-space: normal">{{ $item->commitment?->title ?: '—' }}</td>
                        <td>{{ $item->due_date ?: '—' }}</td>
                        <td><span class="badge bg-label-{{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td>{{ $item->notes ?: '—' }}</td>
                        @if($canManagePortfolioRecords)
                            <td>
                                <form action="{{ route('flow.commitments.update', [$project->id, $item->id]) }}" method="POST"
                                      class="project-commitment-update-form d-flex flex-wrap gap-1 align-items-center"
                                      data-project-id="{{ $project->id }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="commitment_id" value="{{ $item->commitment_id }}">
                                    <input type="text" name="due_date" value="{{ $item->due_date }}" data-jdp autocomplete="off"
                                           class="form-control form-control-sm" style="min-width: 100px" required>
                                    <select name="status" class="form-select form-select-sm" style="min-width: 105px">
                                        <option value="pending" @selected($item->status === 'pending')>در انتظار</option>
                                        <option value="completed" @selected($item->status === 'completed')>انجام شده</option>
                                        <option value="waived" @selected($item->status === 'waived')>مختومه</option>
                                    </select>
                                    <input type="hidden" name="notes" value="{{ $item->notes }}">
                                    <button class="btn btn-sm btn-outline-primary" type="submit">ذخیره</button>
                                </form>
                                <form action="{{ route('flow.commitments.destroy', [$project->id, $item->id]) }}" method="POST"
                                      class="project-commitment-delete-form d-inline"
                                      data-project-id="{{ $project->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger mt-1" type="submit">حذف زمان‌بندی</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManagePortfolioRecords ? 5 : 4 }}" class="text-center text-muted py-4">
                            تعهد زمان‌بندی‌شده‌ای برای این پروژه ثبت نشده است.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
