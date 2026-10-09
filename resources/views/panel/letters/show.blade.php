@extends('layouts.base')
@section('title','پرونده مکاتبه')
@section('content')
@include('partials.alerts')
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<a href="{{ route('letters.index') }}">بازگشت به دبیرخانه</a><section class="card p-4 my-3"><h4>{{ $letter->register_number }} — {{ $letter->subject }}</h4><p>{{ $letter->correspondent }} — {{ $letter->issued_on }} — {{ \App\Models\ExternalLetter::statuses()[$letter->status] }}</p><p style="white-space:pre-wrap">{{ $letter->body }}</p><p>مسئول: {{ $letter->assignee?->name }}</p><p>{{ $letter->completion_note }}</p>
@if($letter->media)<a href="{{ route('letters.attachment',$letter->id) }}">دریافت پیوست</a>@endif
@can('can-access',['letters','edit']) @if($letter->status!=='closed')<details class="my-3"><summary>ویرایش یا ارجاع نامه</summary><form method="POST" action="{{ route('letters.update',$letter->id) }}">@method('PATCH') @include('panel.letters.form')</form></details>
<form method="POST" action="{{ route('letters.transition',$letter->id) }}">@csrf<input type="hidden" name="lock_version" value="{{ $letter->lock_version }}"><label>وضعیت پیگیری</label><select name="status" class="form-select">@if($letter->status==='registered')<option value="in_progress">در حال پیگیری</option>@endif<option value="closed">مختومه</option></select><label>نتیجه پیگیری (برای مختومه‌کردن الزامی)</label><textarea name="completion_note" class="form-control" rows="3"></textarea><button class="btn btn-primary mt-2">ثبت نتیجه</button></form>@endif @endcan</section>
@endsection
