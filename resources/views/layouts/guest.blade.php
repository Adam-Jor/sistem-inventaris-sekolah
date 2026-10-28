<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/apple-icon.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">

    <title>{{ $title }} | {{ config('app.name') }}</title>

    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">
    <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet">
    <link id="pagestyle" href="{{ asset('assets/css/soft-ui-dashboard.min.css') }}" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-layout d-flex flex-column min-vh-100">
    <div class="container position-sticky z-index-sticky top-0">
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg blur blur-rounded top-0 z-index-3 shadow position-absolute my-2 my-sm-3 py-2 start-0 end-0 mx-2 mx-sm-4 px-2 px-sm-3">
                    <div class="container-fluid px-1 px-sm-2 flex-nowrap">
                        <a class="navbar-brand font-weight-bolder ms-lg-0 ms-1 me-2 text-truncate d-flex align-items-center" href="{{ url('/') }}">
                            <img src="{{ asset('assets/img/logo-ct-dark.png') }}" alt="Logo" height="22" class="me-2">
                            <span>{{ config('app.name') }}</span>
                        </a>
                        <div class="d-flex align-items-center ms-auto flex-shrink-0">
                            @if (Route::has('login'))
                                <a class="nav-link text-sm font-weight-bold px-2 me-1 me-sm-2 {{ request()->routeIs('login') ? 'text-dark d-none d-sm-inline-block' : 'text-secondary' }}" href="{{ route('login') }}">Masuk</a>
                            @endif
                            @if (Route::has('register'))
                                <a class="btn btn-sm bg-gradient-dark mb-0 px-3 {{ request()->routeIs('register') ? 'd-none d-sm-inline-block' : '' }}" href="{{ route('register') }}">Daftar</a>
                            @endif
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    <main class="main-content mt-0 flex-grow-1 d-flex align-items-center">
        <section class="w-100">
            <div class="page-header min-vh-75 pt-6 pb-4 pt-md-0 pb-md-0">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-4 col-lg-5 col-md-6 col-sm-10 col-12 d-flex flex-column mx-auto">
                            <div class="card card-plain auth-card mt-4 mt-sm-5 mt-md-7 mb-2 mb-md-0">
                                <div class="card-header pb-0 text-start bg-transparent">
                                    <h3 class="font-weight-bolder text-info text-gradient mb-1">{{ $title }}</h3>
                                    @if ($subtitle !== '')
                                        <p class="text-sm text-secondary mb-0">{{ $subtitle }}</p>
                                    @endif
                                </div>

                                <div class="card-body">
                                    {{ $slot }}
                                </div>

                                @isset($footer)
                                    <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                        {{ $footer }}
                                    </div>
                                @endisset
                            </div>
                        </div>

                        <div class="col-md-6 d-none d-md-block">
                            <div class="oblique position-absolute top-0 h-100 d-md-block d-none me-n8">
                                <div class="oblique-image bg-cover position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6"
                                     style="background-image: url('{{ asset('assets/img/curved-images/curved-6.jpg') }}')"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer py-3 py-md-4 mt-auto">
        <div class="container">
            <p class="mb-0 text-xs text-sm-sm text-secondary text-center">&copy; {{ now()->year }} {{ config('app.name') }}</p>
        </div>
    </footer>

    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/soft-ui-dashboard.min.js') }}"></script>
</body>
</html>
