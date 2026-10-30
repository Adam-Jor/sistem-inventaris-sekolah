@php
    $isAdmin = auth()->user()->isAdmin();
    $pageTitle = $isAdmin ? 'Data Barang' : 'Daftar Barang';
    $hasFilters = $search !== '' || ($categoryId !== '' && $categoryId !== null) || $condition !== '';
@endphp

<x-app-layout :title="$pageTitle" :breadcrumbs="[$pageTitle => null]">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-0 font-weight-bolder">{{ $pageTitle }}</h5>
                            <p class="text-sm text-secondary mb-0">
                                {{ $isAdmin
                                    ? 'Kelola data barang inventaris, stok, dan kondisi barang sekolah'
                                    : 'Cari dan lihat ketersediaan barang sekolah untuk diajukan peminjamannya' }}
                            </p>
                        </div>

                        @if ($isAdmin)
                            <div>
                                <a href="{{ route('items.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                    + Tambah Barang
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Form Pencarian & Filter --}}
                    <form method="GET" action="{{ route('items.index') }}" class="row g-2 align-items-center bg-gray-100 p-2 border-radius-lg mb-2">
                        @if (($perPage ?? 10) !== 10)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                        @endif
                        <div class="col-lg-4 col-md-6 col-12">
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                class="form-control form-control-sm bg-white"
                                placeholder="Cari nama atau kode barang (misal: LAB-001)..."
                            >
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
                            <select name="category_id" class="form-select form-select-sm bg-white">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
                            <select name="condition" class="form-select form-select-sm bg-white">
                                <option value="">Semua Kondisi</option>
                                <option value="baik" @selected($condition === 'baik')>Baik</option>
                                <option value="rusak ringan" @selected($condition === 'rusak ringan')>Rusak Ringan</option>
                                <option value="rusak berat" @selected($condition === 'rusak berat')>Rusak Berat</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-12 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100">Filter</button>
                            @if ($hasFilters)
                                <a href="{{ route('items.index', ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : []) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">Reset</a>
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Barang</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Kategori</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stok</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Kondisi</th>
                                    <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $index => $item)
                                    @php
                                        $conditionBadge = match ($item->condition) {
                                            'baik' => 'success',
                                            'rusak ringan' => 'warning',
                                            'rusak berat' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold text-secondary">
                                                {{ $items->firstItem() + $index }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-0 text-sm">
                                                    <a href="{{ route('items.show', $item) }}" class="text-dark">
                                                        {{ $item->name }}
                                                    </a>
                                                </h6>
                                                <span class="text-xs text-secondary font-weight-bold">{{ $item->code }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs font-weight-bold text-dark">
                                                {{ $item->category?->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-{{ $item->stock > 0 ? 'info' : 'secondary' }}">
                                                {{ number_format($item->stock, 0, ',', '.') }} unit
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-{{ $conditionBadge }}">
                                                {{ ucwords($item->condition) }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <a href="{{ route('items.show', $item) }}" class="btn btn-xs btn-outline-secondary mb-0 px-3 py-1">
                                                    Detail
                                                </a>

                                                @if ($isAdmin)
                                                    <a href="{{ route('items.edit', $item) }}" class="btn btn-xs btn-outline-dark mb-0 px-3 py-1">
                                                        Edit
                                                    </a>
                                                    <button
                                                        type="button"
                                                        x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'delete-item-{{ $item->id }}')"
                                                        class="btn btn-xs bg-gradient-danger mb-0 px-3 py-1"
                                                    >
                                                        Hapus
                                                    </button>
                                                @else
                                                    @if ($item->isAvailable())
                                                        <a href="{{ route('loans.create', ['item_id' => $item->id]) }}" class="btn btn-xs bg-gradient-primary mb-0 px-3 py-1">
                                                            Ajukan Peminjaman
                                                        </a>
                                                    @else
                                                        <span class="badge badge-sm bg-gradient-secondary">Tidak Tersedia</span>
                                                    @endif
                                                @endif
                                            </div>

                                            @if ($isAdmin)
                                                {{-- Modal Konfirmasi Soft Delete Barang (Alpine.js) --}}
                                                <x-modal name="delete-item-{{ $item->id }}" maxWidth="sm">
                                                    <form method="POST" action="{{ route('items.destroy', $item) }}" class="text-start">
                                                        @csrf
                                                        @method('DELETE')

                                                        <h6 class="font-weight-bolder mb-2">Hapus Barang?</h6>
                                                        <p class="text-sm text-secondary mb-3 text-wrap">
                                                            Apakah Anda yakin ingin menghapus barang <strong>{{ $item->name }} ({{ $item->code }})</strong>?
                                                            Data riwayat peminjaman terkait barang ini tetap tersimpan.
                                                        </p>

                                                        <div class="d-flex justify-content-end gap-2">
                                                            <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                                            <x-danger-button>Ya, Hapus</x-danger-button>
                                                        </div>
                                                    </form>
                                                </x-modal>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <p class="text-sm text-secondary mb-2">
                                                {{ $hasFilters ? 'Tidak ada barang yang cocok dengan pencarian/filter Anda.' : 'Belum ada data barang inventaris.' }}
                                            </p>
                                            @if ($isAdmin && ! $hasFilters)
                                                <a href="{{ route('items.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                                    + Tambah Barang Pertama
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-table-pagination :paginator="$items" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

