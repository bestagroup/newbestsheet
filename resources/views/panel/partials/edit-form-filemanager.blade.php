<form data-type="update" data-id="{{ $mediafile->id }}" class="row g-4 mb-4" method="POST" action="{{ route('filemanager.update', $mediafile->id) }}">
    @csrf
    @method('PATCH')

    <div class="col-12 col-md-6">
        <div class="form-floating form-floating-outline">
            <select name="project_id" id="project_id_{{ $mediafile->id }}" class="form-control" required>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}" {{ (int) $project->id === (int) $mediafile->project_id ? 'selected' : '' }}>{{ $project->title }}</option>
                @endforeach
            </select>
            <label for="project_id_{{ $mediafile->id }}">انتخاب طرح / پروژه</label>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-floating form-floating-outline">
            <select name="subject_id" id="subject_id_{{ $mediafile->id }}" class="form-control">
                <option value="">بدون دسته‌بندی</option>
                @foreach($subject_files as $subject_file)
                    <option value="{{ $subject_file->id }}" {{ (int) $mediafile->subject_id === (int) $subject_file->id ? 'selected' : '' }}>{{ $subject_file->title }}</option>
                @endforeach
            </select>
            <label for="subject_id_{{ $mediafile->id }}">نوع سند</label>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" id="editsubmit_{{ $mediafile->id }}" class="btn btn-primary">ذخیره اطلاعات</button>
    </div>
</form>
