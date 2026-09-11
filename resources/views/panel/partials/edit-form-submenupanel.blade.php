<form data-type="update" data-id="{{ $submenupanel->id }}"  class="row g-4 mb-4" method="POST" action="{{ route('submenupanel.update', $submenupanel->id) }}">
        {{csrf_field()}}
        <input type="hidden" name="menu_id" id="menu_id_{{$submenupanel->id}}" value="{{$submenupanel->id}}" />

    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="label_{{$submenupanel->id}}" name="label"
                   placeholder="نام  زیرمنو داشبورد فارسی" value="{{ $submenupanel->label }}">
            <label for="label">نام  زیرمنو داشبورد فارسی</label>
            <div class="invalid-feedback" id="labelFeedback">نام  زیرمنو داشبورد فارسی اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="title_{{$submenupanel->id}}" name="title"
                   placeholder="نام  زیرمنو داشبورد" value="{{ $submenupanel->title }}">
            <label for="title">نام  زیرمنو داشبورد</label>
            <div class="invalid-feedback" id="titleFeedback">نام  زیرمنو داشبورد اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <select name="menupanel_id" id="menupanel_id_{{$submenupanel->id}}" class="form-control select-lg select2">
                @foreach($menupanels as $menupanel)
                    <option value="{{$menupanel->id}}" {{$submenupanel->menu_id == $menupanel->id ? 'selected' : '' }}>{{$menupanel->label}}</option>
                @endforeach
            </select>
            <label for="submenu">انتخاب زیر منو</label>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="class_{{$submenupanel->id}}" name="class"
                   placeholder="کلاس داشبورد" value="{{ $submenupanel->class }}">
            <label for="class">کلاس داشبورد</label>
            <div class="invalid-feedback" id="classFeedback">کلاس داشبورد اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="controller_{{$submenupanel->id}}" name="controller"
                   placeholder="کنترلر داشبورد" value="{{ $submenupanel->controller }}">
            <label for="controller">کنترلر داشبورد</label>
            <div class="invalid-feedback" id="controllerFeedback">کنترلر داشبورد اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <select name="status" id="status_{{$submenupanel->id}}" class="form-control">
                <option value="4" {{$submenupanel->status == 4 ? 'selected' : '' }}>نمایش</option>
                <option value="0" {{$submenupanel->status == 0 ? 'selected' : '' }}>عدم نمایش</option>
            </select>
            <label for="status">نمایش/عدم نمایش</label>
        </div>
    </div>
        <div class="text-center">
            <button type="submit" id="editsubmit_{{$submenupanel->id}}" class="btn btn-primary" >ذخیره اطلاعات</button>
        </div>
    </form>
