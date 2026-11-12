@props([
    'monthlyTrend',
    'selectedMonth' => null,
    'trendRange' => null,
    'interactive' => true,
])

@php
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $rawMax = (int) $monthlyTrend->max('requests');
    // Bulatkan ke bilangan genap terdekat (minimal 2) agar nilai tengah (50%) selalu bilangan bulat berbeda
    $maxTrend = max(2, (int) ceil($rawMax / 2) * 2);
    $colCount = max(1, $monthlyTrend->count());
    $isWideChart = $colCount > 6;
    $desktopVisibility = $isWideChart ? 'd-none d-md-grid' : 'd-none d-sm-grid';
    $mobileVisibility = $isWideChart ? 'd-md-none' : 'd-sm-none';
    $distinctYears = $monthlyTrend->pluck('short_year')->filter()->unique()->count();
@endphp

{{-- Tampilan Tablet & Desktop: Grafik Batang Vertikal dengan Skala Y --}}
<div class="stat-chart-wrapper {{ $desktopVisibility }}" role="img" aria-label="Grafik tren peminjaman bulanan">
    <div class="stat-y-axis tabular-nums" aria-hidden="true">
        <span>{{ $fmt($maxTrend) }}</span>
        <span>{{ $fmt((int) ($maxTrend / 2)) }}</span>
        <span>0</span>
    </div>
    <div class="stat-bar-chart {{ $isWideChart ? 'is-12-cols' : 'is-6-cols' }}" style="--chart-cols: {{ $colCount }}">
        @foreach ($monthlyTrend as $month)
            @php
                $reqPct = round($month['requests'] / $maxTrend * 100, 2);
                $appPct = round($month['approved'] / $maxTrend * 100, 2);
                $monthKey = $month['key'] ?? null;
                $isSelected = $selectedMonth !== null && $monthKey === $selectedMonth;
                $isCurrent = ($month['is_current'] ?? $loop->last) && $selectedMonth === null;

                $barUrl = null;
                if ($interactive && $monthKey) {
                    $queryParams = array_filter([
                        'month' => $isSelected ? null : $monthKey,
                        'trend' => $trendRange,
                    ], fn ($v) => $v !== null && $v !== '');
                    $barUrl = route('statistics.index', $queryParams);
                }

                $showYearSuffix = $distinctYears > 1 && ($loop->first || $month['label'] === 'Jan');
            @endphp
            @if ($barUrl)
                <a
                    href="{{ $barUrl }}"
                    class="stat-bar-col stat-bar-col-interactive {{ $isSelected ? 'is-selected' : '' }} {{ $isCurrent ? 'is-current' : '' }}"
                    title="{{ $month['full_label'] }}: {{ $month['requests'] }} pengajuan, {{ $month['approved'] }} disetujui ({{ $isSelected ? 'Klik untuk menampilkan semua periode' : 'Klik untuk memfilter bulan ini' }})"
                >
                    <div class="stat-bar-values tabular-nums">
                        <span class="stat-val-req">{{ $fmt($month['requests']) }}</span>
                        <span class="stat-val-sep">/</span>
                        <span class="stat-val-app">{{ $fmt($month['approved']) }}</span>
                    </div>
                    <div class="stat-bar-track">
                        <div class="stat-bar stat-bar-requests {{ $month['requests'] === 0 ? 'is-zero' : '' }}" style="height: {{ $reqPct }}%"></div>
                        <div class="stat-bar stat-bar-approved {{ $month['approved'] === 0 ? 'is-zero' : '' }}" style="height: {{ $appPct }}%"></div>
                    </div>
                    <span class="stat-bar-label">
                        {{ $month['label'] }}@if ($showYearSuffix) '{{ $month['short_year'] }}@endif
                    </span>
                </a>
            @else
                <div class="stat-bar-col {{ $isCurrent ? 'is-current' : '' }}" title="{{ $month['full_label'] }}: {{ $month['requests'] }} pengajuan, {{ $month['approved'] }} disetujui">
                    <div class="stat-bar-values tabular-nums">
                        <span class="stat-val-req">{{ $fmt($month['requests']) }}</span>
                        <span class="stat-val-sep">/</span>
                        <span class="stat-val-app">{{ $fmt($month['approved']) }}</span>
                    </div>
                    <div class="stat-bar-track">
                        <div class="stat-bar stat-bar-requests {{ $month['requests'] === 0 ? 'is-zero' : '' }}" style="height: {{ $reqPct }}%"></div>
                        <div class="stat-bar stat-bar-approved {{ $month['approved'] === 0 ? 'is-zero' : '' }}" style="height: {{ $appPct }}%"></div>
                    </div>
                    <span class="stat-bar-label">
                        {{ $month['label'] }}@if ($showYearSuffix) '{{ $month['short_year'] }}@endif
                    </span>
                </div>
            @endif
        @endforeach
    </div>
</div>

{{-- Tampilan Mobile: Grafik Perbandingan Horizontal per Bulan --}}
<div class="stat-trend-mobile {{ $mobileVisibility }}" role="list" aria-label="Tren peminjaman bulanan">
    @foreach ($monthlyTrend as $month)
        @php
            $reqPct = round($month['requests'] / $maxTrend * 100, 2);
            $appPct = round($month['approved'] / $maxTrend * 100, 2);
            $monthKey = $month['key'] ?? null;
            $isSelected = $selectedMonth !== null && $monthKey === $selectedMonth;

            $barUrl = null;
            if ($interactive && $monthKey) {
                $queryParams = array_filter([
                    'month' => $isSelected ? null : $monthKey,
                    'trend' => $trendRange,
                ], fn ($v) => $v !== null && $v !== '');
                $barUrl = route('statistics.index', $queryParams);
            }
        @endphp
        @if ($barUrl)
            <a href="{{ $barUrl }}" class="stat-trend-mobile-item stat-trend-mobile-link {{ $isSelected ? 'is-selected' : '' }}" role="listitem">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <span class="stat-trend-mobile-month">{{ $month['full_label'] }}</span>
                    <div class="stat-trend-mobile-counts tabular-nums">
                        <span class="stat-val-req">{{ $fmt($month['requests']) }} ajuan</span>
                        <span class="stat-val-sep">&bull;</span>
                        <span class="stat-val-app">{{ $fmt($month['approved']) }} disetujui</span>
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
            </a>
        @else
            <div class="stat-trend-mobile-item" role="listitem">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                    <span class="stat-trend-mobile-month">{{ $month['full_label'] }}</span>
                    <div class="stat-trend-mobile-counts tabular-nums">
                        <span class="stat-val-req">{{ $fmt($month['requests']) }} ajuan</span>
                        <span class="stat-val-sep">&bull;</span>
                        <span class="stat-val-app">{{ $fmt($month['approved']) }} disetujui</span>
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
        @endif
    @endforeach
</div>

