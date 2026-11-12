@props([
    'groups' => [],
    'total' => 0,
    'centerLabel' => 'Total',
    'unitLabel' => '',
    'ariaLabel' => 'Grafik Sunburst',
])

@php
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
    $totalCount = max(0, (int) $total);
    $safeDenom = max(1, $totalCount);

    $cx = 110;
    $cy = 110;
    // Ring 1 (Inner - Kelompok Utama) & Ring 2 (Outer - Rincian Sub-Kategori)
    $rInnerStart = 48;
    $rInnerEnd = 74;
    $rOuterStart = 78;
    $rOuterEnd = 104;

    $polarToCartesian = function (float $centerX, float $centerY, float $radius, float $angleInDegrees): array {
        $angleInRadians = ($angleInDegrees - 90.0) * M_PI / 180.0;

        return [
            round($centerX + ($radius * cos($angleInRadians)), 3),
            round($centerY + ($radius * sin($angleInRadians)), 3),
        ];
    };

    $describeAnnularSector = function (
        float $startAngle,
        float $endAngle,
        float $innerRadius,
        float $outerRadius
    ) use ($cx, $cy, $polarToCartesian): string {
        $sweep = max(0.0, $endAngle - $startAngle);
        if ($sweep <= 0.01) {
            return '';
        }

        // Cegah arc 360 derajat penuh kolaps pada titik awal/akhir yang identik di SVG
        if ($sweep >= 359.98) {
            $endAngle = $startAngle + 359.98;
            $sweep = 359.98;
        }

        [$x1, $y1] = $polarToCartesian($cx, $cy, $outerRadius, $startAngle);
        [$x2, $y2] = $polarToCartesian($cx, $cy, $outerRadius, $endAngle);
        [$x3, $y3] = $polarToCartesian($cx, $cy, $innerRadius, $endAngle);
        [$x4, $y4] = $polarToCartesian($cx, $cy, $innerRadius, $startAngle);

        $largeArcFlag = $sweep > 180.0 ? 1 : 0;

        return implode(' ', [
            "M {$x1} {$y1}",
            "A {$outerRadius} {$outerRadius} 0 {$largeArcFlag} 1 {$x2} {$y2}",
            "L {$x3} {$y3}",
            "A {$innerRadius} {$innerRadius} 0 {$largeArcFlag} 0 {$x4} {$y4}",
            'Z',
        ]);
    };

    $innerArcs = [];
    $outerArcs = [];
    $legendItems = [];
    $currentAngle = 0.0;

    foreach ($groups as $gIndex => $group) {
        $groupCount = (int) ($group['count'] ?? 0);
        $groupPct = round($groupCount / $safeDenom * 100, 1);
        $groupSweep = $totalCount > 0 ? ($groupCount / $totalCount) * 360.0 : 0.0;
        $groupStart = $currentAngle;
        $groupEnd = $currentAngle + $groupSweep;
        $groupId = 'inner-'.$gIndex;

        if ($groupCount > 0) {
            $innerArcs[] = [
                'id' => $groupId,
                'label' => $group['label'],
                'sublabel' => $group['sublabel'] ?? 'Kelompok Utama',
                'count' => $groupCount,
                'formatted' => $fmt($groupCount),
                'percentage' => $fmt($groupPct, 1).'%',
                'color' => $group['color'] ?? '#2563eb',
                'url' => $group['url'] ?? null,
                'path' => $describeAnnularSector($groupStart, $groupEnd, $rInnerStart, $rInnerEnd),
            ];
        }

        $children = $group['children'] ?? [];
        $childAngle = $groupStart;
        $groupSafeDenom = max(1, $groupCount);

        foreach ($children as $cIndex => $child) {
            $childCount = (int) ($child['count'] ?? 0);
            $childPct = round($childCount / $safeDenom * 100, 1);
            $childSweep = $groupCount > 0 ? ($childCount / $groupSafeDenom) * $groupSweep : 0.0;
            $childStart = $childAngle;
            $childEnd = $childAngle + $childSweep;
            $childId = 'outer-'.$gIndex.'-'.$cIndex;

            if ($childCount > 0) {
                $outerArcs[] = [
                    'id' => $childId,
                    'parent_id' => $groupId,
                    'label' => $child['label'],
                    'sublabel' => $group['label'],
                    'count' => $childCount,
                    'formatted' => $fmt($childCount),
                    'percentage' => $fmt($childPct, 1).'%',
                    'color' => $child['color'] ?? ($group['color'] ?? '#2563eb'),
                    'url' => $child['url'] ?? ($group['url'] ?? null),
                    'path' => $describeAnnularSector($childStart, $childEnd, $rOuterStart, $rOuterEnd),
                ];
            }

            if (($child['show_in_legend'] ?? true) === true) {
                $legendItems[] = [
                    'id' => $childId,
                    'parent_id' => $groupId,
                    'label' => $child['label'],
                    'group_label' => $group['label'],
                    'count' => $childCount,
                    'formatted' => $fmt($childCount),
                    'percentage' => $fmt($childPct, 1),
                    'color' => $child['color'] ?? ($group['color'] ?? '#2563eb'),
                    'url' => $child['url'] ?? ($group['url'] ?? null),
                ];
            }

            $childAngle = $childEnd;
        }

        $currentAngle = $groupEnd;
    }
