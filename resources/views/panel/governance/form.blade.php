@csrf
<input type="hidden" name="lock_version" value="{{ $meeting->lock_version ?? 0 }}">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">شرکت / طرح</label><select required name="project_id" class="form-select">@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('project_id', $meeting->project_id ?? '') == $project->id)>{{ $project->title }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">عنوان جلسه</label><input required name="title" maxlength="255" class="form-control" value="{{ old('title', $meeting->title ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">نوع جلسه</label><select name="type" class="form-select">@foreach(['ordinary'=>'مجمع عمومی عادی','extraordinary'=>'مجمع فوق‌العاده','board'=>'هیئت مدیره'] as $key=>$label)<option value="{{ $key }}" @selected(old('type', $meeting->type ?? '') === $key)>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">تاریخ شمسی (۱۴۰۵/۰۷/۱۶)</label><input required name="held_on" class="form-control" value="{{ old('held_on', $meeting->held_on ?? '') }}"></div>
<div class="col-md-4"><label class="form-label">محل</label><input name="location" class="form-control" value="{{ old('location', $meeting->location ?? '') }}"></div>
<div class="col-md-6"><label class="form-label">دستور جلسه؛ هر بند در یک خط</label><textarea required name="agenda" rows="5" class="form-control">{{ is_array(old('agenda')) ? implode("
", old('agenda')) : old('agenda', implode("
", $meeting->agenda ?? [])) }}</textarea></div>
<div class="col-md-6"><label class="form-label">حاضران و سمت / نمایندگی</label><textarea required name="attendees" rows="5" class="form-control">{{ old('attendees', $meeting->attendees ?? '') }}</textarea></div>
<div class="col-md-6"><label class="form-label">پیوست از آرشیو (اختیاری)</label><select name="media_file_id" class="form-select"><option value="">بدون پیوست</option>@foreach($attachments as $attachment)<option value="{{ $attachment->id }}" @selected(old('media_file_id', $meeting->media_file_id ?? '') == $attachment->id)>{{ $attachment->original_name ?: $attachment->name }} — {{ $attachment->project?->title ?? 'آرشیو مستقل' }}</option>@endforeach</select><small>پیوست باید متعلق به پرونده انتخاب‌شده باشد. فایل جدید را ابتدا در آرشیو بارگذاری کنید.</small></div>
<div class="col-12"><button class="btn btn-primary" type="submit">ذخیره جلسه</button></div>
</div>
