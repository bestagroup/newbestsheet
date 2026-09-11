@php
    $summary = $financialReport['summary'];
    $formatFinancialValue = static function ($value): string {
        if ($value === null || $value === '') {
            return '—';
        }

        $normalized = str_replace([',', '٬', '،', ' '], '', (string) $value);
        $negative = str_starts_with($normalized, '-');
        $unsigned = $negative ? substr($normalized, 1) : $normalized;
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, null);
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $integer);

        return ($negative ? '-' : '').$grouped.($fraction !== null ? '.'.$fraction : '');
    };
@endphp

<div class="financial-report-wrap">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h6 class="mb-1 fw-bold">گزارش مالی شرکت</h6>
            <small class="text-muted">خلاصه آخرین دوره و جزئیات صورت‌های مالی ثبت‌شده برای همین شرکت</small>
        </div>
        @if($summary['period'])<span class="badge bg-label-primary">آخرین دوره: {{ $summary['period'] }}</span>@endif
    </div>

    @if($financialStatements->isNotEmpty())
        <div class="row g-3 mb-4">
            @foreach([
                ['فروش خالص', $summary['net_sales'], 'mdi-chart-line', 'primary'],
                ['سود / زیان خالص', $summary['net_profit'], 'mdi-cash-multiple', $summary['net_profit'] < 0 ? 'danger' : 'success'],
                ['جمع دارایی‌ها', $summary['total_assets'], 'mdi-bank-outline', 'info'],
                ['جمع بدهی‌ها', $summary['total_liabilities'], 'mdi-scale-balance', 'warning'],
                ['نسبت جاری', $summary['current_ratio'], 'mdi-percent-outline', 'primary'],
                ['بازده دارایی (ROA)', $summary['roa'].'٪', 'mdi-finance', $summary['roa'] < 0 ? 'danger' : 'success'],
            ] as [$label, $value, $icon, $tone])
                <div class="col-6 col-lg-2">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex align-items-center gap-2 text-{{ $tone }} mb-2"><i class="mdi {{ $icon }}"></i><small>{{ $label }}</small></div>
                        <strong class="d-block text-nowrap">{{ is_numeric($value) ? $formatFinancialValue($value) : $value }}</strong>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>دوره</th><th>فروش خالص</th><th>سود ناخالص</th><th>سود / زیان عملیاتی</th><th>سود / زیان خالص</th><th>دارایی‌ها</th><th>بدهی‌ها</th><th>حقوق مالکانه</th></tr></thead>
                <tbody>
                @foreach($financialStatements as $statement)
                    <tr>
                        <td class="text-nowrap"><strong>{{ sprintf('%04d/%02d', $statement->year, $statement->month) }}</strong></td>
                        <td>{{ $formatFinancialValue($statement->net_sales) }}</td>
                        <td>{{ $formatFinancialValue($statement->gross_profit) }}</td>
                        <td>{{ $formatFinancialValue($statement->operating_loss) }}</td>
                        <td class="{{ (float) $statement->net_profit < 0 ? 'text-danger' : '' }}">{{ $formatFinancialValue($statement->net_profit) }}</td>
                        <td>{{ $formatFinancialValue($statement->total_assets) }}</td>
                        <td>{{ $formatFinancialValue($statement->total_liabilities) }}</td>
                        <td>{{ $formatFinancialValue($statement->total_equity) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="accordion" id="financial-details-{{ $project->id }}">
            @foreach($financialStatements as $statement)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="financial-heading-{{ $statement->id }}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#financial-period-{{ $statement->id }}" aria-expanded="false">
                            جزئیات کامل صورت مالی دوره {{ sprintf('%04d/%02d', $statement->year, $statement->month) }}
                        </button>
                    </h2>
                    <div id="financial-period-{{ $statement->id }}" class="accordion-collapse collapse" data-bs-parent="#financial-details-{{ $project->id }}">
                        <div class="accordion-body"><div class="row g-3">
                            @foreach($financialFieldGroups as $groupTitle => $fields)
                                <div class="col-12 col-lg-4"><div class="border rounded-3 p-3 h-100">
                                    <h6 class="border-bottom pb-2 mb-2">{{ $groupTitle }}</h6>
                                    @foreach($fields as $field => $label)
                                        <div class="d-flex justify-content-between gap-3 py-1 small"><span class="text-muted">{{ $label }}</span><strong class="text-nowrap">{{ $formatFinancialValue($statement->{$field}) }}</strong></div>
                                    @endforeach
                                </div></div>
                            @endforeach
                        </div></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-light border text-center text-muted mb-0 py-4">
            <i class="mdi mdi-file-chart-outline mdi-36px d-block mb-2"></i>
            هنوز صورت مالی برای این شرکت ثبت نشده است.
        </div>
    @endif
</div>
