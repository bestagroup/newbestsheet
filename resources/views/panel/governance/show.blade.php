@extends('layouts.base')
@section('title','پرونده جلسه')
@section('content')
@include('partials.alerts')
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<a href="{{ route('meetings.index') }}">بازگشت به جلسات</a>
<section class="card p-4 my-3"><h4>{{ $meeting->title }}</h4><p>{{ $meeting->project->title }} — {{ $meeting->held_on }} — {{ \App\Models\PortfolioMeeting::statuses()[$meeting->status] }}</p><p>{{ $meeting->location }}</p><h6>حاضران</h6><p style="white-space:pre-wrap">{{ $meeting->attendees }}</p><h6>دستور جلسه</h6><ol>@foreach($meeting->agenda as $item)<li>{{ $item }}</li>@endforeach</ol>
@if($meeting->media)<a href="{{ route('media.download',$meeting->media) }}">دریافت پیوست صورتجلسه</a>@endif
@can('can-access',['meetings','edit'])
@if($meeting->status==='draft' && $meeting->resolutions->isEmpty())<details class="my-3"><summary>ویرایش پیش‌نویس</summary><form method="POST" action="{{ route('meetings.update',$meeting) }}">@method('PATCH') @include('panel.governance.form',['projects'=>collect([$meeting->project])])</form></details>@endif
@if(in_array($meeting->status,['draft','held']))<form method="POST" action="{{ route('meetings.transition',$meeting) }}" class="d-flex gap-2 mt-3">@csrf<input type="hidden" name="lock_version" value="{{ $meeting->lock_version }}"><select name="status" class="form-select">@if($meeting->status==='draft')<option value="held">برگزار شد</option>@else<option value="finalized">نهایی‌سازی صورتجلسه</option>@endif<option value="cancelled">لغو جلسه</option></select><button class="btn btn-primary">ثبت وضعیت</button></form>@endif
@endcan
</section>
<section class="card p-4"><h5>مصوبات و پیگیری اجرا</h5>
@foreach($meeting->resolutions as $resolution)<article class="border rounded p-3 mb-3"><strong>بند {{ $resolution->agenda_index+1 }}</strong><p style="white-space:pre-wrap">{{ $resolution->body }}</p><p>مسئول: {{ $resolution->assignee?->name }} — مهلت: {{ $resolution->due_on?->format('Y-m-d') ?? 'تعیین نشده' }} — {{ $resolution->status==='completed'?'انجام‌شده':'باز' }}</p><p>{{ $resolution->completion_note }}</p>
@can('can-access',['meetings','edit']) @if($meeting->status==='finalized' && $resolution->status==='pending')<form method="POST" action="{{ route('meetings.resolutions.complete',[$meeting,$resolution->id]) }}">@csrf @method('PATCH')<label class="form-label">گزارش اجرای مصوبه</label><textarea required name="completion_note" class="form-control"></textarea><button class="btn btn-success mt-2">ثبت انجام مصوبه</button></form>@endif @endcan</article>@endforeach
@can('can-access',['meetings','edit']) @if(in_array($meeting->status,['draft','held']))
<form method="POST" action="{{ route('meetings.resolutions.store',$meeting) }}" class="row g-3">@csrf
<div class="col-md-6"><label>بند دستور جلسه</label><select name="agenda_index" class="form-select">@foreach($meeting->agenda as $index=>$item)<option value="{{ $index }}">{{ $index+1 }}. {{ $item }}</option>@endforeach</select></div>
<div class="col-md-3"><label>مسئول پیگیری</label><select required name="assigned_to" class="form-select">@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
<div class="col-md-3"><label>مهلت اجرا (میلادی)</label><input type="date" name="due_on" class="form-control"></div>
<div class="col-12"><label>متن مصوبه / نتیجه بررسی بند</label><textarea required name="body" class="form-control" rows="3"></textarea></div><div><button class="btn btn-primary">افزودن مصوبه</button></div></form>
@endif @endcan</section>
@endsection
