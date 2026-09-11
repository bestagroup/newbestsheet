    <form data-type="update" data-id="{{ $finance->id }}"  class="row g-4 mb-4" method="POST" action="{{ route('finance.update', $finance->id) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="menu_id" id="menu_id_{{$finance->id}}" value="{{$finance->id}}" />
        <div class="col-6 col-md-3">
            <div class="form-floating form-floating-outline">
                <label for="project_id">نام شرکت</label>
                <select name="project_id" id="project_id" class="form-control select-lg select2">
                    <option value="" selected>انتخاب کنید</option>
                    @foreach($projects as $project)
                        <option value="{{$project->id}}" {{$project->id == $finance->project_id ? 'selected' : ''}}>{{$project->company_name}} - {{$project->title}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="form-floating form-floating-outline">
                <label for="serial">شماره مرحله پرداخت</label>
                <select name="serial" id="serial" class="form-control select-lg select2">
                    <option value="" selected>انتخاب کنید</option>
                    <option value="1" {{$finance->serial == 1 ? 'selected' : ''}}>1</option>
                    <option value="2" {{$finance->serial == 2 ? 'selected' : ''}}>2</option>
                    <option value="3" {{$finance->serial == 3 ? 'selected' : ''}}>3</option>
                    <option value="4" {{$finance->serial == 4 ? 'selected' : ''}}>4</option>
                    <option value="5" {{$finance->serial == 5 ? 'selected' : ''}}>5</option>
                </select>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="form-floating form-floating-outline">
                <input required type="text" class="form-control" id="docserial" name="docserial" value="{{$finance->docserial}}" >
                <label for="docserial">شماره سند بایگانی مالی</label>
                <div class="invalid-feedback" id="docserialFeedback">شماره سند بایگانی مالی اجباری می باشد.</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="form-floating form-floating-outline">
                <input required type="text" class="form-control" id="amount" name="amount" value="{{number_format($finance->amount)}}" >
                <label for="amount">مبلغ پرداختی</label>
                <div class="invalid-feedback" id="amountFeedback">مبلغ پرداختی اجباری می باشد.</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="form-floating form-floating-outline">
                <input required type="text" class="form-control" data-jdp id="date" name="date" value="{{$finance->date}}" >
                <label for="date">تاریخ واریز</label>
                <div class="invalid-feedback" id="dateFeedback">تاریخ واریز اجباری می باشد.</div>
            </div>
        </div>
        <div class="col-9 col-md-9">
            <div class="form-floating form-floating-outline">
                <textarea name="description" id="description" required cols="30" rows="10" class="form-control" >{{$finance->description}}</textarea>
                <label for="description">توضیحات</label>
                <div class="invalid-feedback" id="dateFeedback">توضیحات اجباری می باشد.</div>
            </div>
        </div>
        <div class="text-center">
            <button type="submit" id="editsubmit_{{$finance->id}}" class="btn btn-primary" >ذخیره اطلاعات</button>
        </div>
    </form>
