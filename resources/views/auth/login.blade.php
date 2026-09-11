@extends('layouts.auth')

@section('title', 'ورود به مدیریت سایت')

@section('content')
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner py-4">
            <div class="card p-2">
                <div class="app-brand justify-content-center mt-5">
                    <a href="{{ url('/') }}" class="app-brand-link gap-2">
                        <span class="app-brand-logo demo">
                          <img src="{{ asset('assets/img/sinavclogo.png') }}" alt="توسعه دانش بنیان سینا" width="40">
                        </span>
                        <span class="app-brand-text demo text-heading fw-bold">توسعه دانش بنیان سینا</span>
                    </a>
                </div>

                <div class="card-body mt-2">
                    <h4 class="mb-2 fw-semibold">سامانه یکپارچه سازی ارتباط و نظارت بر شرکت ها </h4>
                    <p class="mb-4 text-center">(بِست شیت)</p>

                    {{-- Flash messages (success/info/warn/error) --}}
                    @include('partials.alerts')

                    {{-- All validation/auth errors as a list on top --}}
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>خطا در ورود:</strong>
                            <ul class="mb-0 mt-2 pe-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="formAuthentication" class="mb-3" action="{{ route('login') }}" method="POST" novalidate>
                        @csrf
                        <div class="form-floating form-floating-outline mb-3">
                            <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="ایمیل" value="{{ old('email') }}" autofocus required>
                            <label for="email">ایمیل</label>
                            @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-password-toggle">
                                <div class="input-group input-group-merge">
                                    <div class="form-floating form-floating-outline">
                                        <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="رمز عبور" required>
                                        <label for="password">رمز عبور</label>
                                        @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <span class="input-group-text cursor-pointer"><i class="mdi mdi-eye-off-outline"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 d-flex justify-content-between">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember-me" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember-me">مرا به خاطر بسپار</label>
                            </div>
                            <a href="{{ route('password.request') }}">فراموشی رمز عبور؟</a>
                        </div>

                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">ورود</button>
                        </div>
                        <div class="mb-3">
                            <a href="{{url('login/google')}}" class="btn btn-danger d-grid w-100"><i class="fa fa-google mr-2"></i> ورود با حساب گوگل </a>
                        </div>
                        <div class="mb-3">
                            <a href="{{route('otplogin')}}" class="btn btn-info d-grid w-100"><i class="fa fa-google mr-2"></i> ورود با رمز یکبارمصرف </a>
                        </div>
                    </form>

                    <p class="text-center">
                        <a href="{{ route('register') }}">
                            <span>برای ایجاد حساب و ثبت طرح کلیک کنید</span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Password toggle
            const togglePassword = document.querySelector('.form-password-toggle .input-group-text');
            const passwordInput = document.querySelector('#password');
            const icon = togglePassword?.querySelector('i');
            togglePassword?.addEventListener('click', function () {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                icon?.classList.toggle('mdi-eye-outline');
                icon?.classList.toggle('mdi-eye-off-outline');
            });
        });
    </script>

    {{-- Toastr: show all errors as toast too (optional) --}}
    <script>
        @if (session('success')) toastr.success(@json(session('success'))); @endif
        @if (session('info'))    toastr.info(@json(session('info')));     @endif
        @if (session('warning')) toastr.warning(@json(session('warning'))); @endif
        @if (session('error'))   toastr.error(@json(session('error')));   @endif

        @if ($errors->any())
        @foreach ($errors->all() as $error)
        toastr.error(@json($error));
        @endforeach
        @endif
    </script>
@endpush
