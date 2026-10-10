<section class="mt-4" aria-labelledby="financial-charts-title">
    <h5 id="financial-charts-title">تحلیل مالی پورتفوی فعال</h5>
    <div class="alert alert-info">
        <strong>{{ $financialCharts['expected'] }} شرکت / پرونده مستقل</strong> در محدوده دسترسی و انتخاب شما؛ مراحل ۱۴ تا ۱۹ و ردنشده.
        نوع دوره: {{ ['annual'=>'سالانه', 'quarterly'=>'فصلی', 'legacy'=>'قدیمی و طبقه‌بندی‌نشده'][$financialCharts['periodType']] }}.
        <div class="small mt-1">واحد مبنا ریال است. نمودارهای دوره‌ای تنها وقتی عدد تجمیعی می‌دهند که اقلام لازم برای تمام شرکت‌های منتخب در همان دوره موجود باشد؛ نبود داده یا مخرج نامعتبر به‌صورت فاصله نمایش داده می‌شود، نه صفر. این جمع مدیریتی، صورت مالی تلفیقی یا بازده سرمایه‌گذار نیست.</div>
        <div class="small mt-1">مبنای شرکت‌ها پورتفوی فعال فعلی است؛ عضویت تاریخی پورتفو بازسازی نمی‌شود. شرکت بدون شناسه مشترک، پرونده مستقل محسوب می‌شود. فیلتر تاریخ صورت مالی بر ماه پایان دوره اعمال می‌شود.</div>
    </div>
    @if(!$financialCharts['incomeAllowed'])
        <div class="alert alert-warning">نمودارهای فروش، حاشیه سود و سود شرکت‌ها فعلاً محاسبه نمی‌شوند: برای داده قدیمی ابتدا نوع دوره را در صورت مالی تعیین کنید؛ برای داده فصلی، مبنای مستقل یا تجمعی را در فیلتر بالا تأیید کنید. نمودارهای ترازنامه همچنان قابل استفاده‌اند.</div>
    @elseif($financialCharts['periodType'] === 'quarterly')
        <p class="text-muted">مبنای سود و فروش: {{ $financialCharts['basis'] === 'ytd' ? 'تجمعی از ابتدای سال؛ دوره‌های سه‌ماهه مستقل نیستند و با هم جمع نمی‌شوند.' : 'ارقام مستقل هر فصل؛ براساس تأیید شما در فیلتر گزارش.' }}</p>
    @endif
    @if($financialCharts['invalidPeriods'])<div class="alert alert-warning">{{ $financialCharts['invalidPeriods'] }} صورت مالی با تاریخ دوره نامعتبر از نمودارها کنار گذاشته شد.</div>@endif
    @if($financialCharts['excludedPayments'])<div class="alert alert-warning">{{ $financialCharts['excludedPayments'] }} پرداخت با مبلغ نامعتبر از نمودار توزیع پرداخت کنار گذاشته شد؛ اسناد آن‌ها باید اصلاح شوند.</div>@endif
    <div class="row g-3">
        @foreach($financialCharts['charts'] as $chart)
            @php $hasData = collect($chart['series'])->contains(fn($series) => collect($series['values'])->contains(fn($value) => $value !== null)); @endphp
            <div class="col-12 col-xl-6">
                <div class="card h-100 report-card"><div class="card-body">
                    <h6 class="fw-bold">{{ $chart['title'] }}</h6>
                    <p class="text-muted small">{{ $chart['note'] }}</p>
                    @if($hasData)
                        <div style="max-height:560px;overflow:auto">
                            <div style="position:relative;height:{{ $chart['type'] === 'horizontal' ? max(300, count($chart['labels']) * 36 + 65) : 300 }}px">
                                <canvas id="financial-chart-{{ $chart['id'] }}" role="img" aria-label="{{ $chart['title'] }}؛ داده دقیق در جدول زیر"></canvas>
                            </div>
                        </div>
                    @else
                        <div class="text-muted text-center border rounded p-4">داده کامل و قابل مقایسه برای این نمودار وجود ندارد؛ جزئیات پوشش را بررسی کنید.</div>
                    @endif
                    <details class="mt-3"><summary class="small">جدول داده دقیق و مقادیر ناموجود</summary>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>دوره / شرکت</th>@foreach($chart['series'] as $series)<th>{{ $series['label'] }} ({{ $chart['unit'] }})</th>@endforeach</tr></thead><tbody>
                            @forelse($chart['labels'] as $index=>$label)
                                <tr><td>{{ $label }}</td>@foreach($chart['series'] as $series)<td dir="ltr">{{ $series['values'][$index] === null ? '—' : ($chart['unit'] === 'ریال' ? \App\Support\Monetary::format($series['values'][$index]) : $series['values'][$index]) }}</td>@endforeach</tr>
                            @empty<tr><td colspan="{{ count($chart['series']) + 1 }}">داده‌ای ثبت نشده است.</td></tr>@endforelse
                        </tbody></table></div>
                    </details>
                </div></div>
            </div>
        @endforeach
    </div>
    <details class="card p-3 mt-3"><summary class="fw-bold">کنترل پوشش اقلام مالی به تفکیک دوره</summary>
        <p class="text-muted small mt-2">اعداد هر ستون، تعداد شرکت‌های دارای مقدار معتبر از کل {{ $financialCharts['expected'] }} شرکت است؛ صفر ثبت‌شده معتبر است. اگر یک شرکت در چند پرونده صورت مالی همان دوره داشته باشد، تا رفع تکرار از محاسبات کنار گذاشته می‌شود.</p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>دوره</th><th>یکتا</th><th>تکراری</th>@foreach(['فروش','سود خالص','سود ناخالص','دارایی جاری','بدهی جاری','وجه نقد','کل دارایی','کل بدهی','حقوق مالکانه'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
            @foreach($financialCharts['periods'] as $period)<tr><td>{{ $period['period'] }}</td><td>{{ $period['usable'] }}</td><td>{{ $period['conflicts'] }}</td>@foreach($period['coverage'] as $count)<td>{{ $count }}/{{ $period['expected'] }}</td>@endforeach</tr>@endforeach
        </tbody></table></div>
    </details>
    <details class="card p-3 mt-3"><summary class="fw-bold">کنترل ترازنامه و کیفیت داده آخرین دوره: {{ $financialCharts['latestPeriod'] ?: '—' }}</summary>
        <p class="text-muted small mt-2">اختلاف = دارایی − (بدهی + حقوق مالکانه). اختلاف صفر فقط برابری حسابی را نشان می‌دهد؛ تأیید صحت یا حسابرسی صورت مالی نیست.</p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>شرکت</th><th>صورت مالی یکتا</th><th>اقلام ناموجود از ۹ قلم</th><th>اختلاف ترازنامه (ریال)</th><th>حقوق مالکانه منفی</th></tr></thead><tbody>
            @foreach($financialCharts['quality'] as $row)<tr><td>{{ $row['company'] }}</td><td>{{ $row['available'] ? 'موجود' : 'ناموجود / تکراری' }}</td><td>{{ $row['missing_fields'] }}</td><td>{{ $row['balance_difference'] === null ? '—' : \App\Support\Monetary::format($row['balance_difference']) }}</td><td>{{ $row['negative_equity'] === null ? '—' : ($row['negative_equity'] ? 'بله' : 'خیر') }}</td></tr>@endforeach
        </tbody></table></div>
    </details>
</section>
