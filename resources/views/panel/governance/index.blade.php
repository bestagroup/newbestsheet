@extends('layouts.base')
@section('title','امور مجامع و مصوبات')
@section('content')
@include('partials.alerts')
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h4>امور مجامع و مصوبات شرکت‌های پورتفو</h4>
@can('can-access',['meetings','insert'])
<details class="card mb-4 p-3"><summary>ثبت جلسه جدید</summary><form method="POST" action="{{ route('meetings.store') }}" class="mt-3">@include('panel.governance.form')</form></details>
@endcan
<div class="card p-3"><div class="table-responsive"><table class="table"><thead><tr><th>طرح</th><th>عنوان</th><th>تاریخ</th><th>وضعیت</th><th>مصوبات باز / کل</th></tr></thead><tbody>
@forelse($meetings as $row)<tr><td>{{ $row->project->title }}</td><td><a href="{{ route('meetings.show',$row) }}">{{ $row->title }}</a></td><td>{{ $row->held_on }}</td><td>{{ \App\Models\PortfolioMeeting::statuses()[$row->status] }}</td><td>{{ $row->pending_count }} / {{ $row->resolutions_count }}</td></tr>@empty<tr><td colspan="5">جلسه‌ای ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>{{ $meetings->links() }}</div>
@endsection
