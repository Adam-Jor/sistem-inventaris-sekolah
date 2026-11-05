<x-app-layout title="Dashboard Admin" :breadcrumbs="['Dashboard' => null]">
    {{-- Banner Sambutan & Aksi Cepat --}}
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="font-weight-bolder mb-1">Selamat datang, {{ auth()->user()->name }}</h4>
                        <p class="text-sm text-secondary mb-0">
                            Anda masuk sebagai <span class="badge badge-sm bg-gradient-primary">Admin</span>.
                            Kelola inventaris barang, verifikasi pengajuan peminjaman, dan pantau aktivitas sistem.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto flex-shrink-0">
                        <a href="{{ route('items.index') }}" class="btn bg-gradient-primary mb-0 flex-fill flex-md-grow-0">Kelola Barang</a>
                        <a href="{{ route('loans.index') }}" class="btn btn-outline-primary mb-0 flex-fill flex-md-grow-0">Verifikasi Peminjaman</a>
                        <a href="{{ route('statistics.index') }}" class="btn btn-outline-primary mb-0 flex-fill flex-md-grow-0">Lihat Statistik</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kartu Statistik Utama --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 user-stat-card">
                <span class="mask bg-primary opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-box-2 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ number_format($stats['total_items'], 0, ',', '.') }}</h4>
                    <span class="text-white text-sm user-stat-label">Total Barang</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 user-stat-card">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-collection text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ number_format($stats['total_categories'], 0, ',', '.') }}</h4>
                    <span class="text-white text-sm user-stat-label">Total Kategori</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card h-100 user-stat-card">
                <span class="mask bg-info opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-single-copy-04 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ number_format($stats['active_loans'], 0, ',', '.') }}</h4>
                    <span class="text-white text-sm user-stat-label">Peminjaman Aktif</span>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-6 col-xl">
            <div class="card h-100 user-stat-card">
                <span class="mask bg-warning opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-bell-55 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ number_format($stats['waiting_loans'], 0, ',', '.') }}</h4>
                    <span class="text-white text-sm user-stat-label">Menunggu Verifikasi</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <div class="card h-100 user-stat-card">
                <span class="mask bg-danger opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-time-alarm text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ number_format($stats['overdue_loans'], 0, ',', '.') }}</h4>
                    <span class="text-white text-sm user-stat-label">Peminjaman Terlambat</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik Tren Peminjaman Per Bulan --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="mb-0">Tren Peminjaman 6 Bulan Terakhir</h6>
                        <p class="text-xs text-secondary mb-0">Perbandingan jumlah pengajuan dan peminjaman yang disetujui per bulan</p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <span class="stat-legend"><span class="stat-legend-dot bg-soft-primary-strong"></span>Pengajuan</span>
                        <span class="stat-legend"><span class="stat-legend-dot bg-primary-gradient"></span>Disetujui</span>
                        <a href="{{ route('statistics.index') }}" class="btn btn-sm btn-outline-primary mb-0">Statistik Lengkap</a>
                    </div>
                </div>
                <div class="card-body p-3 pt-3">
                    <x-monthly-trend-chart :monthlyTrend="$monthlyTrend" />
                </div>
            </div>
        </div>
    </div>

    {{-- Peminjaman Terbaru & Aktivitas Terbaru --}}
    <div class="row">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header pb-2 pb-md-0 d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="mb-0">Peminjaman Terbaru</h6>
                        <p class="text-xs text-secondary mb-0">5 transaksi peminjaman terakhir di sistem</p>
                    </div>
                    <a href="{{ route('loans.index') }}" class="btn btn-sm btn-outline-secondary mb-0 flex-shrink-0">Lihat Semua</a>
                </div>
                <div class="card-body px-0 pt-2 pb-2">
                    {{-- Tampilan Tabel Desktop (>= 768px) --}}
                    <div class="table-responsive p-0 d-none d-md-block">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Peminjam</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Barang</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jatuh Tempo</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentLoans as $loan)
                                    @php
                                        $badgeColor = match ($loan->status) {
                                            'dipinjam' => 'info',
                                            'dikembalikan' => 'success',
                                            'terlambat' => 'danger',
                                            'menunggu' => 'warning',
                                            'ditolak' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex px-3 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $loan->user?->name ?? '-' }}</h6>
                                                    <p class="text-xs text-secondary mb-0">{{ $loan->user?->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">
                                                <a href="{{ route('loans.show', $loan) }}" class="text-dark">
                                                    {{ $loan->item?->name ?? '-' }}
                                                </a>
                                            </p>
                                            <p class="text-xs text-secondary mb-0">{{ number_format($loan->quantity, 0, ',', '.') }} unit &bull; {{ $loan->item?->code }}</p>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $loan->due_date?->translatedFormat('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-{{ $badgeColor }}">{{ ucfirst($loan->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-sm text-secondary">
                                            Belum ada data peminjaman.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Tampilan Kartu Mobile (< 768px) --}}
                    <div class="mobile-table-cards d-md-none">
                        @forelse ($recentLoans as $loan)
                            @php
                                $badgeColor = match ($loan->status) {
                                    'dipinjam' => 'info',
                                    'dikembalikan' => 'success',
                                    'terlambat' => 'danger',
                                    'menunggu' => 'warning',
                                    'ditolak' => 'secondary',
                                    default => 'secondary',
                                };
                            @endphp
                            <div class="mobile-row-card">
                                <div class="mobile-row-header">
                                    <div class="mobile-row-header-main">
                                        <span class="mobile-row-caption">Barang Dipinjam</span>
                                        <h6 class="mobile-row-title">
                                            <a href="{{ route('loans.show', $loan) }}">{{ $loan->item?->name ?? '-' }}</a>
                                        </h6>
                                    </div>
                                    <span class="badge badge-sm bg-gradient-{{ $badgeColor }} flex-shrink-0">
                                        {{ ucfirst($loan->status) }}
                                    </span>
                                </div>

                                <div class="mobile-row-meta">
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Peminjam</span>
                                        <span class="mobile-field-value">{{ $loan->user?->name ?? '-' }}</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Kode Barang</span>
                                        <span class="mobile-field-value">{{ $loan->item?->code ?? '-' }}</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Jumlah</span>
                                        <span class="mobile-field-value">{{ number_format($loan->quantity, 0, ',', '.') }} unit</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Jatuh Tempo</span>
                                        <span class="mobile-field-value {{ $loan->status === 'terlambat' ? 'text-danger' : '' }}">
                                            {{ $loan->due_date?->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mobile-row-actions">
                                    <a href="{{ route('loans.show', $loan) }}" class="btn mobile-btn-detail">
                                        Lihat Detail Peminjaman
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="mobile-empty-state">
                                <p class="text-sm text-secondary mb-0">Belum ada data peminjaman.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0">Aktivitas Terbaru</h6>
                        <p class="text-xs text-secondary mb-0">Jejak audit aktivitas sistem</p>
                    </div>
                    <a href="{{ route('activity-logs.index') }}" class="text-xs font-weight-bold text-primary">Semua</a>
                </div>
                <div class="card-body p-3">
                    <div class="timeline timeline-one-side">
                        @forelse ($recentActivities as $log)
                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="ni ni-bell-55 text-primary text-gradient"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">{{ $log->description }}</h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">
                                        {{ $log->user?->name ?? 'Sistem' }} &bull; {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-secondary mb-0">Belum ada aktivitas tercatat.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
