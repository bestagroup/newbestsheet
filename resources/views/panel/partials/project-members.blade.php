<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-1 fw-bold">اعضای تیم و پرسنل طرح</h6>
            <small class="text-muted">اطلاعات پرسنلی ثبت‌شده برای همین پروژه</small>
        </div>
        <span class="badge bg-label-primary">{{ $members->count() }} نفر</span>
    </div>
    <div class="card-body">
        @if($canManageInvestmentRecords)
            <form action="{{ route('flow.members.store', $project->id) }}" method="POST"
                  class="project-member-form row g-3 align-items-end mb-4" data-project-id="{{ $project->id }}">
                @csrf
                <div class="col-md-3">
                    <label class="form-label">نام و نام خانوادگی</label>
                    <input type="text" name="full_name" class="form-control" required maxlength="255">
                </div>
                <div class="col-md-2">
                    <label class="form-label">کد ملی</label>
                    <input type="text" name="national_code" class="form-control" required maxlength="20">
                </div>
                <div class="col-md-2">
                    <label class="form-label">سمت</label>
                    <input type="text" name="position" class="form-control" required maxlength="255">
                </div>
                <div class="col-md-2">
                    <label class="form-label">شروع</label>
                    <input type="text" name="start_date" class="form-control" maxlength="50" data-jdp autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="form-label">پایان</label>
                    <input type="text" name="end_date" class="form-control" maxlength="50" data-jdp autocomplete="off">
                </div>
                <div class="col-md-1">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <label class="form-check-label">فعال</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">ثبت</button>
                </div>
            </form>

            <div class="member-edit-panel border rounded p-3 mb-4 d-none" data-project-id="{{ $project->id }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <strong>ویرایش عضو</strong>
                    <button type="button" class="btn btn-sm btn-label-secondary member-edit-cancel">انصراف</button>
                </div>
                <form method="POST" class="project-member-update-form row g-3 align-items-end" data-project-id="{{ $project->id }}">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-3"><label class="form-label">نام و نام خانوادگی</label><input type="text" name="full_name" class="form-control member-full-name" required></div>
                    <div class="col-md-2"><label class="form-label">کد ملی</label><input type="text" name="national_code" class="form-control member-national-code" required></div>
                    <div class="col-md-2"><label class="form-label">سمت</label><input type="text" name="position" class="form-control member-position" required></div>
                    <div class="col-md-2"><label class="form-label">شروع</label><input type="text" name="start_date" class="form-control member-start-date" data-jdp autocomplete="off"></div>
                    <div class="col-md-2"><label class="form-label">پایان</label><input type="text" name="end_date" class="form-control member-end-date" data-jdp autocomplete="off"></div>
                    <div class="col-md-1">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check mb-2"><input class="form-check-input member-is-active" type="checkbox" name="is_active" value="1"><label class="form-check-label">فعال</label></div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">ذخیره</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>نام</th><th>کد ملی</th><th>سمت</th><th>شروع</th><th>پایان</th><th>وضعیت</th>
                    @if($canManageInvestmentRecords)<th style="width:120px">عملیات</th>@endif
                </tr>
                </thead>
                <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>{{ $member->full_name }}</td>
                        <td>{{ $member->national_code }}</td>
                        <td>{{ $member->position }}</td>
                        <td>{{ $member->start_date ?: '—' }}</td>
                        <td>{{ $member->end_date ?: '—' }}</td>
                        <td><span class="badge {{ $member->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $member->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
                        @if($canManageInvestmentRecords)
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary member-edit-btn"
                                        data-url="{{ route('flow.members.update', [$project->id, $member->id]) }}"
                                        data-full-name="{{ $member->full_name }}" data-national-code="{{ $member->national_code }}"
                                        data-position="{{ $member->position }}"
                                        data-start-date="{{ $member->start_date }}" data-end-date="{{ $member->end_date }}"
                                        data-is-active="{{ $member->is_active ? 1 : 0 }}">ویرایش</button>
                                <form action="{{ route('flow.members.destroy', [$project->id, $member->id]) }}" method="POST" class="project-member-delete-form d-inline" data-project-id="{{ $project->id }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canManageInvestmentRecords ? 7 : 6 }}" class="text-center text-muted py-4">عضوی برای این پروژه ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
