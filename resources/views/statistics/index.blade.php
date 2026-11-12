@php
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $maxItemLoans = max(1, (int) $topItems->max('loans_count'));
    $maxBorrowerLoans = max(1, (int) $topBorrowers->max('loans_count'));
    $maxCategoryLoans = max(1, (int) $topCategories->max('loans_count'));
    $maxLate = max(1, (int) $lateBorrowers->max('late_count'));
    $periodLabel = $periodLabel ?? ($periods[$period] ?? 'Semua Waktu');
    $selectedMonth = $selectedMonth ?? null;
    $selectedMonthLabel = $selectedMonthLabel ?? null;
    $selectedMonthWeeks = $selectedMonthWeeks ?? collect();
    $availableMonths = $availableMonths ?? collect();
    $trendRange = $trendRange ?? '12m';
    $trendOptions = $trendOptions ?? ['6m' => '6 Bulan', '12m' => '12 Bulan'];
    $trendTitle = $trendTitle ?? 'Tren Peminjaman 12 Bulan Terakhir';
    $trendParam = $trendRange !== '12m' ? ['trend' => $trendRange] : [];
    $hasActiveFilter = $selectedMonth !== null || $period !== 'all' || $trendRange !== '12m';
@endphp

<x-app-layout title="Statistik" :breadcrumbs="['Statistik' => null]">
    {{-- Header & Tombol Filter Popover --}}
    <div class="card mb-4 stat-surface-card stat-header-card">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="stat-live-dot" aria-hidden="true"></span>
                    <span class="stat-eyebrow">Laporan Analitik &bull; {{ $periodLabel }}</span>
                    @if ($selectedMonth)
                        <a href="{{ route('statistics.index', $trendParam) }}" class="stat-active-month-reset stat-ajax-link">
                            &times; Reset bulan
                        </a>
                    @endif
                </div>
                <h5 class="stat-page-title mb-1">Statistik Inventaris & Peminjaman</h5>
                <p class="stat-page-subtitle mb-0">
                    @if ($selectedMonth && $startDate && $endDate)
                        Menampilkan analisis khusus bulan <strong class="text-dark">{{ $selectedMonthLabel }}</strong>
                        (<span class="tabular-nums">{{ $startDate->translatedFormat('j M') }} &ndash; {{ $endDate->translatedFormat('j M Y') }}</span>)
                    @elseif ($startDate)
                        Analisis barang terpopuler, pengguna paling aktif, dan performa pengembalian sejak <strong class="text-dark">{{ $startDate->translatedFormat('d M Y') }}</strong>
                    @else
                        Analisis barang terpopuler, pengguna paling aktif, dan performa pengembalian untuk <strong class="text-dark">seluruh periode</strong>
                    @endif
                </p>
            </div>

            {{-- Tombol Filter dengan Ikon Filter (16px, stroke 1.5) & Popover --}}
            <div
                class="stat-filter-dropdown-wrap flex-shrink-0 align-self-start align-self-md-center"
                x-data="{ filterOpen: false }"
                @click.outside="filterOpen = false"
                @keydown.escape.window="filterOpen = false"
            >
                <div class="d-flex align-items-center gap-2">
                    @if ($hasActiveFilter)
                        <a
                            href="{{ route('statistics.index') }}"
                            class="stat-filter-reset-inline stat-ajax-link"
                            title="Reset semua filter ke default"
                        >
                            Reset
                        </a>
                    @endif

                    <button
                        type="button"
                        class="stat-filter-trigger {{ $hasActiveFilter ? 'is-active' : '' }}"
                        @click="filterOpen = !filterOpen"
                        :aria-expanded="filterOpen.toString()"
                        aria-haspopup="true"
                    >
                        <svg class="stat-filter-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        <span>Filter</span>
                        @if ($hasActiveFilter)
                            <span class="stat-filter-meta tabular-nums">&bull; {{ $periodLabel }}</span>
                        @endif
                    </button>
                </div>

                {{-- Panel Popover Filter Utama --}}
                <div
                    class="stat-filter-popover"
                    x-show="filterOpen"
                    x-transition.opacity.duration.150ms
                    x-cloak
                    role="dialog"
                    aria-label="Filter laporan statistik"
                >
                    <div class="stat-filter-popover-header">
                        <span class="stat-filter-popover-title">Filter Statistik</span>
                        @if ($hasActiveFilter)
                            <a
                                href="{{ route('statistics.index') }}"
                                class="stat-filter-popover-reset stat-ajax-link"
                                @click="filterOpen = false"
                            >Reset Semua</a>
                        @endif
                    </div>

                    {{-- 1. Periode Statistik --}}
                    <div class="stat-filter-group">
                        <label class="stat-filter-label">Rentang Periode</label>
                        <div class="stat-filter-pill-grid">
                            @foreach ($periods as $key => $label)
                                <a
                                    href="{{ route('statistics.index', ($key === 'all' ? [] : ['period' => $key]) + $trendParam) }}"
                                    class="stat-filter-option stat-period-link stat-ajax-link {{ $selectedMonth === null && $period === (string) $key ? 'active' : '' }}"
                                    @click="filterOpen = false"
                                >{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    {{-- 2. Pilih Bulan Spesifik --}}
                    <div class="stat-filter-group">
                        <label for="stat-header-month-select" class="stat-filter-label">Fokus Bulan Tertentu</label>
                        <form method="GET" action="{{ route('statistics.index') }}" class="m-0">
                            @if ($trendRange !== '12m')
                                <input type="hidden" name="trend" value="{{ $trendRange }}">
                            @endif
                            <select
                                id="stat-header-month-select"
                                name="month"
                                data-ajax-select
                                @change="filterOpen = false"
                                class="form-select form-select-sm stat-popover-select {{ $selectedMonth ? 'is-active' : '' }}"
                            >
                                <option value="">Semua Bulan (Ikuti Periode)</option>
                                @foreach ($availableMonths as $m)
                                    <option value="{{ $m['key'] }}" @selected($selectedMonth === $m['key'])>
                                        {{ $m['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>

                    {{-- 3. Rentang Grafik Tren --}}
                    <div class="stat-filter-group mb-0">
                        <label class="stat-filter-label">Rentang Grafik Tren</label>
                        <div class="stat-filter-pill-grid">
                            @foreach ($trendOptions as $tKey => $tLabel)
                                @php
                                    $tParams = array_filter([
                                        'period' => $selectedMonth === null && $period !== 'all' ? $period : null,
                                        'month' => $selectedMonth,
                                        'trend' => $tKey === '12m' ? null : $tKey,
                                    ], fn ($v) => $v !== null && $v !== '');
                                @endphp
                                <a
                                    href="{{ route('statistics.index', $tParams) }}"
                                    class="stat-filter-option stat-chart-range-link stat-ajax-link {{ $trendRange === $tKey ? 'active' : '' }}"
                                    @click="filterOpen = false"
                                >{{ $tLabel }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Baris 1: 4 Kartu KPI Utama (Angka Tabular, Tanpa Kotak Ikon Dekoratif) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 stat-kpi-card">
                <div class="card-body">
                    <span class="stat-kpi-label">Total Pengajuan</span>
                    <div class="stat-kpi-value">{{ $fmt($summary['total_requests']) }}</div>
                    <p class="stat-kpi-hint mb-0 tabular-nums">
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
                <div class="card-body">
                    <span class="stat-kpi-label">Unit Dipinjam</span>
                    <div class="stat-kpi-value">{{ $fmt($summary['units_borrowed']) }}</div>
                    <p class="stat-kpi-hint mb-0 tabular-nums">oleh {{ $fmt($summary['active_borrowers']) }} peminjam</p>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card h-100 stat-kpi-card">
                <div class="card-body">
                    <span class="stat-kpi-label">Tepat Waktu</span>
                    <div class="stat-kpi-value">
                        {{ $summary['on_time_rate'] !== null ? $fmt($summary['on_time_rate'], 1).'%' : '-' }}
                    </div>
                    <p class="stat-kpi-hint mb-0 tabular-nums">
                        {{ $fmt($summary['on_time_count']) }} dari {{ $fmt($summary['returned_count']) }} pengembalian
                    </p>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card h-100 stat-kpi-card">
                <div class="card-body">
                    <span class="stat-kpi-label">Rata-rata Durasi</span>
                    <div class="stat-kpi-value">
                        {{ $summary['average_duration'] !== null ? $fmt($summary['average_duration'], 1) : '-' }}
                        @if ($summary['average_duration'] !== null)
                            <small class="stat-kpi-unit">hari</small>
                        @endif
                    </div>
                    <p class="stat-kpi-hint mb-0">Lama peminjaman hingga kembali</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Baris 2: Tren Peminjaman Bulanan (Full Width 12 Kolom) --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card stat-surface-card stat-trend-card">
                <div class="card-header pb-0 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                    <div>
                        <h6 class="stat-section-title mb-0">{{ $trendTitle }}</h6>
                        <p class="stat-section-subtitle mb-0">
                            Klik batang bulan untuk melihat rincian statistik pada bulan tersebut
                        </p>
                    </div>
                    <div class="d-flex align-items-center justify-content-between justify-content-sm-end gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="stat-legend"><span class="stat-legend-dot bg-soft-primary-strong"></span>Pengajuan</span>
                            <span class="stat-legend"><span class="stat-legend-dot bg-primary-gradient"></span>Disetujui</span>
                        </div>

                        {{-- Tombol Ikon Filter Rentang Grafik Tren (16px, stroke 1.5) --}}
                        <div
                            class="stat-filter-dropdown-wrap"
                            x-data="{ chartFilterOpen: false }"
                            @click.outside="chartFilterOpen = false"
                            @keydown.escape.window="chartFilterOpen = false"
                        >
                            <button
                                type="button"
                                class="stat-icon-filter-btn {{ $trendRange !== '12m' || $selectedMonth !== null ? 'is-active' : '' }}"
                                @click="chartFilterOpen = !chartFilterOpen"
                                :aria-expanded="chartFilterOpen.toString()"
                                aria-label="Filter rentang grafik tren"
                                title="Filter rentang grafik tren ({{ $trendOptions[$trendRange] ?? '12 Bulan' }})"
                            >
                                <svg class="stat-filter-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                                </svg>
                                <span class="stat-icon-filter-text">{{ $trendOptions[$trendRange] ?? '12 Bulan' }}</span>
                            </button>

                            <div
                                class="stat-filter-popover stat-filter-popover-sm"
                                x-show="chartFilterOpen"
                                x-transition.opacity.duration.150ms
                                x-cloak
                                role="dialog"
                                aria-label="Filter rentang grafik tren"
                            >
                                <div class="stat-filter-popover-header">
                                    <span class="stat-filter-popover-title">Rentang Grafik Tren</span>
                                </div>
                                <div class="stat-filter-pill-grid mb-3">
                                    @foreach ($trendOptions as $tKey => $tLabel)
                                        @php
                                            $tParams = array_filter([
                                                'period' => $selectedMonth === null && $period !== 'all' ? $period : null,
                                                'month' => $selectedMonth,
                                                'trend' => $tKey === '12m' ? null : $tKey,
                                            ], fn ($v) => $v !== null && $v !== '');
                                        @endphp
                                        <a
                                            href="{{ route('statistics.index', $tParams) }}"
                                            class="stat-filter-option stat-chart-range-link stat-ajax-link {{ $trendRange === $tKey ? 'active' : '' }}"
                                            @click="chartFilterOpen = false"
                                        >{{ $tLabel }}</a>
                                    @endforeach
                                </div>

                                <div class="stat-filter-group mb-0 pt-2 border-top">
                                    <label for="stat-chart-month-select" class="stat-filter-label">Loncat ke Bulan</label>
                                    <form method="GET" action="{{ route('statistics.index') }}" class="m-0">
                                        @if ($trendRange !== '12m')
                                            <input type="hidden" name="trend" value="{{ $trendRange }}">
                                        @endif
                                        <select
                                            id="stat-chart-month-select"
                                            name="month"
                                            data-ajax-select
                                            @change="chartFilterOpen = false"
                                            class="form-select form-select-sm stat-popover-select {{ $selectedMonth ? 'is-active' : '' }}"
                                        >
                                            <option value="">Semua Bulan</option>
                                            @foreach ($availableMonths as $m)
                                                <option value="{{ $m['key'] }}" @selected($selectedMonth === $m['key'])>
                                                    {{ $m['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <x-monthly-trend-chart
                        :monthlyTrend="$monthlyTrend"
                        :selectedMonth="$selectedMonth"
                        :trendRange="$trendRange !== '12m' ? $trendRange : null"
                    />

                    @if ($selectedMonth && $selectedMonthWeeks->isNotEmpty())
                        <div class="stat-month-drilldown mt-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <div>
                                    <span class="stat-drilldown-title">Rincian Per Minggu &bull; {{ $selectedMonthLabel }}</span>
                                    <span class="stat-drilldown-subtitle ms-1">Distribusi aktivitas di dalam bulan terpilih</span>
                                </div>
                                <a href="{{ route('statistics.index', $trendParam) }}" class="stat-quiet-action stat-ajax-link">
                                    &times; Tutup rincian bulan
                                </a>
                            </div>
                            <div class="stat-week-grid">
                                @foreach ($selectedMonthWeeks as $week)
                                    <div class="stat-week-card">
                                        <div class="d-flex align-items-baseline justify-content-between gap-1">
                                            <span class="stat-week-label">{{ $week['label'] }}</span>
                                            <span class="stat-week-dates tabular-nums">{{ $week['date_range'] }}</span>
                                        </div>
                                        <div class="stat-week-figures tabular-nums mt-1">
                                            <span class="stat-week-approved">{{ $fmt($week['approved']) }} disetujui</span>
                                            <span class="stat-week-req">{{ $fmt($week['requests']) }} ajuan &bull; {{ $fmt($week['units']) }} unit</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Baris 3: Distribusi Status & Kondisi Barang Inventaris (Grafik Sunburst 2-Ring di Bawah Tren Peminjaman) --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="stat-section-title mb-0">Distribusi Status Peminjaman</h6>
                        <p class="stat-section-subtitle mb-0">Hirarki persetujuan & status peminjaman &bull; {{ $periodLabel }}</p>
                    </div>
                    <a href="{{ route('loans.index') }}" class="stat-quiet-action flex-shrink-0">Semua Data &rarr;</a>
                </div>
                <div class="card-body">
                    <x-sunburst-chart
                        :groups="$statusSunburst['groups']"
                        :total="$statusSunburst['total']"
                        centerLabel="Pengajuan"
                        unitLabel="ajuan"
                        ariaLabel="Grafik Sunburst distribusi status peminjaman"
                    />
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="stat-section-title mb-0">Kondisi Barang Inventaris</h6>
                        <p class="stat-section-subtitle mb-0">Hirarki kondisi fisik & ketersediaan stok barang</p>
                    </div>
                    <a href="{{ route('items.index') }}" class="stat-quiet-action flex-shrink-0">Data Barang &rarr;</a>
                </div>
                <div class="card-body">
                    <x-sunburst-chart
                        :groups="$conditionSunburst['groups']"
                        :total="$conditionSunburst['total']"
                        centerLabel="Barang"
                        unitLabel="barang"
                        ariaLabel="Grafik Sunburst kondisi dan ketersediaan barang inventaris"
                    />
                </div>
            </div>
        </div>
    </div>

    {{-- Baris 4 (Proporsi Asimetris 7 : 5): Barang Terpopuler & Kategori Terpopuler --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-0">
                    <h6 class="stat-section-title mb-0">Barang Paling Sering Dipinjam</h6>
                    <p class="stat-section-subtitle mb-0">5 barang teratas berdasarkan jumlah transaksi &bull; {{ $periodLabel }}</p>
                </div>
                <div class="card-body">
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
                                        <span class="stat-rank-meta">
                                            @if ($row->item?->code)
                                                <span class="stat-code-tag">{{ $row->item->code }}</span> &bull;
                                            @endif
                                            {{ $row->item?->category?->name ?? '-' }}
                                        </span>
                                    </div>
                                    <div class="text-end flex-shrink-0 tabular-nums">
                                        <span class="stat-rank-value">{{ $fmt($row->loans_count) }}x</span>
                                        <span class="stat-rank-meta d-block">{{ $fmt($row->units_total) }} unit</span>
                                    </div>
                                </div>
                                <div class="stat-progress"><span style="width: {{ $row->loans_count / $maxItemLoans * 100 }}%"></span></div>
                            </div>
                        </div>
                    @empty
                        <p class="stat-empty">Belum ada barang yang dipinjam pada periode ini</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-0">
                    <h6 class="stat-section-title mb-0">Kategori Terpopuler</h6>
                    <p class="stat-section-subtitle mb-0">Kategori dengan peminjaman terbanyak &bull; {{ $periodLabel }}</p>
                </div>
                <div class="card-body">
                    @forelse ($topCategories as $row)
                        <div class="stat-simple-row">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                <a href="{{ route('items.index', ['category_id' => $row->id]) }}" class="stat-row-label text-truncate min-w-0 stat-drill-link" title="Lihat barang kategori {{ $row->name }}">{{ $row->name }}</a>
                                <span class="stat-row-meta tabular-nums flex-shrink-0">
                                    <strong class="stat-figure-inline">{{ $fmt($row->loans_count) }}x</strong> &bull; {{ $fmt($row->units_total) }} unit
                                </span>
                            </div>
                            <div class="stat-progress"><span style="width: {{ $row->loans_count / $maxCategoryLoans * 100 }}%"></span></div>
                        </div>
                    @empty
                        <p class="stat-empty">Belum ada data kategori yang dipinjam</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Baris 5 (Proporsi Asimetris 7 : 5): Pengguna Paling Aktif & Sering Terlambat Mengembalikan --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-0">
                    <h6 class="stat-section-title mb-0">Pengguna Paling Aktif</h6>
                    <p class="stat-section-subtitle mb-0">5 peminjam dengan transaksi terbanyak &bull; {{ $periodLabel }}</p>
                </div>
                <div class="card-body">
                    @forelse ($topBorrowers as $index => $row)
                        <div class="stat-rank-item">
                            <span class="stat-rank-avatar {{ $index === 0 ? 'is-top' : '' }}">{{ strtoupper(mb_substr($row->user?->name ?? '?', 0, 1)) }}</span>
                            <div class="stat-rank-body">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0">
                                        <h6 class="stat-rank-title">
                                            <span class="stat-rank-num tabular-nums me-1">#{{ $index + 1 }}</span>
                                            @if ($row->user)
                                                <a href="{{ route('loans.index', ['search' => $row->user->email]) }}" title="Lihat riwayat peminjaman {{ $row->user->name }}">{{ $row->user->name }}</a>
                                            @else
                                                Pengguna Terhapus
                                            @endif
                                        </h6>
                                        <span class="stat-rank-meta text-truncate d-block">{{ $row->user?->email ?? '-' }}</span>
                                    </div>
                                    <div class="text-end flex-shrink-0 tabular-nums">
                                        <span class="stat-rank-value">{{ $fmt($row->loans_count) }}x</span>
                                        <span class="stat-rank-meta d-block">
                                            {{ $fmt($row->units_total) }} unit
                                            @if ((int) $row->late_count > 0)
                                                &bull; <span class="text-semantic-danger">{{ $fmt($row->late_count) }} telat</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="stat-progress"><span style="width: {{ $row->loans_count / $maxBorrowerLoans * 100 }}%"></span></div>
                            </div>
                        </div>
                    @empty
                        <p class="stat-empty">Belum ada aktivitas peminjaman pada periode ini</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100 stat-surface-card">
                <div class="card-header pb-0">
                    <h6 class="stat-section-title mb-0">Sering Terlambat Mengembalikan</h6>
                    <p class="stat-section-subtitle mb-0">Peminjam dengan keterlambatan terbanyak &bull; {{ $periodLabel }}</p>
                </div>
                <div class="card-body">
                    @forelse ($lateBorrowers as $row)
                        <div class="stat-simple-row">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                @if ($row->user)
                                    <a href="{{ route('loans.index', ['search' => $row->user->email]) }}" class="stat-row-label text-truncate min-w-0 stat-drill-link" title="Lihat riwayat peminjaman {{ $row->user->name }}">{{ $row->user->name }}</a>
                                @else
                                    <span class="stat-row-label text-truncate min-w-0">Pengguna Terhapus</span>
                                @endif
                                <span class="stat-row-meta tabular-nums flex-shrink-0">
                                    <strong class="text-semantic-danger">{{ $fmt($row->late_count) }}x telat</strong> / {{ $fmt($row->loans_count) }}
                                </span>
                            </div>
                            <div class="stat-progress stat-progress-danger"><span style="width: {{ $row->late_count / $maxLate * 100 }}%"></span></div>
                        </div>
                    @empty
                        <div class="stat-empty stat-empty-success">
                            Tidak ada keterlambatan pengembalian pada periode ini
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Baris 6 (Panel Terpadu 12 Kolom): Stok Menipis & Barang Belum Pernah Dipinjam --}}
    <div class="row g-3 mb-2">
        <div class="col-12">
            <div class="card stat-surface-card">
                <div class="card-body">
                    <div class="stat-split-grid">
                        <div class="stat-split-pane">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="stat-section-title mb-0">Stok Menipis</h6>
                                    <p class="stat-section-subtitle mb-0">Barang dengan stok &le; {{ $lowStockThreshold }} unit</p>
                                </div>
                                <a href="{{ route('items.index', ['availability' => 'low']) }}" class="stat-quiet-action flex-shrink-0">Kelola Barang &rarr;</a>
                            </div>
                            @forelse ($lowStockItems as $item)
                                <div class="stat-list-row">
                                    <div class="min-w-0">
                                        <a href="{{ route('items.show', $item) }}" class="stat-row-label d-block text-truncate stat-drill-link">{{ $item->name }}</a>
                                        <span class="stat-rank-meta">
                                            <span class="stat-code-tag">{{ $item->code }}</span> &bull; {{ $item->category?->name ?? '-' }}
                                        </span>
                                    </div>
                                    <span class="stat-stock-tag {{ $item->stock === 0 ? 'is-out' : 'is-low' }} flex-shrink-0 tabular-nums">
                                        {{ $item->stock === 0 ? 'Habis' : 'Sisa '.$fmt($item->stock).' unit' }}
                                    </span>
                                </div>
                            @empty
                                <div class="stat-empty stat-empty-success">Semua stok barang dalam jumlah aman</div>
                            @endforelse
                        </div>

                        <div class="stat-split-pane">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="stat-section-title mb-0">Barang Belum Pernah Dipinjam</h6>
                                    <p class="stat-section-subtitle mb-0 tabular-nums">
                                        {{ $fmt($idleItems['count']) }} barang tidak dipinjam &bull; {{ $periodLabel }}
                                    </p>
                                </div>
                                <a href="{{ route('items.index') }}" class="stat-quiet-action flex-shrink-0">Daftar Aset &rarr;</a>
                            </div>
                            @forelse ($idleItems['items'] as $item)
                                <div class="stat-list-row">
                                    <div class="min-w-0">
                                        <a href="{{ route('items.show', $item) }}" class="stat-row-label d-block text-truncate stat-drill-link">{{ $item->name }}</a>
                                        <span class="stat-rank-meta">
                                            <span class="stat-code-tag">{{ $item->code }}</span> &bull; {{ $item->category?->name ?? '-' }}
                                        </span>
                                    </div>
                                    <span class="stat-quiet-count tabular-nums flex-shrink-0">Stok {{ $fmt($item->stock) }}</span>
                                </div>
                            @empty
                                <div class="stat-empty stat-empty-success">Semua barang pernah dipinjam pada periode ini</div>
                            @endforelse

                            @if ($idleItems['count'] > $idleItems['items']->count())
                                <p class="stat-row-meta tabular-nums mb-0 mt-2">
                                    + {{ $fmt($idleItems['count'] - $idleItems['items']->count()) }} barang lainnya
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
