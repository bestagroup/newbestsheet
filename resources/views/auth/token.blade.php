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
                    <p class="mb-4 text-center">کد ارسال شده را وارد کنید</p>

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

                    <form id="formAuthentication" class="mb-3" action="{{ route('checktoken') }}" method="POST" novalidate>
                        @csrf
                        <div class="form-floating form-floating-outline mb-3">
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" placeholder="کد پیامک شده" value="{{ old('code') }}" autofocus required>
                            <label for="code">کد پیامک شده</label>
                            @error('code')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <button class="btn btn-primary d-grid w-100" type="submit">ارسال</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')

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
