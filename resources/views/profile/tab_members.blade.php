<div class="tab-pane fade" id="navs-members-card" role="tabpanel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div><h5 class="mb-1 fw-bold">اعضای تیم طرح</h5><small class="text-muted">افراد کلیدی و سمت آن‌ها در اجرای طرح</small></div>
        <button class="btn btn-primary" type="button" id="investeeMemberCreate"><i class="mdi mdi-account-plus-outline me-1"></i>افزودن عضو تیم</button>
    </div>
    <div class="card"><div class="card-body"><div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>نام</th><th>کد ملی</th><th>سمت</th><th>وضعیت</th><th class="text-end">عملیات</th></tr></thead><tbody>
        @forelse($members as $member)
            <tr><td><div class="d-flex align-items-center gap-2"><span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-label-primary" style="width:34px;height:34px"><i class="mdi mdi-account-outline"></i></span><strong>{{ $member->full_name }}</strong></div></td><td>{{ $member->national_code }}</td><td>{{ $member->position }}</td><td><span class="badge bg-label-{{ $member->is_active ? 'success' : 'secondary' }}">{{ $member->is_active ? 'فعال' : 'غیرفعال' }}</span></td><td class="text-end text-nowrap"><button class="btn btn-sm btn-outline-primary investee-member-edit" data-id="{{ $member->id }}"><i class="mdi mdi-pencil-outline"></i></button> <button class="btn btn-sm btn-outline-danger investee-member-delete" data-id="{{ $member->id }}"><i class="mdi mdi-delete-outline"></i></button></td></tr>
        @empty<tr><td colspan="5" class="text-center text-muted py-5"><i class="mdi mdi-account-group-outline mdi-36px d-block mb-2"></i>عضوی برای این طرح ثبت نشده است.</td></tr>@endforelse
        </tbody>
    </table></div></div></div>
</div>
<div class="modal fade" id="investeeMemberModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><div><h5 class="modal-title mb-1">اطلاعات عضو تیم</h5><small class="text-muted">مشخصات و سمت فرد در اجرای طرح</small></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
    <form id="investeeMemberForm" method="POST">@csrf<input type="hidden" name="_method" id="investeeMemberMethod" value="POST"><input type="hidden" id="investeeMemberId">
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label">نام و نام خانوادگی</label><input class="form-control" name="full_name" required></div>
            <div class="col-md-4"><label class="form-label">کد ملی</label><input class="form-control" name="national_code" required></div>
            <div class="col-md-4"><label class="form-label">سمت</label><input class="form-control" name="position" required></div>
            <div class="col-md-6"><label class="form-label">تاریخ شروع</label><input class="form-control" data-jdp name="start_date"></div>
            <div class="col-md-6"><label class="form-label">تاریخ پایان</label><input class="form-control" data-jdp name="end_date"></div>
            <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="investeeMemberActive" checked><label class="form-check-label" for="investeeMemberActive">عضو فعال است</label></div></div>
        </div></div><div class="modal-footer"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button><button class="btn btn-primary" type="submit"><i class="mdi mdi-content-save-outline me-1"></i>ذخیره</button></div>
    </form>
</div></div></div>
