@if($canManageAssignments)
    @php
        $totalStepWeight = max((float) $investsteps->sum(fn ($step) => max(0, (float) $step->weight)), 0.001);
    @endphp
    <div class="card border-0 shadow-sm mb-4" id="investment-step-weights-card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
            <div>
                <h6 class="mb-1 fw-bold"><i class="mdi mdi-scale-balance me-1"></i>وزن مراحل و محاسبه پیشرفت</h6>
                <div class="text-muted small">وزن‌ها نسبی هستند؛ لازم نیست مجموع آن‌ها ۱۰۰ باشد. سامانه سهم هر مرحله را از مجموع وزن مراحل فعال محاسبه می‌کند.</div>
            </div>
            <span class="badge bg-label-primary">پیشرفت این پروژه: {{ (int) $project->progress_percentage }}٪</span>
        </div>
        <div class="card-body">
            <form action="{{ route('investsteps.weights.update') }}" method="POST" id="investment-step-weight-form">
                @csrf
                @method('PATCH')
                <div class="table-responsive" style="max-height: 430px;">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th style="width:70px">گام</th>
                            <th>مرحله</th>
                            <th style="width:160px">وزن نسبی</th>
                            <th style="width:160px">سهم از ۱۰۰٪</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($investsteps as $step)
                            <tr>
                                <td><span class="badge bg-label-secondary">{{ $loop->iteration }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $step->title }}</div>
                                    <div class="text-muted small">{{ \Illuminate\Support\Str::limit($step->description, 90) }}</div>
                                </td>
                                <td>
                                    <input type="number"
                                           name="weights[{{ $step->id }}]"
                                           value="{{ rtrim(rtrim(number_format((float) $step->weight, 3, '.', ''), '0'), '.') }}"
                                           min="0.001" step="0.001"
                                           class="form-control form-control-sm workflow-weight-input"
                                           data-step-id="{{ $step->id }}"
                                           required>
                                </td>
                                <td>
                                    <strong class="workflow-weight-share" data-step-id="{{ $step->id }}">{{ number_format((((float) $step->weight) / $totalStepWeight) * 100, 2) }}٪</strong>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                    <div class="small text-muted">
                        مجموع وزن فعلی: <strong id="workflow-total-weight">{{ rtrim(rtrim(number_format($totalStepWeight, 3, '.', ''), '0'), '.') }}</strong>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-content-save-outline me-1"></i>ذخیره وزن‌ها و بازمحاسبه همه پروژه‌ها
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('investment-step-weight-form');
            if (!form) return;

            const inputs = Array.from(form.querySelectorAll('.workflow-weight-input'));
            const totalEl = document.getElementById('workflow-total-weight');

            const refreshShares = () => {
                const values = inputs.map(input => Math.max(0, Number(input.value || 0)));
                const total = values.reduce((sum, value) => sum + value, 0);
                if (totalEl) totalEl.textContent = total.toLocaleString('fa-IR', {maximumFractionDigits: 3});

                inputs.forEach((input, index) => {
                    const share = total > 0 ? (values[index] / total) * 100 : 0;
                    const target = form.querySelector(`.workflow-weight-share[data-step-id="${input.dataset.stepId}"]`);
                    if (target) target.textContent = `${share.toLocaleString('fa-IR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}٪`;
                });
            };

            inputs.forEach(input => input.addEventListener('input', refreshShares));

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                const confirmed = window.AppAlert
                    ? await window.AppAlert.confirm('با تغییر وزن مراحل، درصد پیشرفت تمام پروژه‌های موجود بازمحاسبه می‌شود. ادامه می‌دهید؟', 'تغییر وزن فرایند')
                    : {isConfirmed: window.confirm('درصد پیشرفت تمام پروژه‌ها بازمحاسبه می‌شود. ادامه می‌دهید؟')};

                if (!confirmed.isConfirmed) return;

                const button = form.querySelector('button[type="submit"]');
                if (button) button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: new FormData(form)
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        const validation = payload.errors ? Object.values(payload.errors).flat().join('\n') : null;
                        throw new Error(validation || payload.message || 'ذخیره وزن مراحل انجام نشد.');
                    }

                    if (window.AppAlert) {
                        await window.AppAlert.success(payload.message || 'وزن مراحل ذخیره شد.');
                    }
                    window.location.reload();
                } catch (error) {
                    if (window.AppAlert) {
                        window.AppAlert.error(error.message || 'ذخیره وزن مراحل انجام نشد.');
                    } else {
                        alert(error.message || 'ذخیره وزن مراحل انجام نشد.');
                    }
                } finally {
                    if (button) button.disabled = false;
                }
            });
        });
    </script>
@endif
