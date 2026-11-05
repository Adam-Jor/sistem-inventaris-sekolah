@php
    $isAdmin = auth()->user()->isAdmin();
    $pageTitle = $isAdmin ? 'Data Peminjaman' : 'Peminjaman Saya';
    $hasFilters = $search !== '' || $status !== '';
@endphp

<x-app-layout :title="$pageTitle" :breadcrumbs="[$pageTitle => null]">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-2 pb-md-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-0 font-weight-bolder">{{ $pageTitle }}</h5>
                            <p class="text-sm text-secondary mb-0">
                                {{ $isAdmin
                                    ? 'Verifikasi pengajuan peminjaman, pantau jatuh tempo, dan kelola riwayat transaksi'
                                    : 'Pantau status pengajuan dan riwayat peminjaman barang sekolah Anda' }}
                            </p>
                        </div>

                        @php
                            $exportFilter = array_filter(['search' => $search, 'status' => $status], fn ($v) => $v !== '');
                        @endphp
                        <div class="d-flex flex-column flex-sm-row flex-wrap align-items-stretch align-items-sm-center justify-content-md-end gap-2 w-100 w-md-auto">
                            @if ($isAdmin)
                                <a href="{{ route('loans.export.excel', $exportFilter) }}" class="btn btn-sm btn-outline-success mb-0 w-100 w-sm-auto" title="Unduh laporan peminjaman dalam format Excel (.csv)">
                                    Ekspor Excel
                                </a>

                                <a href="{{ route('loans.export.pdf', $exportFilter) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark mb-0 w-100 w-sm-auto" title="Buka pratinjau cetak / simpan laporan sebagai PDF">
                                    Cetak / PDF
                                </a>

                                <form method="POST" action="{{ route('loans.notify-due') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary mb-0 w-100 w-sm-auto" title="Kirim notifikasi email untuk peminjaman yang mendekati (H-1/Hari-H) atau melewati jatuh tempo">
                                        Kirim Email Jatuh Tempo
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('loans.create') }}" class="btn btn-sm bg-gradient-primary mb-0 w-100 w-sm-auto">
                                + Ajukan Peminjaman
                            </a>
                        </div>
                    </div>

                    @php
                        $baseQuery = ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : [];
                        $statusOptions = [
                            '' => 'Semua Status',
                            'menunggu' => 'Menunggu',
                            'dipinjam' => 'Dipinjam',
                            'terlambat' => 'Terlambat',
                            'dikembalikan' => 'Dikembalikan',
                            'ditolak' => 'Ditolak',
                        ];
                        $activeFilters = [];
                        if ($search !== '') {
                            $activeFilters[] = [
                                'label' => 'Pencarian',
                                'value' => $search,
                                'url' => route('loans.index', array_filter($baseQuery + ['status' => $status], fn ($v) => $v !== '')),
                            ];
                        }
                        if ($status !== '') {
                            $activeFilters[] = [
                                'label' => 'Status',
                                'value' => $statusOptions[$status] ?? ucfirst($status),
                                'url' => route('loans.index', array_filter($baseQuery + ['search' => $search], fn ($v) => $v !== '')),
                            ];
                        }
                    @endphp

                    {{-- Quick Status Pills (1-Click Filter) --}}
                    <nav class="filter-status-pills mb-2" aria-label="Filter cepat status peminjaman">
                        @foreach ($statusOptions as $key => $label)
                            @php
                                $pillQuery = array_filter($baseQuery + ['search' => $search, 'status' => $key], fn ($v) => $v !== '');
                            @endphp
                            <a
                                href="{{ route('loans.index', $pillQuery) }}"
                                class="filter-status-pill {{ $status === $key ? 'active' : '' }}"
                            >{{ $label }}</a>
                        @endforeach
                    </nav>

                    {{-- Form Pencarian & Filter Status (Mobile-First) --}}
                    <form method="GET" action="{{ route('loans.index') }}" class="row g-2 align-items-center modern-filter-bar mb-2">
                        @if (($perPage ?? 10) !== 10)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                        @endif

                        <div class="col-12 col-md-6 col-lg-6">
                            <x-search-input
                                name="search"
                                :value="$search"
                                :placeholder="$isAdmin ? 'Cari nama/kode barang atau nama peminjam...' : 'Cari nama atau kode barang...'"
                            />
                        </div>

                        <div class="col-12 col-sm-7 col-md-4 col-lg-4">
                            <select name="status" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="form-select form-select-sm bg-white">
                                <option value="">Semua Status</option>
                                <option value="menunggu" @selected($status === 'menunggu')>Menunggu Verifikasi</option>
                                <option value="dipinjam" @selected($status === 'dipinjam')>Sedang Dipinjam</option>
                                <option value="terlambat" @selected($status === 'terlambat')>Terlambat</option>
                                <option value="dikembalikan" @selected($status === 'dikembalikan')>Dikembalikan</option>
                                <option value="ditolak" @selected($status === 'ditolak')>Ditolak</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-5 col-md-2 col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100">Filter</button>
                            @if ($hasFilters)
                                <a href="{{ route('loans.index', $baseQuery) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">Reset</a>
                            @endif
                        </div>
                    </form>

                    <x-filter-chips :filters="$activeFilters" :resetUrl="route('loans.index', $baseQuery)" />
                </div>

                <div class="card-body px-0 pt-2 pb-2">
                    <x-table-toolbar :paginator="$loans" />

                    {{-- Tampilan Tabel Desktop (>= 768px) --}}
                    <div class="table-responsive p-0 d-none d-md-block">
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
                                                {{ number_format($loans->firstItem() + $index, 0, ',', '.') }}
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
                                            <span class="text-xs font-weight-bold text-dark">{{ number_format($loan->quantity, 0, ',', '.') }} unit</span>
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
                                                {{ ucfirst($loan->status) }}
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
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 8 : 7 }}" class="text-center py-5">
                                            <p class="text-sm text-secondary mb-2">
                                                {{ $hasFilters ? 'Tidak ada data peminjaman yang cocok dengan filter Anda.' : 'Belum ada data peminjaman.' }}
                                            </p>
                                            @if (! $hasFilters)
                                                <a href="{{ route('loans.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                                    + Ajukan Peminjaman Baru
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
                        @forelse ($loans as $loan)
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
                            <div class="mobile-row-card">
                                <div class="mobile-row-header">
                                    <div class="mobile-row-header-main">
                                        <span class="mobile-row-caption">Barang Dipinjam</span>
                                        <h6 class="mobile-row-title">
                                            <a href="{{ route('loans.show', $loan) }}">
                                                {{ $loan->item?->name ?? '-' }}
                                            </a>
                                        </h6>
                                    </div>
                                    <span class="badge badge-sm bg-gradient-{{ $badgeColor }} flex-shrink-0">
                                        {{ ucfirst($loan->status) }}
                                    </span>
                                </div>

                                <div class="mobile-row-meta">
                                    @if ($isAdmin)
                                        <div class="mobile-row-field">
                                            <span class="mobile-field-label">Peminjam</span>
                                            <span class="mobile-field-value">{{ $loan->user?->name ?? '-' }}</span>
                                        </div>
                                    @endif
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Kode Barang</span>
                                        <span class="mobile-field-value">
                                            {{ $loan->item?->code ?? '-' }}
                                            @if ($loan->item?->trashed())
                                                <span class="text-danger">(Barang Dihapus)</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Jumlah Pinjam</span>
                                        <span class="mobile-field-value">{{ number_format($loan->quantity, 0, ',', '.') }} unit</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Tanggal Pinjam</span>
                                        <span class="mobile-field-value">{{ $loan->loan_date?->translatedFormat('d M Y') }}</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Jatuh Tempo</span>
                                        <span class="mobile-field-value {{ $loan->status === 'terlambat' ? 'text-danger' : '' }}">
                                            {{ $loan->due_date?->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mobile-row-actions">
                                    <a href="{{ route('loans.show', $loan) }}" class="btn mobile-btn-detail">
                                        {{ $isAdmin ? 'Lihat Detail' : 'Lihat Detail Peminjaman' }}
                                    </a>

                                    @if ($isAdmin)
                                        @if ($loan->status === 'menunggu')
                                            <button
                                                type="button"
                                                x-data=""
                                                x-on:click.prevent="$dispatch('open-modal', 'approve-loan-{{ $loan->id }}')"
                                                class="btn bg-gradient-success"
                                            >
                                                Setujui
                                            </button>

                                            <button
                                                type="button"
                                                x-data=""
                                                x-on:click.prevent="$dispatch('open-modal', 'reject-loan-{{ $loan->id }}')"
                                                class="btn bg-gradient-warning"
                                            >
                                                Tolak
                                            </button>
                                        @elseif (in_array($loan->status, ['dipinjam', 'terlambat'], true))
                                            <a href="{{ route('loans.return.create', $loan) }}" class="btn bg-gradient-info">
                                                Catat Kembali
                                            </a>
                                        @endif

                                        <button
                                            type="button"
                                            x-data=""
                                            x-on:click.prevent="$dispatch('open-modal', 'delete-loan-{{ $loan->id }}')"
                                            class="btn bg-gradient-danger"
                                        >
                                            Hapus
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="mobile-empty-state">
                                <p class="text-sm text-secondary mb-2">
                                    {{ $hasFilters ? 'Tidak ada data peminjaman yang cocok dengan filter Anda.' : 'Belum ada data peminjaman.' }}
                                </p>
                                @if (! $hasFilters)
                                    <a href="{{ route('loans.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                        + Ajukan Peminjaman Baru
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    {{-- Modal Konfirmasi Admin (dipakai bersama oleh Desktop & Mobile) --}}
                    @if ($isAdmin)
                        @foreach ($loans as $loan)
                            @if ($loan->status === 'menunggu')
                                {{-- Modal Konfirmasi Setujui Peminjaman --}}
                                <x-modal name="approve-loan-{{ $loan->id }}" maxWidth="sm">
                                    <form method="POST" action="{{ route('loans.approve', $loan) }}" class="text-start">
                                        @csrf
                                        @method('PATCH')

                                        <h6 class="font-weight-bolder mb-2">Setujui Peminjaman?</h6>
                                        <p class="text-sm text-secondary mb-3 text-wrap">
                                            Setujui peminjaman <strong>{{ $loan->item?->name }} ({{ number_format($loan->quantity, 0, ',', '.') }} unit)</strong>
                                            oleh <strong>{{ $loan->user?->name }}</strong>?
                                            Stok barang akan langsung dikurangi sebanyak {{ number_format($loan->quantity, 0, ',', '.') }} unit.
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
                                                Catatan: Karena status masih {{ $loan->status }}, stok barang ({{ number_format($loan->quantity, 0, ',', '.') }} unit) akan otomatis dikembalikan.
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
                    @endif

                    <x-table-pagination :paginator="$loans" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
