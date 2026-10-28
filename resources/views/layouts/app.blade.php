<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <title>{{ $title !== '' ? $title.' | ' : '' }}{{ config('app.name') }}</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">
    <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet">

    <!-- Soft UI Dashboard -->
    <link id="pagestyle" href="{{ asset('assets/css/soft-ui-dashboard.min.css') }}" rel="stylesheet">

    <!-- CSS & Alpine.js aplikasi -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="g-sidenav-show bg-gray-100">
    @include('layouts.partials.sidebar')

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        @include('layouts.partials.navbar')

        <div class="container-fluid py-4">
            <x-flash-message />

            {{ $slot }}

            <footer class="footer pt-4">
                <div class="container-fluid">
                    <p class="text-sm text-secondary text-center text-lg-start mb-0">
                        &copy; {{ now()->year }} {{ config('app.name') }}
                    </p>
                </div>
            </footer>
        </div>
    </main>

    <!-- Soft UI Dashboard JS -->
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/soft-ui-dashboard.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
