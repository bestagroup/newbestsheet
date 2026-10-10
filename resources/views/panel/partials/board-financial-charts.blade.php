<section class="mt-4" id="board-financial-charts" aria-labelledby="board-charts-title">
    <div class="card mb-3"><div class="card-body">
        <h5 id="board-charts-title">گزارش مالی شرکت برای مدیرعامل و هیئت‌مدیره</h5>
        <form method="GET" action="{{ route('report.index') }}#board-financial-charts" class="row g-3 align-items-end">
            <div class="col-md-5"><label for="chart-company" class="form-label">نام شرکت</label><select class="form-select" id="chart-company" name="chart_company">
                @forelse($boardCharts['companies'] as $company)<option value="{{ $company['id'] }}" @selected($company['id'] === $boardCharts['selectedCompany'])>{{ $company['name'] }}</option>@empty<option value="">شرکتی در محدوده دسترسی وجود ندارد</option>@endforelse
            </select></div>
            <div class="col-md-5"><label for="chart-period" class="form-label">دوره مالی شرکت</label><select class="form-select" id="chart-period" name="chart_period">
                @forelse($boardCharts['periods'] as $period)<option value="{{ $period['id'] }}" @selected($period['id'] === $boardCharts['selectedPeriod'])>{{ $period['label'] }}</option>@empty<option value="">صورت مالی ثبت نشده است</option>@endforelse
            </select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">نمایش نمودارها</button></div>
        </form>
        <p class="text-muted small mt-2 mb-0">این دو فیلتر فقط بخش نمودارهای مالی را تغییر می‌دهند. با تغییر شرکت، دوره‌های ثبت‌شده همان شرکت بارگذاری می‌شود.</p>
    </div></div>
    @if($boardCharts['periodReset'])<p class="small text-warning">دوره انتخاب‌شده برای این شرکت موجود نبود؛ آخرین دوره ثبت‌شده نمایش داده شد.</p>@endif
    @if($boardCharts['charts'])
        <div class="mb-3"><h6 class="fw-bold">{{ $boardCharts['companyName'] }} — {{ $boardCharts['periodLabel'] }}</h6>
            <p class="text-muted small">روند حداکثر شش سال، برای دوره‌های هم‌نوع و هم‌ماه پایان؛ واحد مبنا ریال. فرض مقایسه، یکسان‌بودن طول دوره و رویه ثبت اسناد است. خط تیره یعنی داده ناموجود یا غیرقابل محاسبه، نه صفر.</p>
            <div class="d-flex flex-wrap gap-3 small">@foreach($boardCharts['indicators'] as $item)<span><strong>{{ $item['label'] }}:</strong> {{ $item['value'] === null ? '—' : ($item['unit'] === 'ریال' ? \App\Support\Monetary::format($item['value']) : $item['value']) }} {{ $item['value'] === null ? '' : $item['unit'] }}</span>@endforeach</div>
        </div>
        @foreach($boardCharts['notes'] as $note)<p class="small text-warning mb-2">{{ $note }}</p>@endforeach
        <div class="row g-3">
            @foreach($boardCharts['charts'] as $chart)
                @php $hasData = collect($chart['series'])->contains(fn($series) => collect($series['values'])->contains(fn($value) => $value !== null)); @endphp
                <div class="col-12 col-xl-6"><div class="card h-100"><div class="card-body">
                    <h6 class="fw-bold">{{ $chart['title'] }}</h6><p class="text-muted small">{{ $chart['note'] }}</p>
                    @if($hasData)<div style="position:relative;height:330px"><canvas id="financial-chart-{{ $chart['id'] }}" role="img" aria-label="{{ $chart['title'] }}"></canvas></div>
                    @else<div class="border rounded text-center text-muted p-4">اقلام لازم برای ترسیم این نمودار ثبت نشده است.</div>@endif
                    <details class="mt-3"><summary class="small">مقادیر دقیق نمودار</summary><div class="table-responsive"><table class="table table-sm"><thead><tr><th>دوره / قلم</th>@foreach($chart['series'] as $series)<th>{{ $series['label'] }} ({{ $chart['unit'] }})</th>@endforeach</tr></thead><tbody>
                        @foreach($chart['labels'] as $index=>$label)<tr><td>{{ $label }}</td>@foreach($chart['series'] as $series)<td dir="ltr">{{ $series['values'][$index] === null ? '—' : ($chart['unit'] === 'ریال' ? \App\Support\Monetary::format($series['values'][$index]) : $series['values'][$index]) }}</td>@endforeach</tr>@endforeach
                    </tbody></table></div></details>
                </div></div></div>
            @endforeach
        </div>
    @else<div class="alert alert-info">برای شرکت منتخب صورت مالی قابل نمایش ثبت نشده است.</div>@endif
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('chart-company')?.addEventListener('change', event => {
            document.getElementById('chart-period').disabled = true;
            event.target.form.requestSubmit();
        });
    });
</script>
