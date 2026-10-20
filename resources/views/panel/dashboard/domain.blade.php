<section class="dash-domain" id="domain-{{ $section['key'] }}" data-domain-panel="{{ $section['key'] }}" aria-labelledby="tab-{{ $section['key'] }}">
    <header class="dash-domain-heading"><div><h3>{{ $section['title'] }}</h3><p class="dash-muted">{{ $section['description'] }}</p></div><span class="dash-icon tone-{{ $section['tone'] }}"><i class="mdi {{ $section['icon'] }}" aria-hidden="true"></i></span></header>
    <div class="dash-metrics dash-domain-metrics">@foreach($section['cards'] as $card) @include('panel.dashboard.metric', ['card' => $card]) @endforeach</div>
    <div class="dash-analysis-grid">
        <section class="dash-panel dash-chart-panel" aria-labelledby="chart-title-{{ $section['key'] }}">
            <header class="dash-panel-heading"><h4 id="chart-title-{{ $section['key'] }}">{{ $section['chart']['title'] }}</h4><span class="dash-badge">{{ $section['chart']['unit'] }}</span></header>
            <p class="dash-muted">مقادیر نمودار و کارت‌ها با جستجوی فهرست تغییر نمی‌کنند.</p>
            @if(count($section['chart']['data']))
                <div class="dash-chart-wrap" style="--chart-height: {{ $section['chart']['type'] === 'bar' && $section['key'] !== 'finance' ? min(700, max(280, count($section['chart']['labels']) * 33)) : 300 }}px"><canvas id="domain-chart-{{ $section['key'] }}" role="img" aria-label="{{ $section['chart']['title'] }}؛ مقادیر دقیق در جدول داده‌ها"></canvas></div>
                <p class="dash-footnote" data-chart-status="{{ $section['key'] }}" role="status"></p>
                <details class="dash-chart-data" data-chart-table="{{ $section['key'] }}"><summary>جدول مقادیر دقیق <i class="mdi mdi-table" aria-hidden="true"></i></summary><div class="dash-table-scroll"><table><caption class="visually-hidden">{{ $section['chart']['title'] }}</caption><thead><tr><th scope="col">عنوان</th><th scope="col">{{ $section['chart']['unit'] }}</th></tr></thead><tbody>@foreach($section['chart']['labels'] as $index => $label)<tr><th scope="row">{{ $label }}</th><td dir="ltr" data-exact-number>{{ $section['chart']['data'][$index] ?? '—' }}</td></tr>@endforeach</tbody></table></div></details>
            @else
                <div class="dash-empty"><i class="mdi mdi-chart-box-outline" aria-hidden="true"></i><strong>داده‌ای برای نمودار وجود ندارد.</strong><span>نمودار پس از ثبت اطلاعات این حوزه نمایش داده می‌شود.</span></div>
            @endif
        </section>
        <section class="dash-panel dash-record-panel" aria-labelledby="list-title-{{ $section['key'] }}">
            <header class="dash-panel-heading"><h4 id="list-title-{{ $section['key'] }}">{{ $section['itemsTitle'] }}</h4><span class="dash-badge">{{ $section['items']->count() }} مورد</span></header>
            <p class="dash-muted">{{ $section['itemsDescription'] }}</p>
            <div class="dash-list" data-searchable-list="{{ $section['key'] }}">
                @include('panel.dashboard.search', ['listKey' => $section['key'], 'searchLabel' => $section['searchPlaceholder']])
                <div class="dash-filter-row"><label for="kind-{{ $section['key'] }}">نوع رکورد</label><select id="kind-{{ $section['key'] }}" class="dash-select" data-kind-filter><option value="">همه انواع</option>@foreach($section['items']->pluck('kind')->unique() as $kind)<option value="{{ $kind }}">{{ $kind }}</option>@endforeach</select></div>
                <div class="dash-list-rows dash-records" id="domain-list-{{ $section['key'] }}">
                    @forelse($section['items'] as $item)
                        <article class="dash-record" data-search-row data-kind="{{ $item['kind'] ?? 'مورد' }}">
                            <span class="dash-icon tone-{{ $item['tone'] ?? $section['tone'] }}"><i class="mdi {{ $item['icon'] ?? $section['icon'] }}" aria-hidden="true"></i></span>
                            <div class="dash-row-content"><div class="dash-record-title">@if($item['url'])<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@else<strong>{{ $item['title'] }}</strong>@endif<span class="dash-record-kind">{{ $item['kind'] ?? 'مورد' }}</span></div><span class="dash-muted">{{ $item['subtitle'] }}</span><div class="dash-record-meta"><span>{{ $item['meta'] }}</span>@if($item['created_at'] ?? null)<time datetime="{{ $item['created_at']->toIso8601String() }}">{{ $item['created_at']->locale('fa')->diffForHumans() }}</time>@endif</div></div>
                        </article>
                    @empty <div class="dash-empty"><i class="mdi mdi-folder-open-outline" aria-hidden="true"></i><strong>رکوردی برای نمایش نیست.</strong><span>فقط اطلاعات حوزه‌های مجاز شما نمایش داده می‌شود.</span></div> @endforelse
                </div><p class="dash-search-empty" data-search-empty hidden>موردی مطابق جستجو و نوع رکورد انتخابی پیدا نشد.</p>
            </div>
            <p class="dash-footnote">جستجو فقط در موارد نمایش‌داده‌شده است: حداکثر ۳۰ مورد اخیر؛ در بخش مالی و اداری حداکثر ۱۵ مورد از هر نوع. برای سوابق کامل وارد صفحه مربوط شوید.</p>
        </section>
    </div>
</section>
