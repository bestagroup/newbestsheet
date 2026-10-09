@extends('layouts.base')
@section('title','دبیرخانه مکاتبات خارجی')
@section('content')
@include('partials.alerts')
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h4>دبیرخانه مکاتبات خارجی</h4><p>ثبت و پیگیری نامه‌های وارده و صادره</p>
@can('can-access',['letters','insert'])<details class="card p-3 mb-4"><summary>ثبت نامه</summary><form class="mt-3" method="POST" action="{{ route('letters.store') }}">@include('panel.letters.form')</form></details>@endcan
<form method="GET" class="d-flex gap-2 mb-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="جست‌وجوی موضوع، طرف مکاتبه یا شماره مرجع"><select name="status" class="form-select"><option value="">همه وضعیت‌ها</option>@foreach(\App\Models\ExternalLetter::statuses() as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select><button class="btn btn-primary">جست‌وجو</button></form>
<div class="card p-3"><div class="table-responsive"><table class="table"><thead><tr><th>شماره ثبت</th><th>موضوع</th><th>طرف مکاتبه</th><th>مسئول</th><th>وضعیت</th><th>مهلت</th></tr></thead><tbody>@forelse($letters as $row)<tr><td>{{ $row->register_number }}</td><td><a href="{{ route('letters.show',$row->id) }}">{{ $row->subject }}</a>@if($row->confidential)<span class="badge bg-warning">محرمانه</span>@endif</td><td>{{ $row->correspondent }}</td><td>{{ $row->assignee?->name }}</td><td>{{ \App\Models\ExternalLetter::statuses()[$row->status] }}</td><td @class(['text-danger'=>$row->status!=='closed' && $row->due_on?->isPast()])>{{ $row->due_on?->format('Y-m-d') }}</td></tr>@empty<tr><td colspan="6">نامه‌ای یافت نشد.</td></tr>@endforelse</tbody></table></div>{{ $letters->links() }}</div>
@endsection
