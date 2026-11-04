@php
    $crumbs = collect($breadcrumbs);
    $pageTitle = $title !== '' ? $title : ($crumbs->keys()->last() ?? config('app.name'));
@endphp

<nav class="navbar navbar-main navbar-expand-lg px-0 mx-2 mx-md-4 shadow-none border-radius-xl" id="navbarBlur" navbar-scroll="true">
    <div class="container-fluid py-1 px-2 px-sm-3 d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center min-w-0">
            <a href="{{ route('dashboard') }}" class="d-inline-flex d-xl-none align-items-center me-2 me-sm-3 flex-shrink-0" title="{{ config('app.name') }}">
                <img src="{{ asset('assets/img/logo-sekolah.png') }}" alt="Logo {{ config('app.name') }}" style="max-height: 2.1rem; width: auto;">
            </a>
            <nav aria-label="breadcrumb" class="min-w-0">
                <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-2 me-sm-5 flex-wrap">
                    @if (request()->routeIs('dashboard'))
                        <li class="breadcrumb-item text-sm text-dark active" aria-current="page">Beranda</li>
                    @else
                        <li class="breadcrumb-item text-sm">
                            <a class="opacity-5 text-dark" href="{{ route('dashboard') }}">Beranda</a>
                        </li>
                        @foreach ($breadcrumbs as $label => $url)
                            @if ($loop->last || ! $url)
                                <li class="breadcrumb-item text-sm text-dark active" aria-current="page">{{ $label }}</li>
                            @else
                                <li class="breadcrumb-item text-sm">
                                    <a class="opacity-5 text-dark" href="{{ $url }}">{{ $label }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                </ol>
                <h6 class="font-weight-bolder mb-0">{{ $pageTitle }}</h6>
            </nav>
        </div>

        <div class="d-flex align-items-center">
            <div class="dropdown">
                <a href="javascript:;" class="nav-link text-body font-weight-bold px-0" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ni ni-circle-08 me-sm-1"></i>
                    <span class="d-sm-inline d-none">{{ auth()->user()->name }}</span>
                    <span class="badge badge-sm bg-gradient-secondary ms-2 d-none d-sm-inline">{{ auth()->user()->isAdmin() ? 'Admin' : 'User' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end px-2 py-3" aria-labelledby="userMenuButton">
                    <li>
                        <a class="dropdown-item border-radius-md" href="{{ route('profile.edit') }}">
                            <i class="ni ni-settings-gear-65 me-2"></i>Profil
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item border-radius-md">
                                <i class="ni ni-user-run me-2"></i>Keluar
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            {{-- Tombol buka sidebar (layar kecil) --}}
            <a href="javascript:;" class="nav-link text-body p-0 ps-3 d-xl-none" id="iconNavbarSidenav" aria-label="Buka menu">
                <div class="sidenav-toggler-inner">
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                </div>
            </a>
        </div>
    </div>
</nav>
