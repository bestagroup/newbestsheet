<div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}title">عنوان زیرصفحه</label><input required type="text" name="title" id="{{ $prefix }}title" class="form-control"></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}menu_id">منوی والد</label><select required name="menu_id" id="{{ $prefix }}menu_id" class="form-control"><option value="">انتخاب کنید</option>@foreach($menus as $menu)<option value="{{ $menu->id }}">{{ $menu->title }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}status">وضعیت</label><select required name="status" id="{{ $prefix }}status" class="form-control"><option value="4">نمایش</option><option value="0">عدم نمایش</option></select></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}tab_title">عنوان تب</label><input type="text" name="tab_title" id="{{ $prefix }}tab_title" class="form-control"></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}page_title">عنوان صفحه</label><input type="text" name="page_title" id="{{ $prefix }}page_title" class="form-control"></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}class">کلاس</label><input type="text" name="class" id="{{ $prefix }}class" class="form-control"></div>
    <div class="col-md-4"><label class="form-label" for="{{ $prefix }}controller">کنترلر</label><input type="text" name="controller" id="{{ $prefix }}controller" class="form-control"></div>
    <div class="col-12"><label class="form-label" for="{{ $prefix }}keyword">کلمات کلیدی</label><textarea name="keyword" id="{{ $prefix }}keyword" class="form-control" rows="2"></textarea></div>
    <div class="col-12"><label class="form-label" for="{{ $prefix }}description">توضیحات</label><textarea name="description" id="{{ $prefix }}description" class="form-control" rows="3"></textarea></div>
</div>
