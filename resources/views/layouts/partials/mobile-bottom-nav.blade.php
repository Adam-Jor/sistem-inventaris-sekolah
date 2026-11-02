{{-- Navigasi Bawah Mobile-First Khusus Role User (< 768px) --}}
<nav class="app-mobile-bottom-nav d-md-none" aria-label="Navigasi cepat mobile">
    <ul class="app-mobile-bottom-nav-list">
        <li>
            <a
                href="{{ route('dashboard') }}"
                class="app-mobile-bottom-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7.5" height="7.5" rx="1.75"></rect>
                    <rect x="13.5" y="3" width="7.5" height="7.5" rx="1.75"></rect>
                    <rect x="3" y="13.5" width="7.5" height="7.5" rx="1.75"></rect>
                    <rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.75"></rect>
                </svg>
                <span>Beranda</span>
            </a>
        </li>

        <li>
            <a
                href="{{ route('items.index') }}"
                class="app-mobile-bottom-link {{ request()->routeIs('items.*') ? 'active' : '' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                    <path d="m3.3 7 8.7 5 8.7-5"></path>
                    <path d="M12 22V12"></path>
                </svg>
                <span>Barang</span>
            </a>
        </li>

        <li>
            <a
                href="{{ route('loans.create') }}"
                class="app-mobile-bottom-link app-mobile-bottom-link-cta {{ request()->routeIs('loans.create') ? 'active' : '' }}"
            >
                <span class="app-mobile-bottom-cta-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="width: 17px; height: 17px;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </span>
                <span>Pinjam</span>
            </a>
        </li>

        <li>
            <a
                href="{{ route('loans.index') }}"
                class="app-mobile-bottom-link {{ request()->routeIs('loans.index', 'loans.show') ? 'active' : '' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path>
                    <rect x="9" y="3" width="6" height="4" rx="1"></rect>
                    <path d="M9 12h6"></path>
                    <path d="M9 16h4"></path>
                </svg>
                <span>Pinjaman</span>
            </a>
        </li>

        <li>
            <a
                href="{{ route('profile.edit') }}"
                class="app-mobile-bottom-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                </svg>
                <span>Profil</span>
            </a>
        </li>
    </ul>
</nav>

