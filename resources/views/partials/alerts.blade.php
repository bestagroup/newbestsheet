@php
    $flash = session('flash');
    $success = session('success');
    $info = session('info');
    $warning = session('warning');
    $error = session('error') ?? session('status');
@endphp

@if ($flash)
    <div class="alert alert-{{ $flash['type'] ?? 'info' }} alert-dismissible fade show" role="alert" data-auto-dismiss="true">
        @if (!empty($flash['title'])) <strong>{{ $flash['title'] }}</strong><br> @endif
        {!! nl2br(e($flash['message'] ?? '')) !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($success)
    <div class="alert alert-success alert-dismissible fade show" role="alert" data-auto-dismiss="true">
        {!! nl2br(e($success)) !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($info)
    <div class="alert alert-info alert-dismissible fade show" role="alert" data-auto-dismiss="true">
        {!! nl2br(e($info)) !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($warning)
    <div class="alert alert-warning alert-dismissible fade show" role="alert" data-auto-dismiss="true">
        {!! nl2br(e($warning)) !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($error)
    <div class="alert alert-danger alert-dismissible fade show" role="alert" data-auto-dismiss="true">
        {!! nl2br(e($error)) !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
