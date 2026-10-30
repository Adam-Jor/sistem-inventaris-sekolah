@php
    $hasFilters = $search !== '' || $condition !== '';
@endphp

<x-app-layout title="Data Pengembalian" :breadcrumbs="['Data Pengembalian' => null]">
    {{-- Daftar Peminjaman Aktif yang Menunggu Pengembalian --}}
    @if ($unreturnedLoans->isNotEmpty())
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border">
                    <div class="card-header pb-2 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0 font-weight-bolder">Menunggu Pengembalian ({{ $unreturnedLoans->count() }})</h6>
                            <p class="text-xs text-secondary mb-0">
                                Daftar peminjaman aktif/terlambat yang siap dicatat pengembaliannya oleh Admin
                            </p>
                        </div>
                        <a href="{{ route('loans.index') }}" class="text-xs font-weight-bold text-primary">Lihat Semua Peminjaman</a>
                    </div>

                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Peminjam</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Barang</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jumlah</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jatuh Tempo</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($unreturnedLoans as $activeLoan)
                                        <tr>
                                            <td class="ps-4">
                                                <h6 class="mb-0 text-sm">{{ $activeLoan->user?->name ?? '-' }}</h6>
                                                <span class="text-xs text-secondary">{{ $activeLoan->user?->email }}</span>
                                            </td>
                                            <td>
                                                <span class="text-xs font-weight-bold text-dark d-block">{{ $activeLoan->item?->name ?? '-' }}</span>
                                                <span class="text-xs text-secondary">{{ $activeLoan->item?->code }}</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-xs font-weight-bold">{{ number_format($activeLoan->quantity, 0, ',', '.') }} unit</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-xs font-weight-bold {{ $activeLoan->status === 'terlambat' ? 'text-danger' : 'text-secondary' }}">
                                                    {{ $activeLoan->due_date?->translatedFormat('d M Y') }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="badge badge-sm bg-gradient-{{ $activeLoan->status === 'terlambat' ? 'danger' : 'info' }}">
                                                    {{ ucfirst($activeLoan->status) }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-end pe-4">
                                                <a href="{{ route('loans.return.create', $activeLoan) }}" class="btn btn-xs bg-gradient-primary mb-0 px-3 py-1">
                                                    Catat Pengembalian
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tabel Riwayat Catatan Pengembalian --}}
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-0 font-weight-bolder">Riwayat Pengembalian Barang</h5>
                            <p class="text-sm text-secondary mb-0">
                                Daftar seluruh barang yang telah dikembalikan beserta kondisi saat diterima kembali
                            </p>
                        </div>
                    </div>

                    {{-- Form Pencarian & Filter Kondisi --}}
                    <form method="GET" action="{{ route('return-logs.index') }}" class="row g-2 align-items-center bg-gray-100 p-2 border-radius-lg mb-2">
                        @if (($perPage ?? 10) !== 10)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                        @endif

                        <div class="col-lg-6 col-md-6 col-12">
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                class="form-control form-control-sm bg-white"
                                placeholder="Cari nama/kode barang atau nama peminjam..."
                            >
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
                            <select name="condition" class="form-select form-select-sm bg-white">
                                <option value="">Semua Kondisi Saat Kembali</option>
                                <option value="baik" @selected($condition === 'baik')>Baik</option>
                                <option value="rusak ringan" @selected($condition === 'rusak ringan')>Rusak Ringan</option>
                                <option value="rusak berat" @selected($condition === 'rusak berat')>Rusak Berat</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-2 col-sm-6 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100">Filter</button>
                            @if ($hasFilters)
                                <a href="{{ route('return-logs.index', ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : []) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="card-body px-0 pt-2 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 55px;">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Peminjam</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Barang</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jumlah</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl Kembali</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kondisi Kembali</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Catatan Admin</th>
                                    <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($returnLogs as $index => $returnLog)
                                    @php
                                        $conditionBadge = match ($returnLog->condition_on_return) {
                                            'baik' => 'success',
                                            'rusak ringan' => 'warning',
                                            'rusak berat' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold text-secondary">
                                                {{ $returnLogs->firstItem() + $index }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-0 text-sm">{{ $returnLog->loan?->user?->name ?? '-' }}</h6>
                                                <span class="text-xs text-secondary">{{ $returnLog->loan?->user?->email }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-0 text-sm">{{ $returnLog->loan?->item?->name ?? '-' }}</h6>
                                                <span class="text-xs text-secondary font-weight-bold">{{ $returnLog->loan?->item?->code }}</span>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold text-dark">{{ number_format($returnLog->loan?->quantity ?? 0, 0, ',', '.') }} unit</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $returnLog->returned_at?->translatedFormat('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-{{ $conditionBadge }}">
                                                {{ ucwords($returnLog->condition_on_return) }}
                                            </span>
                                        </td>
                                        <td>
                                            <p class="text-xs text-secondary mb-0 text-wrap" style="max-width: 260px;">
                                                {{ $returnLog->notes ?: '-' }}
                                            </p>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <a href="{{ route('return-logs.show', $returnLog) }}" class="btn btn-xs btn-outline-secondary mb-0 px-3 py-1">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <p class="text-sm text-secondary mb-0">
                                                {{ $hasFilters ? 'Tidak ada data pengembalian yang cocok dengan filter Anda.' : 'Belum ada data pengembalian barang yang tercatat.' }}
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-table-pagination :paginator="$returnLogs" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

