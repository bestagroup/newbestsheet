<div class="dash-search-controls">
    <label class="visually-hidden" for="search-{{ $listKey }}">{{ $searchLabel }}</label>
    <div class="dash-search-field"><i class="mdi mdi-magnify" aria-hidden="true"></i><input id="search-{{ $listKey }}" class="js-domain-search" data-list-search type="search" maxlength="160" placeholder="{{ $searchLabel }}" autocomplete="off"><button type="button" data-clear-search aria-label="پاک کردن جستجو"><i class="mdi mdi-close" aria-hidden="true"></i></button></div>
    <div class="dash-search-options"><label><input type="checkbox" data-exact-search> عبارت پیوسته</label><span class="dash-result-count" data-result-count role="status" aria-live="polite"></span></div>
</div>
