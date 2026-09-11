@php
    $currentStepIndex = $investsteps->search(fn ($candidate) => (int) $candidate->id === (int) $project->invest_step);
    $currentStepIndex = $currentStepIndex === false ? 0 : (int) $currentStepIndex;
    $totalInvestmentWeight = max((float) $investsteps->sum(fn ($step) => max(0, (float) $step->weight)), 0.001);
    $visualProgress = (int) $project->progress_percentage;
@endphp

<div class="tab-pane fade" id="navs-investment-card" role="tabpanel">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <h5 class="mb-1 fw-bold">مسیر مرحله‌ای سرمایه‌گذاری</h5>
                    <p class="text-muted mb-0">پرونده شما مرحله‌به‌مرحله بررسی می‌شود. مرحله جاری با تأکید بیشتر مشخص شده است.</p>
                </div>
                <div class="text-end">
                    <div class="small text-muted">پیشرفت فرایند</div>
                    <div class="fs-4 fw-bold text-primary">{{ min(100, max(0, $visualProgress)) }}٪</div>
                </div>
            </div>
            <div class="progress" style="height: 9px; border-radius: 20px;">
                <div class="progress-bar" role="progressbar" style="width: {{ min(100, max(0, $visualProgress)) }}%" aria-valuenow="{{ $visualProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    <div class="investment-stepper">
        @forelse($investsteps as $step)
            @php
                $stepIndex = $loop->index;
                $history = $projectStepsByStep->get($step->id);
                $stageInstance = $projectStageInstances->get($step->id);
                $isRejected = $history?->status === 'rejected' || ((bool) $project->is_rejected && (int) $project->reject_step === (int) $step->id);
                $isCurrent = ! $project->is_rejected && (int) $project->invest_step === (int) $step->id;
                $isCompleted = $history?->status === 'approved' || ($stepIndex < $currentStepIndex && ! $isRejected);
                $isFuture = ! $isCompleted && ! $isCurrent && ! $isRejected;
                $stateClass = $isRejected ? 'is-rejected' : ($isCurrent ? 'is-current' : ($isCompleted ? 'is-completed' : 'is-future'));
                $stateLabel = $isRejected ? 'رد شده' : ($isCurrent ? 'مرحله جاری' : ($isCompleted ? 'تکمیل شده' : 'مرحله آتی'));
                $badgeClass = $isRejected ? 'danger' : ($isCurrent ? 'primary' : ($isCompleted ? 'success' : 'secondary'));
                $stageStatus = $stageInstance?->status?->value;
                $canUploadAtStep = ! $project->is_rejected
                    && $stageInstance !== null
                    && $stageStatus !== 'rejected';
            @endphp

            <div class="investment-step {{ $stateClass }}">
                <div class="investment-step__marker">
                    @if($isCompleted)
                        <i class="mdi mdi-check mdi-24px"></i>
                    @elseif($isRejected)
                        <i class="mdi mdi-close mdi-24px"></i>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </div>
                <div class="investment-step__card">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                        <div class="min-w-0">
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <h6 class="mb-0 fw-bold">مرحله {{ $loop->iteration }} — {{ $step->title }}</h6>
                                @if($isCurrent)<span class="badge bg-primary"><i class="mdi mdi-map-marker-radius-outline me-1"></i>شما اینجا هستید</span>@endif
                            </div>
                            @if($step->description)<div class="text-muted small" style="line-height: 1.8;">{{ $step->description }}</div>@endif
                            <div class="small text-muted mt-1">
                                سهم این مرحله از پیشرفت: <strong>{{ number_format((((float) $step->weight) / $totalInvestmentWeight) * 100, 2) }}٪</strong>
                            </div>
                        </div>
                        <span class="badge bg-label-{{ $badgeClass }}">{{ $stateLabel }}</span>
                    </div>

                    @if($history)
                        <div class="d-flex flex-wrap gap-3 mt-3 small text-muted">
                            <span><i class="mdi mdi-calendar-check-outline me-1"></i>{{ $history->created_at ? jdate($history->created_at)->format('Y/m/d H:i') : '—' }}</span>
                            @if($history->description)<span><i class="mdi mdi-comment-text-outline me-1"></i>{{ \Illuminate\Support\Str::limit($history->description, 180) }}</span>@endif
                        </div>
                    @endif

                    @if($stageInstance?->comments?->isNotEmpty())
                        <div class="mt-3 pt-3 border-top">
                            <div class="small fw-semibold mb-2"><i class="mdi mdi-comment-text-multiple-outline me-1"></i>توضیحات کارشناسان</div>
                            @foreach($stageInstance->comments as $comment)
                                <div class="alert alert-light border py-2 mb-2 small">
                                    <strong>{{ $comment->author?->name ?? 'کارشناس' }}:</strong>
                                    {{ $comment->body }}
                                    <span class="text-muted ms-1">{{ jdate($comment->created_at)->format('Y/m/d H:i') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($step->documentRequirements->isNotEmpty())
                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="small fw-semibold"><i class="mdi mdi-folder-check-outline me-1"></i>مدارک این مرحله</div>
                                @if($isFuture)
                                    <span class="badge bg-label-info"><i class="mdi mdi-upload-outline me-1"></i>بارگذاری پیشاپیش فعال است</span>
                                @elseif($isCompleted || $isRejected)
                                    <span class="badge bg-label-secondary">امکان افزودن مدرک تکمیلی</span>
                                @endif
                            </div>
                            <div class="row g-2">
                                @foreach($step->documentRequirements as $requirement)
                                    @php
                                        $requirementFiles = $files->filter(static function ($file) use ($requirement, $stageInstance): bool {
                                            if ($file->document_requirement_id !== null) {
                                                return (int) $file->document_requirement_id === (int) $requirement->id;
                                            }

                                            return (int) $file->subject_id === (int) $requirement->subject_file_id
                                                && ($file->project_stage_instance_id === null
                                                    || (int) $file->project_stage_instance_id === (int) $stageInstance?->id);
                                        })->values();
                                        $validFiles = $requirementFiles->where('status', '!=', 5)->values();
                                        $count = $validFiles->count();
                                        $minimumFiles = max(1, (int) $requirement->minimum_files);
                                        $missingRequired = $requirement->is_required && $count < $minimumFiles;
                                        $remainingFiles = max(0, $minimumFiles - $count);
                                    @endphp
                                    <div class="col-lg-6">
                                        <div class="step-document-chip h-100"
                                             data-requirement-id="{{ $requirement->id }}"
                                             data-uploaded-count="{{ $count }}"
                                             data-can-upload="{{ $canUploadAtStep ? '1' : '0' }}">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div class="min-w-0">
                                                    <div class="fw-semibold small text-break">{{ $requirement->subject?->title ?? 'سند' }}</div>
                                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                        <span class="badge {{ $requirement->is_required ? 'bg-label-danger' : 'bg-label-secondary' }}">
                                                            {{ $requirement->is_required ? 'الزامی' : 'اختیاری' }}
                                                        </span>
                                                        @if($minimumFiles > 1)
                                                            <span class="badge bg-label-info">حداقل {{ $minimumFiles }} فایل</span>
                                                        @endif
                                                        @if($missingRequired)
                                                            <span class="badge bg-label-warning">{{ $remainingFiles }} فایل باقی‌مانده</span>
                                                        @elseif($count > 0)
                                                            <span class="badge bg-label-success">تکمیل</span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted small mt-1">{{ $count }} فایل معتبر ثبت شده است.</div>
                                                </div>
                                                @if($canUploadAtStep)
                                                    <button type="button" class="btn btn-sm btn-outline-primary investee-upload-trigger flex-shrink-0"
                                                            data-subject="{{ $requirement->subject_file_id }}"
                                                            data-requirement="{{ $requirement->id }}"
                                                            data-title="{{ $requirement->subject?->title }}"
                                                            data-minimum="{{ $minimumFiles }}"
                                                            data-uploaded="{{ $count }}">
                                                        <i class="mdi mdi-upload-outline me-1"></i>بارگذاری
                                                    </button>
                                                @endif
                                            </div>

                                            @if($requirementFiles->isNotEmpty())
                                                <div class="step-document-files mt-2 pt-2 border-top">
                                                    @foreach($requirementFiles as $file)
                                                        @php
                                                            $fileState = (int) $file->status === 4
                                                                ? ['تأیید شده', 'success', 'mdi-check-circle-outline']
                                                                : ((int) $file->status === 5
                                                                    ? ['رد شده', 'danger', 'mdi-close-circle-outline']
                                                                    : ['در انتظار بررسی', 'warning', 'mdi-clock-outline']);
                                                        @endphp
                                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-1">
                                                            <div class="small min-w-0 text-break">
                                                                <i class="mdi mdi-file-document-outline text-primary me-1"></i>{{ $file->original_name ?: $file->name }}
                                                                <span class="badge bg-label-{{ $fileState[1] }} ms-1"><i class="mdi {{ $fileState[2] }} me-1"></i>{{ $fileState[0] }}</span>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                                                <a class="btn btn-xs btn-outline-primary" href="{{ $file->url }}" target="_blank" rel="noopener" title="مشاهده فایل"><i class="mdi mdi-eye-outline"></i></a>
                                                                @if($canUploadAtStep && (int) $file->user_id === (int) auth()->id() && (int) $file->status !== 4)
                                                                    <button type="button" class="btn btn-xs btn-outline-danger investee-document-delete" data-id="{{ $file->id }}" title="حذف فایل"><i class="mdi mdi-delete-outline"></i></button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="small text-muted mt-3 pt-3 border-top">
                            <i class="mdi mdi-file-check-outline me-1"></i>برای این مرحله مدرکی جهت بارگذاری تعریف نشده است.
                        </div>
                    @endif

                    @if($isCurrent)
                        <div class="alert alert-primary border-0 mt-3 mb-0 py-2">
                            <i class="mdi mdi-information-outline me-1"></i>
                            بررسی پرونده شما اکنون در این مرحله قرار دارد. در صورت نیاز به سند یا اقدام جدید، از طریق اعلان‌های سامانه مطلع خواهید شد.
                        </div>
                    @elseif($isFuture)
                        <div class="small text-muted mt-2"><i class="mdi mdi-lock-outline me-1"></i>بررسی این مرحله پس از تکمیل مراحل قبلی فعال می‌شود؛ مدارک آن را می‌توانید از هم‌اکنون بارگذاری کنید.</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="alert alert-info mb-0">مرحله فعالی برای فرایند سرمایه‌گذاری تعریف نشده است.</div>
        @endforelse
    </div>
</div>
