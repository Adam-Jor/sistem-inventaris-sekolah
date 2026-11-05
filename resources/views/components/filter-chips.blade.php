@props([
    'filters' => [],
    'resetUrl' => '#',
])

@if (count($filters) > 0)
    <div class="active-filter-bar">
        <span class="active-filter-title">Filter aktif:</span>

        <div class="active-filter-list">
            @foreach ($filters as $chip)
                <a href="{{ $chip['url'] }}" class="active-filter-chip" title="Hapus filter {{ $chip['label'] }}">
                    <span class="chip-key">{{ $chip['label'] }}:</span>
                    <strong class="chip-val">{{ $chip['value'] }}</strong>
                    <span class="chip-remove" aria-hidden="true">&times;</span>
                </a>
            @endforeach

            <a href="{{ $resetUrl }}" class="active-filter-reset">
                Reset Semua
            </a>
        </div>
    </div>
@endif

