@props([
    'paginator',
    'options' => \App\Http\Controllers\Controller::PER_PAGE_OPTIONS,
])

@if ($paginator->total() > 0)
    @php
        $queryParams = collect(request()->query())->except(['page', 'per_page'])->all();
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
    @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 px-4 pt-3 pb-2 border-top mt-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="GET" action="{{ url()->current() }}" class="d-inline-flex align-items-center gap-2 mb-0">
                @foreach ($queryParams as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $nestedValue)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                        @endforeach
                    @elseif ($value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <label for="per-page-select" class="text-xs text-secondary font-weight-normal mb-0">Tampilkan</label>
                <select
                    id="per-page-select"
                    name="per_page"
                    class="form-select form-select-sm py-1 ps-2 pe-4 bg-white"
                    style="width: auto; min-width: 68px;"
                    onchange="this.form.submit()"
                >
                    @foreach ($options as $option)
                        <option value="{{ $option }}" @selected((int) $paginator->perPage() === (int) $option)>
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
                <span class="text-xs text-secondary">data</span>
            </form>

            <span class="text-xs text-secondary ms-md-2">
                Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $paginator->total() }}</strong> data
            </span>
        </div>

        @if ($paginator->hasPages())
            <nav aria-label="Navigasi halaman">
                <ul class="pagination pagination-primary justify-content-md-end mb-0">
                    {{-- Tombol Sebelumnya --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
                        </li>
                    @endif

                    {{-- Halaman Pertama + Elipsis --}}
                    @if ($startPage > 1)
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                        </li>
                        @if ($startPage > 2)
                            <li class="page-item disabled" aria-disabled="true">
                                <span class="page-link">&hellip;</span>
                            </li>
                        @endif
                    @endif

                    {{-- Nomor Halaman Sekitar Halaman Aktif --}}
                    @for ($page = $startPage; $page <= $endPage; $page++)
                        @if ($page === $currentPage)
                            <li class="page-item active" aria-current="page">
                                <span class="page-link text-white">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endfor

                    {{-- Elipsis + Halaman Terakhir --}}
                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)
                            <li class="page-item disabled" aria-disabled="true">
                                <span class="page-link">&hellip;</span>
                            </li>
                        @endif
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
                        </li>
                    @endif

                    {{-- Tombol Berikutnya --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
@endif

