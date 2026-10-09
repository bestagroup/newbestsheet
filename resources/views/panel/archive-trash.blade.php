@extends('layouts.base')
@section('title','بازیابی فایل‌های بایگانی‌شده')
@section('content')
@include('partials.alerts')<h4>بازیابی فایل‌های بایگانی‌شده</h4><a href="{{ route('filemanager.index') }}">بازگشت به آرشیو</a><div class="card p-3 mt-3"><table class="table"><thead><tr><th>شناسه</th><th>نام فایل</th><th>بایگانی در</th><th>بازیابی</th></tr></thead><tbody>@foreach($files as $file)<tr><td>{{ $file->id }}</td><td>{{ $file->original_name }}</td><td>{{ $file->deleted_at }}</td><td><form method="POST" action="{{ route('archive.restore',$file->id) }}">@csrf<button class="btn btn-primary">بازیابی</button></form></td></tr>@endforeach</tbody></table>{{ $files->links() }}</div>
@endsection
