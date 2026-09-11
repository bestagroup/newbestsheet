<form data-type="update" data-id="{{ $project->id }}"  class="row g-4 mb-4" method="POST" action="{{ route('project.update', $project->id) }}">

    @csrf
    @method('PATCH')
    <input type="hidden" name="menu_id" id="menu_id_{{$project->id}}" value="{{$project->id}}" />

    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="company_name_{{$project->id}}" name="company_name"
                   placeholder="نام شرکت" value="{{ $project->company_name }}">
            <label for="company_name">نام شرکت</label>
            <div class="invalid-feedback" id="company_nameFeedback">نام شرکت اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="title_{{$project->id}}" name="title"
                   placeholder="نام طرح" value="{{ $project->title }}">
            <label for="title">نام طرح</label>
            <div class="invalid-feedback" id="titleFeedback">نام طرح اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required inputmode="numeric" pattern="^\d{3,20}$" maxlength="20" minlength="3" type="text" class="form-control" id="registration_number_{{$project->id}}" name="registration_number"
                   placeholder="شماره ثبت" value="{{ $project->registration_number }}">
            <label for="registration_number">شماره ثبت</label>
            <div class="invalid-feedback" id="registration_numberFeedback">شماره ثبت اجباری و شامل عدد می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required inputmode="numeric" pattern="^\d{3,20}$" maxlength="20" minlength="3" type="text" class="form-control" id="national_id_{{$project->id}}" name="national_id"
                   placeholder="شناسه ملی شرکت" value="{{ $project->national_id }}">
            <label for="national_id">شناسه ملی شرکت</label>
            <div class="invalid-feedback" id="national_idFeedback">شناسه ملی شرکت اجباری و شامل عدد می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required inputmode="numeric" pattern="^\d{3,20}$" maxlength="20" minlength="3" type="text" class="form-control" id="economic_code_{{$project->id}}" name="economic_code"
                   placeholder="کد اقتصادی شرکت" value="{{ $project->economic_code }}">
            <label for="economic_code">کد اقتصادی شرکت</label>
            <div class="invalid-feedback" id="economic_codeFeedback">کد اقتصادی اجباری، و شامل عدد می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <select name="legal_type" id="legal_type_{{$project->id}}" class="form-control">
                <option value="" selected>انتخاب کنید</option>
                <option value="مسئولیت محدود"   {{$project->legal_type == 'مسئولیت محدود' ? 'selected' : ''}}>مسئولیت محدود</option>
                <option value="سهامی خاص"       {{$project->legal_type == 'سهامی خاص' ? 'selected' : ''}}>سهامی خاص</option>
                <option value="سهامی عام"       {{$project->legal_type == 'سهامی عام' ? 'selected' : ''}}>سهامی عام</option>
                <option value="تعاونی"          {{$project->legal_type == 'تعاونی' ? 'selected' : ''}}>تعاونی</option>
                <option value="موسسه غیر تجاری" {{$project->legal_type == 'موسسه غیر تجاری' ? 'selected' : ''}}>موسسه غیر تجاری</option>
            </select>
            <label for="legal_type">نوع شرکت</label>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input inputmode="numeric" pattern="^\d{3,20}$" type="text" class="form-control" id="tel_{{$project->id}}" name="tel" placeholder="تلفن شرکت" value="{{ $project->tel }}">
            <label for="tel">تلفن شرکت</label>
            <div class="invalid-feedback" id="telFeedback">شماره تلفن شامل عدد می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input type="email" class="form-control" id="email_{{$project->id}}" name="email" placeholder="ایمیل شرکت" value="{{ $project->email }}">
            <label for="email">ایمیل شرکت</label>
            <div class="invalid-feedback" id="emailFeedback">آدرس ایمیل را با دقت وارد کنید.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input type="text" class="form-control" id="website_{{$project->id}}" name="website" placeholder="وبسایت" value="{{ $project->website }}">
            <label for="website">وبسایت</label>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="postal_code_{{$project->id}}" name="postal_code" placeholder="کد پستی" value="{{ $project->postal_code }}">
            <label for="postal_code">کد پستی</label>
            <div class="invalid-feedback" id="postal_codeFeedback">کد پستی باید به شکل عدد 10 رقمی وارد شود</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <select name="state" id="state" class="form-control select2">
                <option value="" selected>انتخاب کنید</option>
                @foreach($states as $state)
                    <option value="{{$state->id}}" {{$project->province == $state->id ? 'selected' : ''}}>
                        {{$state->title}}
                    </option>
                @endforeach
            </select>
            <label for="state">استان</label>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <select name="city" id="city" class="form-control select2">
                <option value="" selected>انتخاب کنید</option>
                @foreach($cities as $city)
                    <option value="{{$city->id}}" {{$project->city == $city->id ? 'selected' : ''}}>
                        {{$city->title}}
                    </option>
                @endforeach
            </select>
            <label for="city">شهرستان</label>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required type="text" class="form-control" id="CEO_{{$project->id}}" name="CEO" placeholder="مدیرعامل" value="{{ $project->CEO }}">
            <label for="CEO">مدیرعامل</label>
            <div class="invalid-feedback" id="CEOFeedback">نام مدیرعامل اجباری می باشد.</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <input required inputmode="numeric" pattern="^\d{3,20}$" maxlength="10" minlength="10" type="text" class="form-control" id="ceo_national_code_{{$project->id}}" name="ceo_national_code"
                   placeholder="کد ملی مدیرعامل" value="{{ $project->ceo_national_code }}">
            <label for="ceo_national_code">کد ملی مدیرعامل</label>
            <div class="invalid-feedback" id="ceo_national_codeFeedback">کد ملی مدیرعامل اجباری می باشد و با دقت وارد شود</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="form-floating form-floating-outline">
            <textarea rows="2" class="form-control" id="percentageshare_{{$project->id}}" name="percentageshare" placeholder="درصد سهام">{{ $project->percentageshare }}</textarea>
            <label for="percentageshare">درصد سهام</label>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="form-floating form-floating-outline">
            <textarea rows="2" class="form-control" id="address_{{$project->id}}" name="address" placeholder="آدرس">{{ $project->address }}</textarea>
            <label for="address">آدرس شرکت</label>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="input-group mb-3">
            <input type="text" name="logo" class="form-control" value="{{ $project->logo }}" id="logo_{{ $project->id }}" readonly placeholder="انتخاب فایل برای پروژه {{ $project->id }}">
            <button class="btn btn-outline-secondary file-selector" type="button" data-record-id="{{ $project->id }}" data-input-id="logo_{{ $project->id }}">
                انتخاب فایل
            </button>
        </div>
    </div>
    <div class="col-12 col-md-12">
        <div class="form-floating form-floating-outline">
            <textarea name="description" class="form-control" id="description_{{$project->id}}" style="height: 150px" placeholder="معرفی طرح" >{{ $project->description }}</textarea>
            <label for="description">معرفی طرح</label>
        </div>
    </div>
    <div class="text-end">
        <button type="submit" id="editsubmit_{{$project->id}}" class="btn btn-primary" >ذخیره اطلاعات</button>
    </div>
</form>
