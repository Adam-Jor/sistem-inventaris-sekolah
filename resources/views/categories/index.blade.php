<x-app-layout title="Kategori Barang" :breadcrumbs="['Kategori' => null]">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-2 pb-md-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-0 font-weight-bolder">Daftar Kategori Barang</h5>
                            <p class="text-sm text-secondary mb-0">
                                Kelola pengelompokan inventaris barang sekolah
                            </p>
                        </div>

                        <div class="w-100 w-md-auto">
                            <a href="{{ route('categories.create') }}" class="btn btn-sm bg-gradient-primary mb-0 w-100 w-md-auto">
                                + Tambah Kategori
                            </a>
                        </div>
                    </div>

                    @php
                        $baseQuery = ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : [];
                        $activeFilters = [];
                        if ($search !== '') {
                            $activeFilters[] = ['label' => 'Pencarian', 'value' => $search, 'url' => route('categories.index', $baseQuery)];
                        }
                    @endphp

                    <form method="GET" action="{{ route('categories.index') }}" class="row g-2 align-items-center modern-filter-bar mb-2">
                        @if (($perPage ?? 10) !== 10)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                        @endif
                        <div class="col-12 col-sm-8 col-md-9 col-lg-10">
                            <x-search-input
                                name="search"
                                :value="$search"
                                placeholder="Cari nama atau deskripsi kategori..."
                            />
                        </div>
                        <div class="col-12 col-sm-4 col-md-3 col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100">Cari</button>
                            @if ($search !== '')
                                <a href="{{ route('categories.index', $baseQuery) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">Reset</a>
                            @endif
                        </div>
                    </form>

                    <x-filter-chips :filters="$activeFilters" :resetUrl="route('categories.index', $baseQuery)" />
                </div>

                <div class="card-body px-0 pt-2 pb-2">
                    <x-table-toolbar :paginator="$categories" />

                    {{-- Tampilan Tabel Desktop (>= 768px) --}}
                    <div class="table-responsive p-0 d-none d-md-block">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 60px;">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Nama Kategori</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Deskripsi</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jumlah Barang</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dibuat</th>
                                    <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $index => $category)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold text-secondary">
                                                {{ number_format($categories->firstItem() + $index, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            <h6 class="mb-0 text-sm">{{ $category->name }}</h6>
                                        </td>
                                        <td>
                                            <p class="text-xs text-secondary mb-0 text-wrap" style="max-width: 360px;">
                                                {{ $category->description ?: '-' }}
                                            </p>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-{{ $category->items_count > 0 ? 'info' : 'secondary' }}">
                                                {{ number_format($category->items_count, 0, ',', '.') }} Barang
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $category->created_at?->translatedFormat('d M Y') ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-xs btn-outline-dark mb-0 px-3 py-1">
                                                    Edit
                                                </a>
                                                <button
                                                    type="button"
                                                    x-data=""
                                                    x-on:click.prevent="$dispatch('open-modal', 'delete-category-{{ $category->id }}')"
                                                    class="btn btn-xs bg-gradient-danger mb-0 px-3 py-1"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <p class="text-sm text-secondary mb-2">
                                                {{ $search !== '' ? "Tidak ada kategori yang cocok dengan pencarian \"{$search}\"." : 'Belum ada data kategori barang.' }}
                                            </p>
                                            @if ($search === '')
                                                <a href="{{ route('categories.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                                    + Tambah Kategori Pertama
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Tampilan Kartu Mobile (< 768px) --}}
                    <div class="mobile-table-cards d-md-none">
                        @forelse ($categories as $category)
                            <div class="mobile-row-card">
                                <div class="mobile-row-header">
                                    <div class="mobile-row-header-main">
                                        <span class="mobile-row-caption">Nama Kategori</span>
                                        <h6 class="mobile-row-title">{{ $category->name }}</h6>
                                    </div>
                                    <span class="badge badge-sm bg-gradient-{{ $category->items_count > 0 ? 'info' : 'secondary' }} flex-shrink-0">
                                        {{ number_format($category->items_count, 0, ',', '.') }} Barang
                                    </span>
                                </div>

                                <div class="mobile-row-meta">
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Deskripsi</span>
                                        <span class="mobile-field-value">{{ $category->description ?: '-' }}</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Tanggal Dibuat</span>
                                        <span class="mobile-field-value">{{ $category->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
                                    </div>
                                </div>

                                <div class="mobile-row-actions">
                                    <a href="{{ route('categories.edit', $category) }}" class="btn mobile-btn-detail">
                                        Edit Kategori
                                    </a>
                                    <button
                                        type="button"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'delete-category-{{ $category->id }}')"
                                        class="btn bg-gradient-danger"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="mobile-empty-state">
                                <p class="text-sm text-secondary mb-2">
                                    {{ $search !== '' ? "Tidak ada kategori yang cocok dengan pencarian \"{$search}\"." : 'Belum ada data kategori barang.' }}
                                </p>
                                @if ($search === '')
                                    <a href="{{ route('categories.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                        + Tambah Kategori Pertama
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    {{-- Modal Konfirmasi Hapus Kategori (dipakai bersama oleh Desktop & Mobile) --}}
                    @foreach ($categories as $category)
                        <x-modal name="delete-category-{{ $category->id }}" maxWidth="sm">
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="text-start">
                                @csrf
                                @method('DELETE')

                                <h6 class="font-weight-bolder mb-2">Hapus Kategori?</h6>
                                <p class="text-sm text-secondary mb-3 text-wrap">
                                    Apakah Anda yakin ingin menghapus kategori <strong>{{ $category->name }}</strong>?
                                    @if ($category->items_count > 0)
                                        <span class="d-block text-danger text-xs mt-2">
                                            Perhatian: Kategori ini masih memiliki {{ number_format($category->items_count, 0, ',', '.') }} barang dan tidak dapat dihapus.
                                        </span>
                                    @endif
                                </p>

                                <div class="d-flex justify-content-end gap-2">
                                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                    <x-danger-button>Ya, Hapus</x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    @endforeach

                    <x-table-pagination :paginator="$categories" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
