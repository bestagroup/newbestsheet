@if($currentInvestStep)
    <div class="card border-0 bg-light mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="mb-1 fw-bold">مدارک مرحله {{ $currentInvestStep->id }} — {{ $currentInvestStep->title }}</h6>
                    <small class="text-muted">مدارک مرتبط با این مرحله؛ موارد «الزامی» قبل از تأیید مرحله در Backend کنترل می‌شوند.</small>
                </div>
                <span class="badge bg-label-primary">{{ $currentStepRequirements->count() }} نوع سند</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-3">
                    <thead class="table-light"><tr><th>سند</th><th>نوع الزام</th><th>حداقل</th><th>بارگذاری‌شده</th><th>وضعیت</th><th>اقدام</th></tr></thead>
                    <tbody>
                    @forelse($currentStepRequirements as $requirement)
                        @php
                            $uploadedCount = (int) ($requirementFileCounts[$requirement->subject_file_id] ?? 0);
                            $minimumFiles = max(1, (int) $requirement->minimum_files);
                            $isSatisfied = !$requirement->is_required || $uploadedCount >= $minimumFiles;
                        @endphp
                        <tr>
                            <td>{{ $requirement->subject?->title ?? 'سند بدون عنوان' }}</td>
                            <td><span class="badge {{ $requirement->is_required ? 'bg-danger' : 'bg-secondary' }}">{{ $requirement->is_required ? 'الزامی' : 'مرتبط' }}</span></td>
                            <td>{{ $minimumFiles }}</td>
                            <td>{{ $uploadedCount }}</td>
                            <td><span class="badge {{ $isSatisfied ? 'bg-success' : 'bg-warning text-dark' }}">{{ $isSatisfied ? 'آماده' : 'ناقص' }}</span></td>
                            <td class="text-nowrap">
                                @if(auth()->user()->can('can-access', ['filemanager', 'insert']))
                                    <button type="button" class="btn btn-sm btn-outline-primary upload-btn" data-id="{{ $project->id }}"
                                            data-subject="{{ $requirement->subject_file_id }}" data-title="{{ $requirement->subject?->title }}">بارگذاری</button>
                                @endif
                                @if($canManageAssignments)
                                    <form action="{{ route('investsteps.documents.update', [$currentInvestStep->id, $requirement->id]) }}" method="POST" class="document-requirement-update-form d-inline-flex gap-1" data-project-id="{{ $project->id }}">
                                        @csrf @method('PATCH')
                                        <select name="is_required" class="form-select form-select-sm" style="width:95px">
                                            <option value="0" @selected(!$requirement->is_required)>مرتبط</option>
                                            <option value="1" @selected($requirement->is_required)>الزامی</option>
                                        </select>
                                        <input type="number" min="1" max="20" name="minimum_files" class="form-control form-control-sm" value="{{ $minimumFiles }}" style="width:70px">
                                        <button type="submit" class="btn btn-sm btn-outline-success">ذخیره</button>
                                    </form>
                                    <form action="{{ route('investsteps.documents.destroy', [$currentInvestStep->id, $requirement->id]) }}" method="POST" class="document-requirement-delete-form d-inline" data-project-id="{{ $project->id }}">
                                        @csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">برای این مرحله سندی پیکربندی نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($canManageAssignments && $availableSubjectFiles->isNotEmpty())
                <form action="{{ route('investsteps.documents.store', $currentInvestStep->id) }}" method="POST" class="document-requirement-create-form row g-2 align-items-end" data-project-id="{{ $project->id }}">
                    @csrf
                    <div class="col-md-6"><label class="form-label">افزودن نوع سند به مرحله</label><select name="subject_file_id" class="form-select" required><option value="">انتخاب سند</option>@foreach($availableSubjectFiles as $subject)<option value="{{ $subject->id }}">{{ $subject->title }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">نوع</label><select name="is_required" class="form-select"><option value="0">مرتبط</option><option value="1">الزامی</option></select></div>
                    <div class="col-md-2"><label class="form-label">حداقل فایل</label><input type="number" min="1" max="20" name="minimum_files" class="form-control" value="1"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">افزودن</button></div>
                </form>
            @endif
        </div>
    </div>
@endif
