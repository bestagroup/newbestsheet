<form id="editform" data-type="update" method="POST" class="row g-4 mb-2"
      action="{{ url('panel/ticketing/'.$ticket->id) }}">
    {{ csrf_field() }}
    {{ method_field('PUT') }}

    <div class="col-12 col-md-6">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="subject_edit" name="subject"
                   value="{{ $ticket->subject }}" placeholder="موضوع تیکت">
            <label for="subject_edit">موضوع تیکت</label>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" id="investor_name_edit" name="investor_name"
                   value="{{ $ticket->investor_name }}" placeholder="نام سرمایه‌گذار">
            <label for="investor_name_edit">نام سرمایه‌گذار</label>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" id="investee_name_edit" name="investee_name"
                   value="{{ $ticket->investee_name }}" placeholder="نام سرمایه‌پذیر">
            <label for="investee_name_edit">نام سرمایه‌پذیر</label>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="form-floating form-floating-outline">
            <select required name="priority" id="priority_edit" class="form-control">
                <option value="1" {{ (int)$ticket->priority === 1 ? 'selected' : '' }}>کم</option>
                <option value="2" {{ (int)$ticket->priority === 2 ? 'selected' : '' }}>عادی</option>
                <option value="3" {{ (int)$ticket->priority === 3 ? 'selected' : '' }}>زیاد</option>
                <option value="4" {{ (int)$ticket->priority === 4 ? 'selected' : '' }}>فوری</option>
            </select>
            <label for="priority_edit">اولویت</label>
        </div>
    </div>

    <div class="col-12 col-md-3">
        <div class="form-floating form-floating-outline">
            <select required name="status" id="status_edit" class="form-control">
                <option value="1" {{ (int)$ticket->status === 1 ? 'selected' : '' }}>باز</option>
                <option value="2" {{ (int)$ticket->status === 2 ? 'selected' : '' }}>در انتظار</option>
                <option value="3" {{ (int)$ticket->status === 3 ? 'selected' : '' }}>حل شده</option>
                <option value="4" {{ (int)$ticket->status === 4 ? 'selected' : '' }}>بسته</option>
                <option value="0" {{ (int)$ticket->status === 0 ? 'selected' : '' }}>لغو</option>
            </select>
            <label for="status_edit">وضعیت</label>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
    </div>
</form>
