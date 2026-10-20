@php
    $statement = $statement ?? null;
    $idPrefix = $idPrefix ?? 'statement';
    $selectedProjectId = old('project_id', data_get($statement, 'project_id'));
    $selectedYear = old('year', data_get($statement, 'year'));
    $selectedMonth = old('month', data_get($statement, 'month', 12));
@endphp
@if($statement)
    <input type="hidden" name="lock_version" value="{{ $statement->lock_version }}">
@endif

<div class="col-12">
    <div class="alert alert-primary d-flex align-items-start gap-2 mb-0" role="note">
        <i class="mdi mdi-information-outline mdi-20px mt-1"></i>
        <div>
            <strong>دوره مالی را دقیق انتخاب کنید.</strong>
            سال و ماه، مربوط به پایان دوره شمسی است. در مبنای مستقل، ارقام سود و زیان فقط مربوط به بازه واردشده است؛ در مبنای تجمعی، شروع بازه باید ابتدای سال مالی شرکت باشد. اقلام ترازنامه همواره مانده پایان دوره‌اند. سوابق تعیین‌نشده تا تکمیل اطلاعات، جدا نمایش داده می‌شوند.
        </div>
    </div>
</div>

<div class="col-12 col-lg-6">
    <div class="form-floating form-floating-outline">
        <select required name="project_id" id="{{ $idPrefix }}_project_id" class="form-select statement-project-select">
            <option value="">انتخاب شرکت پورتفو</option>
            @foreach($projects as $project)
                @php
                    $companyName = $project->company_name ?: $project->title;
                    $projectSuffix = $project->title && $project->title !== $companyName ? ' — '.$project->title : '';
                @endphp
                <option value="{{ $project->id }}" @selected((string) $project->id === (string) $selectedProjectId)>
                    {{ $companyName.$projectSuffix }}
                </option>
            @endforeach
        </select>
        <label for="{{ $idPrefix }}_project_id">شرکت پورتفو</label>
    </div>
</div>

<div class="col-6 col-lg-3">
    <div class="form-floating form-floating-outline">
        <input required type="text" inputmode="numeric" maxlength="4" class="form-control"
               name="year" id="{{ $idPrefix }}_year" value="{{ $selectedYear }}" placeholder="۱۴۰۴">
        <label for="{{ $idPrefix }}_year">سال مالی</label>
    </div>
</div>

<div class="col-6 col-lg-3">
    <div class="form-floating form-floating-outline">
        <select required name="month" id="{{ $idPrefix }}_month" class="form-select">
            @foreach(range(1, 12) as $month)
                <option value="{{ $month }}" @selected((int) $selectedMonth === $month)>ماه {{ $month }}</option>
            @endforeach
        </select>
        <label for="{{ $idPrefix }}_month">ماه پایان دوره</label>
    </div>
</div>

<div class="col-12 col-lg-4"><label class="form-label">نوع دوره</label>
<select name="period_type" class="form-select" required>
@foreach(['annual' => 'سالانه', 'quarterly' => 'فصلی', 'legacy' => 'قدیمی / طبقه‌بندی‌نشده'] as $value => $label)
<option value="{{ $value }}" @selected(old('period_type', data_get($statement, 'period_type', 'annual')) === $value)>{{ $label }}</option>
@endforeach
</select></div>
@foreach(['period_start' => 'شروع دوره شمسی', 'period_end' => 'پایان دوره شمسی'] as $field => $label)
<div class="col-12 col-lg-4">
    <label class="form-label" for="{{ $idPrefix }}_{{ $field }}">{{ $label }}</label>
    <input type="text" dir="ltr" class="form-control" name="{{ $field }}" id="{{ $idPrefix }}_{{ $field }}"
           value="{{ old($field, data_get($statement, $field)) }}" placeholder="1404/01/01" maxlength="10">
</div>
@endforeach
<div class="col-12 col-lg-4">
    <label class="form-label" for="{{ $idPrefix }}_income_basis">مبنای صورت سود و زیان</label>
    <select name="income_basis" id="{{ $idPrefix }}_income_basis" class="form-select" required>
        @foreach(['standalone' => 'مستقل — فقط بازه واردشده', 'ytd' => 'تجمعی — از ابتدای سال مالی', 'unconfirmed' => 'تعیین‌نشده — نیازمند بررسی'] as $value => $label)
            <option value="{{ $value }}" @selected(old('income_basis', data_get($statement, 'income_basis', 'standalone')) === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="col-12"><small class="text-muted">ثبت استاندارد نیازمند اتصال پرونده به شرکت ثبت‌شده است. برای اسناد تاریخی بدون اطلاعات کافی، گزینه تعیین‌نشده و تاریخ‌های خالی را نگه دارید. تعیین مبنا، تأیید حسابرسی سند محسوب نمی‌شود.</small></div>
@foreach($fieldGroups as $groupTitle => $fields)
    <div class="col-12">
        <section class="statement-form-section" aria-labelledby="{{ $idPrefix }}_group_{{ $loop->index }}">
            <div class="statement-form-section__title" id="{{ $idPrefix }}_group_{{ $loop->index }}">
                <i class="mdi {{ $loop->index === 0 ? 'mdi-chart-line' : ($loop->index === 1 ? 'mdi-domain' : 'mdi-scale-balance') }}"></i>
                <span>{{ $groupTitle }}</span>
            </div>
            <div class="row g-3">
                @foreach($fields as $field => $label)
                    @php
                        $rawValue = old($field, data_get($statement, $field));
                        $normalizedValue = str_replace([',', '٬', '،', ' '], '', (string) $rawValue);
                        $displayValue = '';
                        if ($rawValue !== null && $rawValue !== '' && preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $normalizedValue, $parts)) {
                            $displayValue = $parts[1]
                                .preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $parts[2])
                                .(isset($parts[3]) ? '.'.$parts[3] : '');
                        }
                    @endphp
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="form-floating form-floating-outline">
                            <input type="text" inputmode="decimal" class="form-control number-input text-start"
                                   dir="ltr" name="{{ $field }}" id="{{ $idPrefix }}_{{ $field }}"
                                   value="{{ $displayValue }}" placeholder="0" autocomplete="off">
                            <label for="{{ $idPrefix }}_{{ $field }}">{{ $label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endforeach
