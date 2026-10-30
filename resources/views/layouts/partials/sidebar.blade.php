@php
    $authUser = auth()->user();

    // Daftar menu langsung ditampilkan seluruhnya tanpa pemisah seksi.
    if ($authUser->isAdmin()) {
        $menuItems = [
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
            ['label' => 'Kategori', 'icon' => 'categories', 'route' => 'categories.index', 'match' => 'categories.*'],
            ['label' => 'Barang', 'icon' => 'items', 'route' => 'items.index', 'match' => 'items.*'],
            ['label' => 'Peminjaman', 'icon' => 'loans', 'route' => 'loans.index', 'match' => 'loans.*'],
            ['label' => 'Pengembalian', 'icon' => 'returns', 'route' => 'return-logs.index', 'match' => 'return-logs.*'],
            ['label' => 'Activity Log', 'icon' => 'activity-logs', 'route' => 'activity-logs.index', 'match' => 'activity-logs.*'],
            ['label' => 'Manajemen User', 'icon' => 'users', 'route' => 'users.index', 'match' => 'users.*'],
            ['label' => 'Profil', 'icon' => 'profile', 'route' => 'profile.edit', 'match' => 'profile.*'],
        ];
    } else {
        $menuItems = [
            ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
            ['label' => 'Daftar Barang', 'icon' => 'items', 'route' => 'items.index', 'match' => 'items.*'],
            ['label' => 'Peminjaman Saya', 'icon' => 'loans', 'route' => 'loans.index', 'match' => 'loans.*'],
            ['label' => 'Profil', 'icon' => 'profile', 'route' => 'profile.edit', 'match' => 'profile.*'],
        ];
    }
@endphp

<aside class="sidenav app-sidenav navbar navbar-vertical navbar-expand-xs fixed-start bg-white" id="sidenav-main">
    <div class="sidenav-header app-sidenav-header">
        {{-- id="iconSidenav" wajib ada: dipakai oleh soft-ui-dashboard.js saat sidebar dibuka di layar kecil --}}
        <i class="ni ni-fat-remove p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>

        <a class="navbar-brand m-0 d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <img src="{{ asset('assets/img/logo-ct-dark.png') }}" class="navbar-brand-img" alt="Logo">
            <span class="app-brand-text">{{ config('app.name') }}</span>
        </a>
    </div>

    <div class="app-sidenav-menu" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            @foreach ($menuItems as $item)
                @php $active = request()->routeIs($item['match']); @endphp
                <li class="nav-item">
                    <a class="nav-link app-nav-link {{ $active ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        @switch($item['icon'])
                            @case('dashboard')
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                @break

                            @case('categories')
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                                </svg>
                                @break

                            @case('items')
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                                @break

                            @case('loans')
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                    <line x1="8" y1="11" x2="16" y2="11"></line>
                                    <line x1="8" y1="15" x2="13" y2="15"></line>
                                </svg>
                                @break

                            @case('returns')
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                    <rect x="1" y="3" width="22" height="5" rx="1"></rect>
                                    <polyline points="9 14 11 16 15 12"></polyline>
                                </svg>
                                @break

                            @case('activity-logs')
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="18" height="18" rx="4"></rect>
                                    <line x1="8" y1="16" x2="8" y2="12"></line>
                                    <line x1="12" y1="16" x2="12" y2="8"></line>
                                    <line x1="16" y1="16" x2="16" y2="14"></line>
                                </svg>
                                @break

                            @case('users')
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                @break

                            @default
                                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                        @endswitch

                        <span class="nav-link-text">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="app-sidenav-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="app-logout-btn">
                <svg class="app-nav-icon" xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</aside>
