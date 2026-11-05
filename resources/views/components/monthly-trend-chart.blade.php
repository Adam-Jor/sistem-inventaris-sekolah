@props([
    'monthlyTrend',
])

@php
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $rawMax = (int) $monthlyTrend->max('requests');
    // Bulatkan ke bilangan genap terdekat (minimal 2) agar nilai tengah (50%) selalu bilangan bulat berbeda
    $maxTrend = max(2, (int) ceil($rawMax / 2) * 2);
@endphp

{{-- Tampilan Tablet & Desktop (>= 576px): Grafik Batang Vertikal dengan Skala Y --}}
<div class="stat-chart-wrapper d-none d-sm-grid" role="img" aria-label="Grafik tren peminjaman 6 bulan terakhir">
    <div class="stat-y-axis" aria-hidden="true">
        <span>{{ $fmt($maxTrend) }}</span>
        <span>{{ $fmt((int) ($maxTrend / 2)) }}</span>
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

