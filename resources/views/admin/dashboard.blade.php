<x-app-layout title="Dashboard Admin" :breadcrumbs="['Dashboard' => null]">
    {{-- Header Operasional & Aksi Utama --}}
    <div class="card mb-4 stat-surface-card">
        <div class="card-body p-3 p-md-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="stat-live-dot" aria-hidden="true"></span>
                    <span class="stat-eyebrow">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
                <h5 class="stat-page-title mb-1">Ringkasan Inventaris & Peminjaman</h5>
                <p class="stat-page-subtitle mb-0">
                    Pantau ketersediaan aset sekolah, verifikasi pengajuan peminjaman, dan tindak lanjuti keterlambatan pengembalian
                </p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-lg-auto flex-shrink-0">
                <a href="{{ route('loans.index') }}" class="btn btn-sm bg-gradient-primary mb-0 flex-fill flex-lg-grow-0">Verifikasi Peminjaman</a>
                <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary mb-0 flex-fill flex-lg-grow-0">Kelola Barang</a>
                <a href="{{ route('statistics.index') }}" class="stat-quiet-action ms-lg-1">Lihat Statistik &rarr;</a>
            </div>
        </div>
    </div>

    {{-- Kartu KPI Utama (Angka Tabular, Tanpa Dekorasi Berlebih) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('items.index') }}" class="card h-100 stat-kpi-card stat-kpi-link">
                <div class="card-body">
                    <span class="stat-kpi-label">Total Barang</span>
                    <div class="stat-kpi-value">{{ number_format($stats['total_items'], 0, ',', '.') }}</div>
                    <span class="stat-kpi-hint">Jenis aset terdaftar</span>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('categories.index') }}" class="card h-100 stat-kpi-card stat-kpi-link">
                <div class="card-body">
                    <span class="stat-kpi-label">Total Kategori</span>
                    <div class="stat-kpi-value">{{ number_format($stats['total_categories'], 0, ',', '.') }}</div>
                    <span class="stat-kpi-hint">Kelompok inventaris</span>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('loans.index', ['status' => 'dipinjam']) }}" class="card h-100 stat-kpi-card stat-kpi-link">
                <div class="card-body">
                    <span class="stat-kpi-label">Peminjaman Aktif</span>
                    <div class="stat-kpi-value">{{ number_format($stats['active_loans'], 0, ',', '.') }}</div>
                    <span class="stat-kpi-hint">Transaksi sedang berjalan</span>
                </div>
            </a>
        </div>

        <div class="col-6 col-md-6 col-xl">
            <a href="{{ route('loans.index', ['status' => 'menunggu']) }}" class="card h-100 stat-kpi-card stat-kpi-link">
                <div class="card-body">
                    <span class="stat-kpi-label">Menunggu Verifikasi</span>
                    <div class="stat-kpi-value {{ $stats['waiting_loans'] > 0 ? 'text-accent-primary' : '' }}">
                        {{ number_format($stats['waiting_loans'], 0, ',', '.') }}
                    </div>
                    <span class="stat-kpi-hint {{ $stats['waiting_loans'] > 0 ? 'stat-kpi-hint-warn' : '' }}">
                        {{ $stats['waiting_loans'] > 0 ? 'Perlu keputusan Admin' : 'Tidak ada antrean baru' }}
                    </span>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6 col-xl">
            <a href="{{ route('loans.index', ['status' => 'terlambat']) }}" class="card h-100 stat-kpi-card stat-kpi-link">
                <div class="card-body">
                    <span class="stat-kpi-label">Peminjaman Terlambat</span>
                    <div class="stat-kpi-value {{ $stats['overdue_loans'] > 0 ? 'text-semantic-danger' : '' }}">
                        {{ number_format($stats['overdue_loans'], 0, ',', '.') }}
                    </div>
                    <span class="stat-kpi-hint {{ $stats['overdue_loans'] > 0 ? 'stat-kpi-hint-danger' : '' }}">
                        {{ $stats['overdue_loans'] > 0 ? 'Melewati batas kembali' : 'Semua kembali tepat waktu' }}
                    </span>
                </div>
            </a>
        </div>
    </div>

    {{-- Grafik Tren Peminjaman Per Bulan --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card stat-surface-card">
                <div class="card-header pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="stat-section-title mb-0">Tren Peminjaman 6 Bulan Terakhir</h6>
                        <p class="stat-section-subtitle mb-0">Perbandingan jumlah pengajuan dan peminjaman yang disetujui per bulan</p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <span class="stat-legend"><span class="stat-legend-dot bg-soft-primary-strong"></span>Pengajuan</span>
                        <span class="stat-legend"><span class="stat-legend-dot bg-primary-gradient"></span>Disetujui</span>
                        <a href="{{ route('statistics.index') }}" class="stat-quiet-action">Statistik Lengkap &rarr;</a>
                    </div>
                </div>
                <div class="card-body p-3 pt-3">
                    <x-monthly-trend-chart :monthlyTrend="$monthlyTrend" />
                </div>
            </div>
        </div>
    </div>

    {{-- Peminjaman Terbaru & Aktivitas Terbaru --}}
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-2 pb-md-0 d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="stat-section-title mb-0">Peminjaman Terbaru</h6>
                        <p class="stat-section-subtitle mb-0">5 transaksi peminjaman terakhir di sistem</p>
                    </div>
                    <a href="{{ route('loans.index') }}" class="stat-quiet-action flex-shrink-0">Lihat Semua &rarr;</a>
                </div>
                <div class="card-body px-0 pt-2 pb-2">
                    {{-- Tampilan Tabel Desktop (>= 768px) --}}
                    <div class="table-responsive p-0 d-none d-md-block">
                        <table class="table align-items-center mb-0 stat-clean-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Peminjam</th>
                                    <th class="ps-2">Barang</th>
                                    <th class="text-center">Jatuh tempo</th>
                                    <th class="text-center pe-4">Status</th>
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
                                        <td class="ps-4">
                                            <div class="py-1">
                                                <div class="stat-cell-primary">{{ $loan->user?->name ?? '-' }}</div>
                                                <div class="stat-cell-secondary">{{ $loan->user?->email }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="stat-cell-primary">
                                                <a href="{{ route('loans.show', $loan) }}" class="stat-drill-link">
                                                    {{ $loan->item?->name ?? '-' }}
                                                </a>
                                            </div>
                                            <div class="stat-cell-secondary">
                                                <span class="tabular-nums">{{ number_format($loan->quantity, 0, ',', '.') }} unit</span>
                                                @if ($loan->item?->code)
                                                    &bull; <span class="stat-code-tag">{{ $loan->item->code }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="stat-cell-date tabular-nums">
                                                {{ $loan->due_date?->translatedFormat('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center pe-4">
                                            <span class="badge badge-sm bg-gradient-{{ $badgeColor }}">{{ ucfirst($loan->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-sm text-secondary">
                                            Belum ada data peminjaman
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
                                        <span class="mobile-field-value"><span class="stat-code-tag">{{ $loan->item?->code ?? '-' }}</span></span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Jumlah</span>
                                        <span class="mobile-field-value tabular-nums">{{ number_format($loan->quantity, 0, ',', '.') }} unit</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Jatuh Tempo</span>
                                        <span class="mobile-field-value tabular-nums {{ $loan->status === 'terlambat' ? 'text-danger' : '' }}">
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
                                <p class="text-sm text-secondary mb-0">Belum ada data peminjaman</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="stat-section-title mb-0">Aktivitas Terbaru</h6>
                        <p class="stat-section-subtitle mb-0">Jejak audit aktivitas sistem</p>
                    </div>
                    <a href="{{ route('activity-logs.index') }}" class="stat-quiet-action">Semua &rarr;</a>
                </div>
                <div class="card-body p-3">
                    <div class="stat-activity-feed">
                        @forelse ($recentActivities as $log)
                            <div class="stat-activity-item">
                                <span class="stat-activity-dot" aria-hidden="true"></span>
                                <div class="stat-activity-content">
                                    <div class="stat-activity-desc">{{ $log->description }}</div>
                                    <div class="stat-activity-meta tabular-nums">
                                        {{ $log->user?->name ?? 'Sistem' }} &bull; {{ $log->created_at?->translatedFormat('d M Y, H:i') }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-secondary mb-0">Belum ada aktivitas tercatat</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
