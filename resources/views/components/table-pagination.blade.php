@props([
    'paginator',
])

@if ($paginator->total() > 0)
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
    @endphp

    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 px-3 px-md-4 pt-3 pb-2 border-top mt-0">
        <span class="text-xs text-secondary text-center text-md-start">
            Menampilkan <strong class="text-dark">{{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}&ndash;{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}</strong>
            dari <strong class="text-dark">{{ number_format($paginator->total(), 0, ',', '.') }}</strong> data
        </span>

        @if ($paginator->hasPages())
            <nav aria-label="Navigasi halaman">
                <ul class="pagination app-pagination pagination-primary justify-content-center justify-content-md-end mb-0">
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
                                <span class="page-link">{{ $page }}</span>
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
