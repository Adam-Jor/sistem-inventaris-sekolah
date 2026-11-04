@php
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $maxTrend = max(1, (int) $monthlyTrend->max('requests'));
    $maxItemLoans = max(1, (int) $topItems->max('loans_count'));
    $maxBorrowerLoans = max(1, (int) $topBorrowers->max('loans_count'));
    $maxCategoryLoans = max(1, (int) $topCategories->max('loans_count'));
    $maxLate = max(1, (int) $lateBorrowers->max('late_count'));
    $periodLabel = $periods[$period];
@endphp

<x-app-layout title="Statistik" :breadcrumbs="['Statistik' => null]">
    {{-- Header & Filter Periode --}}
    <div class="card mb-4">
        <div class="card-body p-3 p-md-4 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1 font-weight-bolder">Statistik Inventaris & Peminjaman</h5>
                <p class="text-sm text-secondary mb-0">
                    Analisis barang terpopuler, pengguna paling aktif, dan performa pengembalian
                    @if ($startDate)
                        sejak <strong class="text-dark">{{ $startDate->translatedFormat('d M Y') }}</strong>.
                    @else
                        untuk <strong class="text-dark">seluruh periode</strong>.
                    @endif
                </p>
            </div>

            <nav class="stat-period-switch" aria-label="Filter periode statistik">
                @foreach ($periods as $key => $label)
                    <a
                        href="{{ route('statistics.index', $key === 'all' ? [] : ['periode' => $key]) }}"
                        class="stat-period-link {{ $period === (string) $key ? 'active' : '' }}"
                        @if ($period === (string) $key) aria-current="page" @endif
                    >{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- KPI Utama --}}
    <div class="row g-2 g-sm-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 stat-kpi-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="stat-kpi-label">Total Pengajuan</span>
                        <span class="stat-kpi-icon bg-soft-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect></svg>
                        </span>
                    </div>
                    <h3 class="stat-kpi-value">{{ $fmt($summary['total_requests']) }}</h3>
                    <p class="stat-kpi-hint mb-0">
                        {{ $fmt($summary['approved_count']) }} disetujui
                        @if ($summary['approval_rate'] !== null)
                            &bull; {{ $fmt($summary['approval_rate'], 1) }}%
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card h-100 stat-kpi-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="stat-kpi-label">Unit Dipinjam</span>
                        <span class="stat-kpi-icon bg-soft-info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline></svg>
                        </span>
                    </div>
                    <h3 class="stat-kpi-value">{{ $fmt($summary['units_borrowed']) }}</h3>
                    <p class="stat-kpi-hint mb-0">oleh {{ $fmt($summary['active_borrowers']) }} peminjam</p>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card h-100 stat-kpi-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="stat-kpi-label">Tepat Waktu</span>
                        <span class="stat-kpi-icon bg-soft-success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="9 12 11 14 15 10"></polyline></svg>
                        </span>
                    </div>
                    <h3 class="stat-kpi-value">
                        {{ $summary['on_time_rate'] !== null ? $fmt($summary['on_time_rate'], 1).'%' : '-' }}
                    </h3>
                    <p class="stat-kpi-hint mb-0">
                        {{ $fmt($summary['on_time_count']) }} dari {{ $fmt($summary['returned_count']) }} pengembalian
                    </p>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card h-100 stat-kpi-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span class="stat-kpi-label">Rata-rata Durasi</span>
                        <span class="stat-kpi-icon bg-soft-warning">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </span>
                    </div>
                    <h3 class="stat-kpi-value">
                        {{ $summary['average_duration'] !== null ? $fmt($summary['average_duration'], 1) : '-' }}
                        @if ($summary['average_duration'] !== null)
                            <small class="stat-kpi-unit">hari</small>
                        @endif
                    </h3>
                    <p class="stat-kpi-hint mb-0">lama peminjaman hingga kembali</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tren Bulanan & Distribusi Status --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="mb-0">Tren Peminjaman 6 Bulan Terakhir</h6>
                        <p class="text-xs text-secondary mb-0">Jumlah pengajuan dibandingkan peminjaman yang disetujui</p>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <span class="stat-legend"><span class="stat-legend-dot bg-soft-primary-strong"></span>Pengajuan</span>
                        <span class="stat-legend"><span class="stat-legend-dot bg-primary-gradient"></span>Disetujui</span>
                    </div>
                </div>
                <div class="card-body p-3 pt-3">
                    {{-- Tampilan Tablet & Desktop (>= 576px): Grafik Batang Vertikal dengan Skala Y --}}
                    <div class="stat-chart-wrapper d-none d-sm-grid" role="img" aria-label="Grafik tren peminjaman 6 bulan terakhir">
                        <div class="stat-y-axis" aria-hidden="true">
                            <span>{{ $fmt($maxTrend) }}</span>
                            <span>{{ $fmt((int) round($maxTrend / 2)) }}</span>
                            <span>0</span>
                        </div>
                        <div class="stat-bar-chart">
                            @foreach ($monthlyTrend as $month)
                                @php
                                    $reqPct = round($month['requests'] / $maxTrend * 100, 2);
                                    $appPct = round($month['approved'] / $maxTrend * 100, 2);
                                @endphp
                                <div class="stat-bar-col" title="{{ $month['full_label'] }}: {{ $month['requests'] }} pengajuan, {{ $month['approved'] }} disetujui">
                                    <div class="stat-bar-values">
                                        <span class="stat-val-req">{{ $fmt($month['requests']) }}</span>
                                        <span class="stat-val-sep">/</span>
                                        <span class="stat-val-app">{{ $fmt($month['approved']) }}</span>
                                    </div>
                                    <div class="stat-bar-track">
                                        <div class="stat-bar stat-bar-requests {{ $month['requests'] === 0 ? 'is-zero' : '' }}" style="height: {{ $reqPct }}%"></div>
                                        <div class="stat-bar stat-bar-approved {{ $month['approved'] === 0 ? 'is-zero' : '' }}" style="height: {{ $appPct }}%"></div>
                                    </div>
                                    <span class="stat-bar-label">{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tampilan Mobile (< 576px): Grafik Perbandingan Horizontal per Bulan --}}
                    <div class="stat-trend-mobile d-sm-none" role="list" aria-label="Tren peminjaman 6 bulan terakhir">
                        @foreach ($monthlyTrend as $month)
                            @php
                                $reqPct = round($month['requests'] / $maxTrend * 100, 2);
                                $appPct = round($month['approved'] / $maxTrend * 100, 2);
                            @endphp
                            <div class="stat-trend-mobile-item" role="listitem">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <span class="stat-trend-mobile-month">{{ $month['full_label'] }}</span>
                                    <div class="stat-trend-mobile-counts">
                                        <span class="stat-trend-pill stat-trend-pill-req">{{ $fmt($month['requests']) }} ajuan</span>
                                        <span class="stat-trend-pill stat-trend-pill-app">{{ $fmt($month['approved']) }} disetujui</span>
                                    </div>
                                </div>
                                <div class="stat-trend-mobile-bars">
                                    <div class="stat-trend-mobile-track">
                                        <span class="stat-trend-fill stat-bar-requests {{ $month['requests'] === 0 ? 'is-zero' : '' }}" style="width: {{ $reqPct }}%"></span>
                                    </div>
                                    <div class="stat-trend-mobile-track">
                                        <span class="stat-trend-fill stat-bar-approved {{ $month['approved'] === 0 ? 'is-zero' : '' }}" style="width: {{ $appPct }}%"></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Distribusi Status</h6>
                    <p class="text-xs text-secondary mb-0">Komposisi status peminjaman &bull; {{ $periodLabel }}</p>
                </div>
                <div class="card-body p-3">
                    <div class="stat-stacked-bar mb-3" role="img" aria-label="Komposisi status peminjaman">
                        @foreach ($statusDistribution as $status)
                            @if ($status['count'] > 0)
                                <span class="stat-stacked-segment bg-stat-{{ $status['color'] }}" style="width: {{ $status['percentage'] }}%" title="{{ $status['label'] }}: {{ $status['count'] }}"></span>
                            @endif
                        @endforeach
                    </div>

                    <ul class="stat-legend-list">
                        @foreach ($statusDistribution as $status)
                            <li>
                                <span class="d-flex align-items-center gap-2">
                                    <span class="stat-legend-dot bg-stat-{{ $status['color'] }}"></span>
                                    {{ $status['label'] }}
                                </span>
                                <span class="text-dark font-weight-bold">
                                    {{ $fmt($status['count']) }}
                                    <span class="text-secondary font-weight-normal ms-1">({{ $fmt($status['percentage'], 1) }}%)</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Barang Terpopuler & Pengguna Paling Aktif --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Barang Paling Sering Dipinjam</h6>
                    <p class="text-xs text-secondary mb-0">5 barang teratas berdasarkan jumlah transaksi &bull; {{ $periodLabel }}</p>
                </div>
                <div class="card-body p-3">
                    @forelse ($topItems as $index => $row)
                        <div class="stat-rank-item">
                            <span class="stat-rank-badge rank-{{ $index + 1 }}">{{ $index + 1 }}</span>
                            <div class="stat-rank-body">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <h6 class="stat-rank-title">
                                            @if ($row->item && ! $row->item->trashed())
                                                <a href="{{ route('items.show', $row->item) }}">{{ $row->item->name }}</a>
                                            @else
                                                {{ $row->item?->name ?? 'Barang tidak ditemukan' }}
                                                <span class="text-danger text-xxs">(Dihapus)</span>
                                            @endif
                                        </h6>
                                        <span class="stat-rank-meta">{{ $row->item?->code }} &bull; {{ $row->item?->category?->name ?? '-' }}</span>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="stat-rank-value">{{ $fmt($row->loans_count) }}x</span>
                                        <span class="stat-rank-meta d-block">{{ $fmt($row->units_total) }} unit</span>
                                    </div>
                                </div>
                                <div class="stat-progress"><span style="width: {{ $row->loans_count / $maxItemLoans * 100 }}%"></span></div>
                            </div>
                        </div>
                    @empty
                        <p class="stat-empty">Belum ada barang yang dipinjam pada periode ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Pengguna Paling Aktif</h6>
                    <p class="text-xs text-secondary mb-0">5 peminjam dengan transaksi terbanyak &bull; {{ $periodLabel }}</p>
                </div>
                <div class="card-body p-3">
                    @forelse ($topBorrowers as $index => $row)
                        <div class="stat-rank-item">
                            <span class="stat-rank-avatar">{{ strtoupper(mb_substr($row->user?->name ?? '?', 0, 1)) }}</span>
                            <div class="stat-rank-body">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <h6 class="stat-rank-title">
                                            <span class="text-secondary me-1">#{{ $index + 1 }}</span>{{ $row->user?->name ?? 'Pengguna Terhapus' }}
                                        </h6>
                                        <span class="stat-rank-meta text-truncate d-block">{{ $row->user?->email ?? '-' }}</span>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <span class="stat-rank-value">{{ $fmt($row->loans_count) }}x</span>
                                        <span class="stat-rank-meta d-block">
                                            {{ $fmt($row->units_total) }} unit
                                            @if ((int) $row->late_count > 0)
                                                &bull; <span class="text-danger">{{ $fmt($row->late_count) }} telat</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="stat-progress"><span style="width: {{ $row->loans_count / $maxBorrowerLoans * 100 }}%"></span></div>
                            </div>
                        </div>
                    @empty
                        <p class="stat-empty">Belum ada aktivitas peminjaman pada periode ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Kategori, Keterlambatan, Kondisi Barang --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Kategori Terpopuler</h6>
                    <p class="text-xs text-secondary mb-0">Kategori dengan peminjaman terbanyak</p>
                </div>
                <div class="card-body p-3">
                    @forelse ($topCategories as $row)
                        <div class="stat-simple-row">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <span class="text-sm text-dark font-weight-bold text-truncate min-w-0">{{ $row->name }}</span>
                                <span class="text-xs text-secondary flex-shrink-0">
                                    <strong class="text-dark">{{ $fmt($row->loans_count) }}x</strong> &bull; {{ $fmt($row->units_total) }} unit
                                </span>
                            </div>
                            <div class="stat-progress stat-progress-info"><span style="width: {{ $row->loans_count / $maxCategoryLoans * 100 }}%"></span></div>
                        </div>
                    @empty
                        <p class="stat-empty">Belum ada data kategori yang dipinjam.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Sering Terlambat Mengembalikan</h6>
                    <p class="text-xs text-secondary mb-0">Peminjam dengan keterlambatan terbanyak</p>
                </div>
                <div class="card-body p-3">
                    @forelse ($lateBorrowers as $row)
                        <div class="stat-simple-row">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <span class="text-sm text-dark font-weight-bold text-truncate min-w-0">{{ $row->user?->name ?? 'Pengguna Terhapus' }}</span>
                                <span class="text-xs text-secondary flex-shrink-0">
                                    <strong class="text-danger">{{ $fmt($row->late_count) }}x telat</strong> / {{ $fmt($row->loans_count) }}
                                </span>
                            </div>
                            <div class="stat-progress stat-progress-danger"><span style="width: {{ $row->late_count / $maxLate * 100 }}%"></span></div>
                        </div>
                    @empty
                        <div class="stat-empty stat-empty-success">
                            Tidak ada keterlambatan pengembalian pada periode ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Kondisi Barang Inventaris</h6>
                    <p class="text-xs text-secondary mb-0">Kondisi terkini seluruh barang</p>
                </div>
                <div class="card-body p-3">
                    @foreach ($conditionDistribution as $condition)
                        <div class="stat-simple-row">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <span class="d-flex align-items-center gap-2 text-sm text-dark font-weight-bold min-w-0">
                                    <span class="stat-legend-dot bg-stat-{{ $condition['color'] }}"></span>{{ $condition['label'] }}
                                </span>
                                <span class="text-xs text-secondary flex-shrink-0">
                                    <strong class="text-dark">{{ $fmt($condition['count']) }}</strong> barang ({{ $fmt($condition['percentage'], 1) }}%)
                                </span>
                            </div>
                            <div class="stat-progress stat-progress-{{ $condition['color'] }}"><span style="width: {{ $condition['percentage'] }}%"></span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Stok Menipis & Barang Belum Pernah Dipinjam --}}
    <div class="row g-3 mb-2">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="mb-0">Stok Menipis</h6>
                        <p class="text-xs text-secondary mb-0">Barang dengan stok &le; {{ $lowStockThreshold }} unit</p>
                    </div>
                    <a href="{{ route('items.index') }}" class="text-xs font-weight-bold text-primary flex-shrink-0">Kelola Barang</a>
                </div>
                <div class="card-body p-3">
                    @forelse ($lowStockItems as $item)
                        <div class="stat-list-row">
                            <div class="min-w-0">
                                <a href="{{ route('items.show', $item) }}" class="text-sm text-dark font-weight-bold d-block text-truncate">{{ $item->name }}</a>
                                <span class="text-xs text-secondary">{{ $item->code }} &bull; {{ $item->category?->name ?? '-' }}</span>
                            </div>
                            <span class="badge badge-sm bg-gradient-{{ $item->stock === 0 ? 'danger' : 'warning' }} flex-shrink-0">
                                {{ $item->stock === 0 ? 'Habis' : $fmt($item->stock).' unit' }}
                            </span>
                        </div>
                    @empty
                        <div class="stat-empty stat-empty-success">Semua stok barang dalam jumlah aman.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Barang Belum Pernah Dipinjam</h6>
                    <p class="text-xs text-secondary mb-0">
                        {{ $fmt($idleItems['count']) }} barang tidak dipinjam &bull; {{ $periodLabel }}
                    </p>
                </div>
                <div class="card-body p-3">
                    @forelse ($idleItems['items'] as $item)
                        <div class="stat-list-row">
                            <div class="min-w-0">
                                <a href="{{ route('items.show', $item) }}" class="text-sm text-dark font-weight-bold d-block text-truncate">{{ $item->name }}</a>
                                <span class="text-xs text-secondary">{{ $item->code }} &bull; {{ $item->category?->name ?? '-' }}</span>
                            </div>
                            <span class="badge badge-sm bg-gradient-secondary flex-shrink-0">Stok {{ $fmt($item->stock) }}</span>
                        </div>
                    @empty
                        <div class="stat-empty stat-empty-success">Semua barang pernah dipinjam pada periode ini.</div>
                    @endforelse

                    @if ($idleItems['count'] > $idleItems['items']->count())
                        <p class="text-xs text-secondary mb-0 mt-2">
                            + {{ $fmt($idleItems['count'] - $idleItems['items']->count()) }} barang lainnya
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

