<form data-type="update" data-id="{{ $menupanel->id }}"  class="row g-4 mb-4" method="POST" action="{{ route('menupanel.update', $menupanel->id) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="menu_id" id="menu_id_{{$menupanel->id}}" value="{{$menupanel->id}}" />

    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="label_{{$menupanel->id}}" name="label"
                   placeholder="نام  منو داشبورد فارسی" value="{{ $menupanel->label }}">
            <label for="label">نام  منو داشبورد فارسی</label>
            <div class="invalid-feedback" id="labelFeedback">نام  منو داشبورد فارسی اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="title_{{$menupanel->id}}" name="title"
                   placeholder="نام  منو داشبورد" value="{{ $menupanel->title }}">
            <label for="title">نام  منو داشبورد</label>
            <div class="invalid-feedback" id="titleFeedback">نام  منو داشبورد اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <select name="submenu" id="submenu_{{$menupanel->id}}" class="form-control">
                <option value="1" {{$menupanel->submenu == 1 ? 'selected' : '' }} >دارد</option>
                <option value="0" {{$menupanel->submenu == 0 ? 'selected' : '' }}>ندارد</option>
            </select>
            <label for="submenu">زیر  منو داشبورد</label>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="class_{{$menupanel->id}}" name="class"
                   placeholder="کلاس داشبورد" value="{{ $menupanel->class }}">
            <label for="class">کلاس داشبورد</label>
            <div class="invalid-feedback" id="classFeedback">کلاس داشبورد اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="controller_{{$menupanel->id}}" name="controller"
                   placeholder="کنترلر داشبورد" value="{{ $menupanel->controller }}">
            <label for="controller">کنترلر داشبورد</label>
            <div class="invalid-feedback" id="controllerFeedback">کنترلر داشبورد اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <select name="status" id="status_{{$menupanel->id}}" class="form-control">
                <option value="1" {{$menupanel->status == 1 ? 'selected' : '' }} >نمایش</option>
                <option value="0" {{$menupanel->status == 0 ? 'selected' : '' }}>عدم نمایش</option>
            </select>
            <label for="status">نمایش/عدم نمایش</label>
        </div>
    </div>
    <div class="text-center">
        <button type="submit" id="editsubmit_{{$menupanel->id}}" class="btn btn-primary" >ذخیره اطلاعات</button>
    </div>
</form>
