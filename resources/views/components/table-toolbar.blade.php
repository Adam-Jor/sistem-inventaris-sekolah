@props([
    'paginator',
    'options' => \App\Http\Controllers\Controller::PER_PAGE_OPTIONS,
])

@if ($paginator->total() > 0)
    @php
        $queryParams = collect(request()->query())->except(['page', 'per_page'])->all();
    @endphp

    <div class="app-table-toolbar">
        <form method="GET" action="{{ url()->current() }}" class="app-per-page-form">
            @foreach ($queryParams as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $nestedValue)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                    @endforeach
                @elseif ($value !== null && $value !== '')
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <label for="per-page-select" class="app-per-page-label">Tampilkan</label>
            <select
                id="per-page-select"
                name="per_page"
                class="form-select form-select-sm app-per-page-select"
                aria-label="Jumlah data per halaman"
                onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()"
            >
                @foreach ($options as $option)
                    <option value="{{ $option }}" @selected((int) $paginator->perPage() === (int) $option)>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            <span class="app-per-page-label">data</span>
        </form>

        <span class="app-table-summary">
            Total <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> data
        </span>
    </div>
@endif

