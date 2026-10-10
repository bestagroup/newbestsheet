<section class="mt-4" id="board-financial-charts" aria-labelledby="board-charts-title">
    <div class="card mb-3"><div class="card-body">
        <h5 id="board-charts-title">گزارش مالی شرکت‌ها برای مدیرعامل و هیئت‌مدیره</h5>
        <form method="GET" action="{{ route('report.index') }}#board-financial-charts" class="row g-3 align-items-end">
            <div class="col-md-10"><label for="chart-companies" class="form-label">شرکت‌ها برای مقایسه</label>
                <select class="form-select" id="chart-companies" name="chart_companies[]" multiple size="5" aria-describedby="company-selection-help">
                    @foreach($boardCharts['companies'] as $company)<option value="{{ $company['id'] }}" @selected(in_array($company['id'],$boardCharts['selectedCompanies'],true))>{{ $company['name'] }}</option>@endforeach
                </select>
                <small id="company-selection-help" class="text-muted">یک، دو یا چند شرکت را انتخاب کنید و نمایش نمودارها را بزنید. در انتخاب‌گر ساده از Ctrl یا Command برای انتخاب چند مورد استفاده کنید.</small>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">نمایش نمودارها</button></div>
        </form>
    </div></div>
    <p class="text-muted small">تمام دوره‌های ثبت‌شده شرکت‌های منتخب نمایش داده می‌شود؛ هر شرکت رنگ ثابتی دارد. با کلیک روی نام شرکت در راهنمای هر نمودار می‌توانید سری آن را پنهان یا نمایان کنید. مقادیر ریالی، اسمی و بدون تعدیل تورم‌اند.</p>
    <p class="text-muted small">سالانه، فصلی و داده قدیمی در نمودارهای جدا نمایش داده می‌شوند. نقطه ناموجود به معنی صفر نیست. مبنای مستقل یا تجمعی صورت فصلی در اسناد سامانه ثبت نشده؛ مقایسه اقتصادی مستلزم یکسان‌بودن طول دوره و رویه ثبت شرکت‌هاست.</p>
    @foreach($boardCharts['notes'] as $note)<p class="small text-warning">{{ $note }}</p>@endforeach
    <div class="row g-3">
        @forelse($boardCharts['charts'] as $chart)
            @php $hasData = collect($chart['series'])->contains(fn($series) => collect($series['values'])->contains(fn($value) => $value !== null)); @endphp
            <div class="col-12 col-xl-6"><div class="card h-100"><div class="card-body">
                <h6 class="fw-bold">{{ $chart['title'] }}</h6><p class="text-muted small">{{ $chart['note'] }}</p>
                @if($hasData)
                    <div style="overflow-x:auto"><div style="position:relative;height:360px;min-width:{{ max(300,count($chart['labels'])*65) }}px"><canvas id="financial-chart-{{ $chart['id'] }}" role="img" aria-label="{{ $chart['title'] }}؛ مقایسه شرکت‌های منتخب"></canvas></div></div>
                @else<div class="border rounded text-center text-muted p-4">اقلام لازم برای این نمودار ثبت نشده است.</div>@endif
                <details class="mt-3"><summary class="small">مقادیر دقیق مقایسه</summary><div class="table-responsive"><table class="table table-sm"><thead><tr><th>دوره</th>@foreach($chart['series'] as $series)<th>{{ $series['label'] }} ({{ $chart['unit'] }})</th>@endforeach</tr></thead><tbody>
                    @foreach($chart['labels'] as $index=>$label)<tr><td>{{ $label }}</td>@foreach($chart['series'] as $series)<td dir="ltr">{{ $series['values'][$index] === null ? '—' : ($chart['unit'] === 'ریال' ? \App\Support\Monetary::format($series['values'][$index]) : $series['values'][$index]) }}</td>@endforeach</tr>@endforeach
                </tbody></table></div></details>
            </div></div></div>
        @empty<div class="col-12"><div class="alert alert-info">برای شرکت‌های منتخب صورت مالی قابل نمایش ثبت نشده است.</div></div>@endforelse
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('chart-companies');
        if (window.jQuery?.fn?.select2 && !jQuery(select).hasClass('select2-hidden-accessible')) {
            jQuery(select).select2({width:'100%',dir:'rtl',placeholder:'انتخاب یک یا چند شرکت',closeOnSelect:false});
        }
    });
</script>
