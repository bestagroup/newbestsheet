<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>@yield('code') | {{ config('app.name') }}</title>
    <style>
        body{margin:0;font-family:Tahoma,Arial,sans-serif;background:#f5f6f8;color:#263238;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px;box-sizing:border-box}
        .error-card{width:min(560px,100%);background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:36px;box-shadow:0 14px 36px rgba(15,23,42,.08);text-align:center}
        .code{font-size:64px;font-weight:800;line-height:1;color:#455a64;margin-bottom:18px}
        h1{font-size:22px;margin:0 0 12px}p{line-height:1.9;color:#607d8b;margin:0 0 24px}
        a{display:inline-block;text-decoration:none;background:#37474f;color:#fff;padding:10px 18px;border-radius:10px}
        small{display:block;margin-top:20px;color:#90a4ae;direction:ltr}
    </style>
</head>
<body>
<div class="error-card">
    <div class="code">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}">بازگشت به سامانه</a>
    @if(request()->attributes->get('request_id'))
        <small>Request ID: {{ request()->attributes->get('request_id') }}</small>
    @endif
</div>
</body>
</html>
