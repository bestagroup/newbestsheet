@if($card['url'])<a class="dash-metric tone-border-{{ $card['tone'] }}" href="{{ $card['url'] }}">@else<article class="dash-metric tone-border-{{ $card['tone'] }}">@endif
    <div class="dash-metric-top"><span>{{ $card['label'] }}</span><span class="dash-icon tone-{{ $card['tone'] }}"><i class="mdi {{ $card['icon'] }}" aria-hidden="true"></i></span></div>
    <strong class="dash-metric-value">{{ is_numeric($card['value']) ? \App\Support\Monetary::format($card['value']) : $card['value'] }}</strong>
    <span class="dash-metric-hint">{{ $card['hint'] }}@if($card['url'])<i class="mdi mdi-arrow-left" aria-hidden="true"></i>@endif</span>
@if($card['url'])</a>@else</article>@endif
