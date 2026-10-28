@php
    $isAdmin = auth()->user()->isAdmin();
    $pageTitle = $isAdmin ? 'Data Peminjaman' : 'Peminjaman Saya';
    $hasFilters = $search !== '' || $status !== '';
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
                                    ? 'Verifikasi pengajuan peminjaman, pantau jatuh tempo, dan kelola riwayat transaksi'
                                    : 'Pantau status pengajuan dan riwayat peminjaman barang sekolah Anda' }}
                            </p>
                        </div>

                        <div>
                            <a href="{{ route('loans.create') }}" class="btn btn-sm bg-gradient-{{ $isAdmin ? 'primary' : 'info' }} mb-0">
                                + Ajukan Peminjaman
                            </a>
                        </div>
                    </div>

                    {{-- Form Pencarian & Filter Status --}}
                    <form method="GET" action="{{ route('loans.index') }}" class="row g-2 align-items-center bg-gray-100 p-2 border-radius-lg mb-2">
                        @if (($perPage ?? 10) !== 10)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                        @endif

                        <div class="col-lg-6 col-md-6 col-12">
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                class="form-control form-control-sm bg-white"
                                placeholder="{{ $isAdmin ? 'Cari nama/kode barang atau nama peminjam...' : 'Cari nama atau kode barang...' }}"
                            >
                        </div>

                        <div class="col-lg-4 col-md-4 col-sm-6 col-12">
                            <select name="status" class="form-select form-select-sm bg-white">
                                <option value="">Semua Status</option>
                                <option value="menunggu" @selected($status === 'menunggu')>Menunggu Verifikasi</option>
                                <option value="dipinjam" @selected($status === 'dipinjam')>Sedang Dipinjam</option>
                                <option value="terlambat" @selected($status === 'terlambat')>Terlambat</option>
                                <option value="dikembalikan" @selected($status === 'dikembalikan')>Dikembalikan</option>
                                <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-2 col-sm-6 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100">Filter</button>
                            @if ($hasFilters)
                                <a href="{{ route('loans.index', ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : []) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">Reset</a>
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
                                    @if ($isAdmin)
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Peminjam</th>
                                    @endif
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Barang</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jumlah</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Tgl Pinjam</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jatuh Tempo</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($loans as $index => $loan)
                                    @php
                                        $badgeColor = match ($loan->status) {
                                            'dipinjam' => 'info',
                                            'dikembalikan' => 'success',
                                            'terlambat' => 'danger',
                                            'menunggu' => 'warning',
                                            'ditolak' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold text-secondary">
                                                {{ $loans->firstItem() + $index }}
                                            </span>
                                        </td>

                                        @if ($isAdmin)
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm">{{ $loan->user?->name ?? '-' }}</h6>
                                                    <span class="text-xs text-secondary">{{ $loan->user?->email }}</span>
                                                </div>
                                            </td>
                                        @endif

                                        <td>
                                            <div class="d-flex flex-column">
                                                <h6 class="mb-0 text-sm">
                                                    <a href="{{ route('loans.show', $loan) }}" class="text-dark">
                                                        {{ $loan->item?->name ?? '-' }}
                                                    </a>
                                                </h6>
                                                <span class="text-xs text-secondary font-weight-bold">
                                                    {{ $loan->item?->code }}
                                                    @if ($loan->item?->trashed())
                                                        <span class="text-danger">(Barang Dihapus)</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>

                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold text-dark">{{ $loan->quantity }} unit</span>
                                        </td>

                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $loan->loan_date?->translatedFormat('d M Y') }}
                                            </span>
                                        </td>

                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold {{ $loan->status === 'terlambat' ? 'text-danger' : 'text-secondary' }}">
                                                {{ $loan->due_date?->translatedFormat('d M Y') }}
                                            </span>
                                        </td>

                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-{{ $badgeColor }}">
                                                {{ $loan->status }}
                                            </span>
                                        </td>

                                        <td class="align-middle text-end pe-4">
                                            <div class="d-inline-flex flex-wrap align-items-center justify-content-end gap-1">
                                                <a href="{{ route('loans.show', $loan) }}" class="btn btn-xs btn-outline-secondary mb-0 px-2 py-1">
                                                    Detail
                                                </a>

                                                @if ($isAdmin)
                                                    @if ($loan->status === 'menunggu')
                                                        <button
                                                            type="button"
                                                            x-data=""
                                                            x-on:click.prevent="$dispatch('open-modal', 'approve-loan-{{ $loan->id }}')"
                                                            class="btn btn-xs bg-gradient-success mb-0 px-2 py-1"
                                                        >
                                                            Setujui
                                                        </button>

                                                        <button
                                                            type="button"
                                                            x-data=""
                                                            x-on:click.prevent="$dispatch('open-modal', 'reject-loan-{{ $loan->id }}')"
                                                            class="btn btn-xs bg-gradient-warning mb-0 px-2 py-1"
                                                        >
                                                            Tolak
                                                        </button>
                                                    @elseif (in_array($loan->status, ['dipinjam', 'terlambat'], true))
                                                        <a href="{{ route('loans.return.create', $loan) }}" class="btn btn-xs bg-gradient-info mb-0 px-2 py-1">
                                                            Catat Kembali
                                                        </a>
                                                    @endif

                                                    <button
                                                        type="button"
                                                        x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'delete-loan-{{ $loan->id }}')"
                                                        class="btn btn-xs bg-gradient-danger mb-0 px-2 py-1"
                                                    >
                                                        Hapus
                                                    </button>
                                                @endif
                                            </div>

                                            @if ($isAdmin)
                                                @if ($loan->status === 'menunggu')
                                                    {{-- Modal Konfirmasi Setujui Peminjaman --}}
                                                    <x-modal name="approve-loan-{{ $loan->id }}" maxWidth="sm">
                                                        <form method="POST" action="{{ route('loans.approve', $loan) }}" class="text-start">
                                                            @csrf
                                                            @method('PATCH')

                                                            <h6 class="font-weight-bolder mb-2">Setujui Peminjaman?</h6>
                                                            <p class="text-sm text-secondary mb-3 text-wrap">
                                                                Setujui peminjaman <strong>{{ $loan->item?->name }} ({{ $loan->quantity }} unit)</strong>
                                                                oleh <strong>{{ $loan->user?->name }}</strong>?
                                                                Stok barang akan langsung dikurangi sebanyak {{ $loan->quantity }} unit.
                                                            </p>

                                                            <div class="d-flex justify-content-end gap-2">
                                                                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                                                <x-primary-button class="bg-gradient-success">Ya, Setujui</x-primary-button>
                                                            </div>
                                                        </form>
                                                    </x-modal>

                                                    {{-- Modal Konfirmasi Tolak Peminjaman --}}
                                                    <x-modal name="reject-loan-{{ $loan->id }}" maxWidth="sm">
                                                        <form method="POST" action="{{ route('loans.reject', $loan) }}" class="text-start">
                                                            @csrf
                                                            @method('PATCH')

                                                            <h6 class="font-weight-bolder mb-2">Tolak Pengajuan?</h6>
                                                            <p class="text-sm text-secondary mb-3 text-wrap">
                                                                Tolak pengajuan peminjaman <strong>{{ $loan->item?->name }}</strong>
                                                                oleh <strong>{{ $loan->user?->name }}</strong>?
                                                            </p>

                                                            <div class="mb-3">
                                                                <x-input-label for="rejection_reason_{{ $loan->id }}" value="Alasan Penolakan (Opsional)" />
                                                                <input
                                                                    id="rejection_reason_{{ $loan->id }}"
                                                                    type="text"
                                                                    name="rejection_reason"
                                                                    class="form-control form-control-sm"
                                                                    placeholder="Contoh: Barang akan dipakai ujian sekolah"
                                                                >
                                                            </div>

                                                            <div class="d-flex justify-content-end gap-2">
                                                                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                                                <x-danger-button>Tolak Pengajuan</x-danger-button>
                                                            </div>
                                                        </form>
                                                    </x-modal>
                                                @endif

                                                {{-- Modal Konfirmasi Soft Delete Peminjaman --}}
                                                <x-modal name="delete-loan-{{ $loan->id }}" maxWidth="sm">
                                                    <form method="POST" action="{{ route('loans.destroy', $loan) }}" class="text-start">
                                                        @csrf
                                                        @method('DELETE')

                                                        <h6 class="font-weight-bolder mb-2">Hapus Data Peminjaman?</h6>
                                                        <p class="text-sm text-secondary mb-3 text-wrap">
                                                            Apakah Anda yakin ingin menghapus data peminjaman
                                                            <strong>{{ $loan->item?->name }}</strong> oleh <strong>{{ $loan->user?->name }}</strong>?
                                                            @if (in_array($loan->status, ['dipinjam', 'terlambat'], true))
                                                                <span class="d-block text-info text-xs mt-2">
                                                                    Catatan: Karena status masih {{ $loan->status }}, stok barang ({{ $loan->quantity }} unit) akan otomatis dikembalikan.
                                                                </span>
                                                            @endif
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
                                        <td colspan="{{ $isAdmin ? 8 : 7 }}" class="text-center py-5">
                                            <p class="text-sm text-secondary mb-2">
                                                {{ $hasFilters ? 'Tidak ada data peminjaman yang cocok dengan filter Anda.' : 'Belum ada data peminjaman.' }}
                                            </p>
                                            @if (! $hasFilters)
                                                <a href="{{ route('loans.create') }}" class="btn btn-sm bg-gradient-{{ $isAdmin ? 'primary' : 'info' }} mb-0">
                                                    + Ajukan Peminjaman Baru
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-table-pagination :paginator="$loans" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