@endphp

<div
    class="stat-sunburst-layout"
    x-data="{
        activeId: null,
        activeLabel: null,
        activeSub: null,
        activeCount: null,
        activePct: null,
        hoverSlice(id, label, sub, count, pct) {
            this.activeId = id;
            this.activeLabel = label;
            this.activeSub = sub;
            this.activeCount = count;
            this.activePct = pct;
        },
        clearHover() {
            this.activeId = null;
            this.activeLabel = null;
            this.activeSub = null;
            this.activeCount = null;
            this.activePct = null;
        }
    }"
>
    {{-- Kanvas SVG Sunburst 2-Ring --}}
    <div class="stat-sunburst-visual">
        <div class="stat-sunburst-svg-wrap">
            <svg
                viewBox="0 0 220 220"
                class="stat-sunburst-svg"
                role="img"
                aria-label="{{ $ariaLabel }}"
                @mouseleave="clearHover()"
            >
                {{-- Track Latar Belakang Cincin Dalam & Luar --}}
                <circle
                    cx="{{ $cx }}"
                    cy="{{ $cy }}"
                    r="{{ ($rInnerStart + $rInnerEnd) / 2 }}"
                    fill="none"
                    stroke="#f1f5f9"
                    stroke-width="{{ $rInnerEnd - $rInnerStart }}"
                />
                <circle
                    cx="{{ $cx }}"
                    cy="{{ $cy }}"
                    r="{{ ($rOuterStart + $rOuterEnd) / 2 }}"
                    fill="none"
                    stroke="#f8fafc"
                    stroke-width="{{ $rOuterEnd - $rOuterStart }}"
                />

                {{-- Cincin Dalam (Level 1: Kelompok Utama) --}}
                <g class="stat-sunburst-ring-inner">
                    @foreach ($innerArcs as $arc)
                        @if ($arc['path'] !== '')
                            @if ($arc['url'])
                                <a
                                    href="{{ $arc['url'] }}"
                                    class="stat-sunburst-link"
                                    @mouseenter="hoverSlice(@js($arc['id']), @js($arc['label']), @js($arc['sublabel']), @js($arc['formatted']), @js($arc['percentage']))"
                                    @focus="hoverSlice(@js($arc['id']), @js($arc['label']), @js($arc['sublabel']), @js($arc['formatted']), @js($arc['percentage']))"
                                    @blur="clearHover()"
                                >
                                    <path
                                        d="{{ $arc['path'] }}"
                                        fill="{{ $arc['color'] }}"
                                        class="stat-sunburst-arc stat-sunburst-arc-inner"
                                        :class="{
                                            'is-hovered': activeId === @js($arc['id']),
                                            'is-dimmed': activeId !== null && activeId !== @js($arc['id'])
                                        }"
                                    >
                                        <title>{{ $arc['label'] }}: {{ $arc['formatted'] }} {{ $unitLabel }} ({{ $arc['percentage'] }})</title>
                                    </path>
                                </a>
                            @else
                                <path
                                    d="{{ $arc['path'] }}"
                                    fill="{{ $arc['color'] }}"
                                    class="stat-sunburst-arc stat-sunburst-arc-inner"
                                    @mouseenter="hoverSlice(@js($arc['id']), @js($arc['label']), @js($arc['sublabel']), @js($arc['formatted']), @js($arc['percentage']))"
                                    :class="{
                                        'is-hovered': activeId === @js($arc['id']),
                                        'is-dimmed': activeId !== null && activeId !== @js($arc['id'])
                                    }"
                                >
                                    <title>{{ $arc['label'] }}: {{ $arc['formatted'] }} {{ $unitLabel }} ({{ $arc['percentage'] }})</title>
                                </path>
                            @endif
                        @endif
                    @endforeach
                </g>

                {{-- Cincin Luar (Level 2: Rincian Status / Ketersediaan) --}}
                <g class="stat-sunburst-ring-outer">
                    @foreach ($outerArcs as $arc)
                        @if ($arc['path'] !== '')
                            @if ($arc['url'])
                                <a
                                    href="{{ $arc['url'] }}"
                                    class="stat-sunburst-link"
                                    @mouseenter="hoverSlice(@js($arc['id']), @js($arc['label']), @js($arc['sublabel']), @js($arc['formatted']), @js($arc['percentage']))"
                                    @focus="hoverSlice(@js($arc['id']), @js($arc['label']), @js($arc['sublabel']), @js($arc['formatted']), @js($arc['percentage']))"
                                    @blur="clearHover()"
                                >
                                    <path
                                        d="{{ $arc['path'] }}"
                                        fill="{{ $arc['color'] }}"
                                        class="stat-sunburst-arc stat-sunburst-arc-outer"
                                        :class="{
                                            'is-hovered': activeId === @js($arc['id']) || activeId === @js($arc['parent_id']),
                                            'is-dimmed': activeId !== null && activeId !== @js($arc['id']) && activeId !== @js($arc['parent_id'])
                                        }"
                                    >
                                        <title>{{ $arc['label'] }} ({{ $arc['sublabel'] }}): {{ $arc['formatted'] }} {{ $unitLabel }} ({{ $arc['percentage'] }})</title>
                                    </path>
                                </a>
                            @else
                                <path
                                    d="{{ $arc['path'] }}"
                                    fill="{{ $arc['color'] }}"
                                    class="stat-sunburst-arc stat-sunburst-arc-outer"
                                    @mouseenter="hoverSlice(@js($arc['id']), @js($arc['label']), @js($arc['sublabel']), @js($arc['formatted']), @js($arc['percentage']))"
                                    :class="{
                                        'is-hovered': activeId === @js($arc['id']) || activeId === @js($arc['parent_id']),
                                        'is-dimmed': activeId !== null && activeId !== @js($arc['id']) && activeId !== @js($arc['parent_id'])
                                    }"
                                >
                                    <title>{{ $arc['label'] }} ({{ $arc['sublabel'] }}): {{ $arc['formatted'] }} {{ $unitLabel }} ({{ $arc['percentage'] }})</title>
                                </path>
                            @endif
                        @endif
                    @endforeach
                </g>
            </svg>

            {{-- Label Tengah Dinamis (Menampilkan Total atau Segmen yang Di-hover) --}}
            <div class="stat-sunburst-center tabular-nums">
                <span
                    class="stat-sunburst-center-value"
                    x-text="activeCount !== null ? activeCount : @js($fmt($totalCount))"
                >{{ $fmt($totalCount) }}</span>
                <span
                    class="stat-sunburst-center-label"
                    x-text="activeLabel !== null ? activeLabel : @js($centerLabel)"
                >{{ $centerLabel }}</span>
                <span
                    class="stat-sunburst-center-pct"
                    x-show="activePct !== null"
                    x-text="activePct"
                    x-cloak
                ></span>
            </div>
        </div>
    </div>

    {{-- Daftar Legenda Interaktif --}}
    <div class="stat-sunburst-legend-wrap">
        {{-- Ringkasan Kelompok Cincin Dalam --}}
        <div class="stat-sunburst-groups" role="list" aria-label="Kelompok utama">
            @foreach ($groups as $gIndex => $group)
                @php
                    $gCount = (int) ($group['count'] ?? 0);
                    $gPct = $fmt(round($gCount / $safeDenom * 100, 1), 1).'%';
                    $gId = 'inner-'.$gIndex;
                @endphp
                <a
                    href="{{ $group['url'] ?? '#' }}"
                    class="stat-sunburst-group-pill"
                    @mouseenter="hoverSlice(@js($gId), @js($group['label']), @js($group['sublabel'] ?? 'Kelompok Utama'), @js($fmt($gCount)), @js($gPct))"
                    @mouseleave="clearHover()"
                    :class="{ 'is-active': activeId === @js($gId) }"
                >
                    <span class="stat-legend-dot" style="background-color: {{ $group['color'] ?? '#2563eb' }}"></span>
                    <span class="stat-sunburst-group-name">{{ $group['label'] }}</span>
                    <span class="stat-sunburst-group-val tabular-nums">{{ $fmt($gCount) }}</span>
                </a>
            @endforeach
        </div>

        {{-- Rincian Cincin Luar --}}
        <ul class="stat-legend-list stat-sunburst-legend-list">
            @foreach ($legendItems as $item)
                <li>
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="stat-legend-link"
                        @mouseenter="hoverSlice(@js($item['id']), @js($item['label']), @js($item['group_label']), @js($item['formatted']), @js($item['percentage'].'%'))"
                        @mouseleave="clearHover()"
                        :class="{ 'is-highlighted': activeId === @js($item['id']) || activeId === @js($item['parent_id']) }"
                        title="Lihat {{ $item['label'] }} ({{ $item['formatted'] }} {{ $unitLabel }})"
                    >
                        <span class="d-flex align-items-center gap-2 min-w-0">
                            <span class="stat-legend-dot" style="background-color: {{ $item['color'] }}"></span>
                            <span class="text-truncate">{{ $item['label'] }}</span>
                        </span>
                        <span class="stat-figure-inline tabular-nums flex-shrink-0">
                            {{ $item['formatted'] }}
                            @if ($unitLabel)
                                <span class="stat-figure-unit">{{ $unitLabel }}</span>
                            @endif
                            <span class="stat-figure-muted ms-1">({{ $item['percentage'] }}%)</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>

