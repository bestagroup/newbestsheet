@php
    $kpiTypes = \App\Support\KpiOptions::TYPES;
    $kpiBases = \App\Support\KpiOptions::BASES;
    $kpiUnits = \App\Support\KpiOptions::UNITS;
    $kpiPeriods = \App\Support\KpiOptions::PERIODS;
    $canEditKpis = $canManagePortfolioRecords || $canReviewKpis;
    $currentKpiCount = $kpis->where('is_current', true)->count();
    $reviewMeta = [
        'pending' => ['در انتظار بررسی سرمایه‌گذار', 'warning'],
        'approved' => ['تأیید سرمایه‌گذار', 'success'],
        'rejected' => ['رد سرمایه‌گذار', 'danger'],
        'superseded' => ['نسخه پیشین', 'secondary'],
    ];
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-1 fw-bold">شاخص‌های کلیدی عملکرد پروژه</h6>
            <small class="text-muted">نسخه جاری، سوابق تغییرات و تصمیم کارشناس سرمایه‌گذار</small>
        </div>
        <span class="badge bg-label-primary">{{ $currentKpiCount }} شاخص جاری</span>
    </div>
    <div class="card-body">
        @if($canManagePortfolioRecords)
            <form action="{{ route('flow.kpis.store', $project->id) }}" method="POST"
                  class="project-kpi-form row g-3 align-items-end mb-4" data-project-id="{{ $project->id }}">
                @csrf
                <div class="col-md-1"><label class="form-label">شماره</label><input type="number" min="1" name="kpi_number" class="form-control" required></div>
                <div class="col-md-3"><label class="form-label">عنوان</label><input type="text" name="title" class="form-control" required></div>
                <div class="col-md-2"><label class="form-label">نوع</label><select name="type" class="form-control"><option value="">انتخاب کنید</option>@foreach($kpiTypes as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">مبنا</label><select name="type_value" class="form-control"><option value="">انتخاب کنید</option>@foreach($kpiBases as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">مقدار هدف</label><input type="text" name="value" class="form-control"></div>
                <div class="col-md-2"><label class="form-label">واحد</label><select name="unit" class="form-control"><option value="">انتخاب کنید</option>@foreach($kpiUnits as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">مهلت</label><input type="text" name="deadline" class="form-control" data-jdp autocomplete="off" placeholder="1405/06/31"></div>
                <div class="col-md-3"><label class="form-label">دوره اندازه‌گیری</label><select name="period_time" class="form-control"><option value="">انتخاب کنید</option>@foreach($kpiPeriods as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100">ثبت KPI</button></div>
            </form>
        @endif

        @if($canEditKpis)
            <div class="kpi-edit-panel border rounded p-3 mb-4 d-none" data-project-id="{{ $project->id }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div><strong>ثبت نسخه جدید KPI</strong><div class="text-muted small mt-1">نسخه قبلی بدون تغییر در تاریخچه باقی می‌ماند.</div></div>
                    <button type="button" class="btn btn-sm btn-label-secondary kpi-edit-cancel">انصراف</button>
                </div>
                <form method="POST" class="project-kpi-update-form row g-3 align-items-end" data-project-id="{{ $project->id }}">
                    @csrf @method('PATCH')
                    <div class="col-md-1"><label class="form-label">شماره</label><input type="number" min="1" name="kpi_number" class="form-control kpi-number" required></div>
                    <div class="col-md-3"><label class="form-label">عنوان</label><input type="text" name="title" class="form-control kpi-title" required></div>
                    <div class="col-md-2"><label class="form-label">نوع</label><select name="type" class="form-control kpi-type"><option value="">انتخاب کنید</option>@foreach($kpiTypes as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">مبنا</label><select name="type_value" class="form-control kpi-type-value"><option value="">انتخاب کنید</option>@foreach($kpiBases as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">مقدار هدف</label><input type="text" name="value" class="form-control kpi-value"></div>
                    <div class="col-md-2"><label class="form-label">واحد</label><select name="unit" class="form-control kpi-unit"><option value="">انتخاب کنید</option>@foreach($kpiUnits as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">مهلت</label><input type="text" name="deadline" class="form-control kpi-deadline" data-jdp autocomplete="off" placeholder="1405/06/31"></div>
                    <div class="col-md-2"><label class="form-label">دوره</label><select name="period_time" class="form-control kpi-period-time"><option value="">انتخاب کنید</option>@foreach($kpiPeriods as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
                    <div class="col-md-1"><div class="form-check"><input type="hidden" name="completed" value="0"><input class="form-check-input kpi-completed" type="checkbox" name="completed" value="1"><label class="form-check-label">تکمیل</label></div></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100">ثبت نسخه جدید</button></div>
                </form>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light"><tr><th>شماره</th><th>نسخه</th><th>عنوان</th><th>نوع / مبنا</th><th>هدف</th><th>مهلت / دوره</th><th>وضعیت اجرا</th><th>نظر سرمایه‌گذار</th>@if($canEditKpis || $canManagePortfolioRecords)<th>عملیات</th>@endif</tr></thead>
                <tbody>
                @forelse($kpis as $kpi)
                    @php
                        $rawKpiValue = (string) ($kpi->value ?? '');
                        $numericKpiValue = str_replace([',', '٬', '،', ' '], '', $rawKpiValue);
                        $displayKpiValue = $numericKpiValue !== '' && is_numeric($numericKpiValue) ? number_format((float) $numericKpiValue) : $rawKpiValue;
                        [$reviewLabel, $reviewColor] = $reviewMeta[$kpi->review_status] ?? [$kpi->review_status ?: 'در انتظار بررسی', 'warning'];
                        $columnCount = ($canEditKpis || $canManagePortfolioRecords) ? 9 : 8;
                    @endphp
                    <tr class="{{ $kpi->is_current ? '' : 'table-light text-muted' }}">
                        <td>{{ $kpi->kpi_number }}</td>
                        <td><span class="badge bg-label-{{ $kpi->is_current ? 'primary' : 'secondary' }}">نسخه {{ $kpi->revision_number ?: 1 }}</span></td>
                        <td><strong class="d-block">{{ $kpi->title }}</strong><small class="text-muted">{{ $kpi->code }}</small></td>
                        <td>{{ $kpi->type ?: '—' }}<div class="small text-muted">{{ $kpi->type_value ?: 'بدون مبنا' }}</div></td>
                        <td>{{ $displayKpiValue ?: '—' }} {{ $kpi->unit }}</td>
                        <td>{{ trim(($kpi->deadline ?? '').' '.($kpi->period_time ?? $kpi->time_step ?? '')) ?: '—' }}</td>
                        <td><span class="badge bg-label-{{ $kpi->completed_at ? 'success' : 'warning' }}">{{ $kpi->completed_at ? 'تکمیل شده' : 'در انتظار' }}</span></td>
                        <td>
                            <span class="badge bg-label-{{ $reviewColor }}">{{ $reviewLabel }}</span>
                            @if($kpi->reviewedBy)<div class="small text-muted mt-1">{{ $kpi->reviewedBy->name }} — {{ $kpi->reviewed_at ? jdate($kpi->reviewed_at)->format('Y/m/d H:i') : '' }}</div>@endif
                            @if($kpi->review_comment)<div class="small mt-1">{{ $kpi->review_comment }}</div>@endif
                        </td>
                        @if($canEditKpis || $canManagePortfolioRecords)
                            <td class="text-nowrap">
                                @if($kpi->is_current && $canEditKpis)
                                    <button type="button" class="btn btn-sm btn-outline-primary kpi-edit-btn"
                                            data-url="{{ route('flow.kpis.update', [$project->id, $kpi->id]) }}" data-kpi-number="{{ $kpi->kpi_number }}"
                                            data-title="{{ $kpi->title }}" data-type="{{ $kpi->type }}" data-type-value="{{ $kpi->type_value }}"
                                            data-value="{{ $kpi->value }}" data-unit="{{ $kpi->unit }}" data-deadline="{{ $kpi->deadline }}"
                                            data-period-time="{{ $kpi->period_time ?? $kpi->time_step }}" data-completed="{{ $kpi->completed_at ? 1 : 0 }}">ویرایش و ایجاد نسخه</button>
                                @endif
                                @if($kpi->is_current && $canReviewKpis)
                                    <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#kpi-review-{{ $kpi->id }}" aria-expanded="false">تأیید / رد</button>
                                @endif
                                @if($kpi->is_current && !$kpi->root_kpi_id && $canManagePortfolioRecords)
                                    <form action="{{ route('flow.kpis.destroy', [$project->id, $kpi->id]) }}" method="POST" class="project-kpi-delete-form d-inline" data-project-id="{{ $project->id }}">
                                        @csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                    @if($kpi->is_current && $canReviewKpis)
                        <tr class="border-0"><td colspan="{{ $columnCount }}" class="p-0 border-0">
                            <div class="collapse" id="kpi-review-{{ $kpi->id }}"><div class="border border-success-subtle rounded p-3 my-2 mx-1 bg-label-success">
                                <form action="{{ route('flow.kpis.review', [$project->id, $kpi->id]) }}" method="POST"
                                      class="project-kpi-review-form row g-2 align-items-end" data-project-id="{{ $project->id }}">
                                    @csrf @method('PATCH')
                                    <div class="col-md-3"><label class="form-label">تصمیم کارشناس سرمایه‌گذار</label><select name="decision" class="form-select" required><option value="approved">تأیید</option><option value="rejected">رد</option></select></div>
                                    <div class="col-md-7"><label class="form-label">توضیحات (برای رد الزامی)</label><input type="text" name="review_comment" class="form-control" maxlength="5000" placeholder="نظر یا دلیل تصمیم"></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-success w-100">ثبت تصمیم</button></div>
                                </form>
                            </div></div>
                        </td></tr>
                    @endif
                @empty
                    <tr><td colspan="{{ ($canEditKpis || $canManagePortfolioRecords) ? 9 : 8 }}" class="text-center text-muted py-4">شاخصی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
