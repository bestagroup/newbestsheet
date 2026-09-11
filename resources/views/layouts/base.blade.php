@php
    $theme = session('theme', 'theme-default');
    $themeStyle = $theme === 'theme-default-dark' ? 'dark-style' : 'light-style';
@endphp
    <!DOCTYPE html>
<html lang="fa" dir="rtl" class="{{ $themeStyle }}" data-theme="{{ $theme }}" data-assets-path="{{ asset('assets/') }}/" data-template="vertical-menu-template">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>@yield('title', $thispage['list'] ?? $thispage['title'] ?? config('app.name'))</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/icon.png') }}"/>

    <!-- Fonts and Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/materialdesignicons.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/IRANSans.css') }}"/>

    <!-- Toastr -->

    <!-- Core & Theme CSS -->
    @if(session('theme') === 'theme-default-dark')
        <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core-dark.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default-dark.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/core.css') }}">
        <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/theme-default.css') }}">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}"/>

    <!-- Vendor CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/dropzone6/dropzone.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css')}}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.css')}}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css')}}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css')}}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/jalalidatepicker/jalalidatepicker.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/css/form-enhancements.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/quill/editor.css')}}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/formvalidation/dist/css/formValidation.min.css')}}"/>
    <!-- Helpers & Config -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/js/config.js') }}"></script>

    @yield('style')
    @stack('styles')
</head>
<!-- Debug -->

<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <!-- Sidebar -->
        @include('partials.sidebar')
        <!-- / Sidebar -->

        <div class="layout-page">
            <!-- Header -->
            @include('partials.header')
            <!-- / Header -->

            <!-- Main Content -->
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Core JS -->
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
@include('partials.sweetalert-config')
<script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>

<script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>

<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>


<!-- Vendors JS -->
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/dropzone6/dropzone.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/fullcalendar/fullcalendar.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/jalalidatepicker/jalalidatepicker.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/jdate/jdate.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr-jdate.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/fa-jdate.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>


<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}?v={{ filemtime(public_path('assets/js/main.js')) }}"></script>
<script src="{{ asset('assets/js/form-enhancements.js') }}"></script>

@yield('script')
@stack('scripts')
</body>
</html>
