@php
    $isAdmin = auth()->user()->isAdmin();
    $listLabel = $isAdmin ? 'Data Peminjaman' : 'Peminjaman Saya';
    $badgeColor = match ($loan->status) {
        'dipinjam' => 'info',
        'dikembalikan' => 'success',
        'terlambat' => 'danger',
        'menunggu' => 'warning',
        'ditolak' => 'secondary',
        default => 'secondary',
    };
@endphp

<x-app-layout title="Detail Peminjaman" :breadcrumbs="[$listLabel => route('loans.index'), 'Detail Peminjaman' => null]">
    <div class="row">
        <div class="col-lg-7 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 font-weight-bolder">Informasi Peminjaman</h5>
                        <p class="text-xs text-secondary mb-0">
                            Diajukan pada {{ $loan->created_at?->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>
                    <span class="badge badge-sm bg-gradient-{{ $badgeColor }}">
                        {{ $loan->status }}
                    </span>
                </div>

                <div class="card-body">
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Peminjam</span>
                            <span class="font-weight-bold text-dark">
                                {{ $loan->user?->name ?? '-' }}
                                <span class="text-xs text-secondary">({{ $loan->user?->email }})</span>
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Barang</span>
                            <span class="font-weight-bold text-dark">
                                {{ $loan->item?->name ?? '-' }}
                                <span class="text-xs text-secondary">({{ $loan->item?->code }})</span>
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Kategori Barang</span>
                            <span class="font-weight-bold text-dark">{{ $loan->item?->category?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Jumlah Dipinjam</span>
                            <span class="badge badge-sm bg-gradient-dark">{{ $loan->quantity }} unit</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Tanggal Pinjam</span>
                            <span class="font-weight-bold text-dark">{{ $loan->loan_date?->translatedFormat('d M Y') }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Batas Tanggal Kembali</span>
                            <span class="font-weight-bold {{ $loan->status === 'terlambat' ? 'text-danger' : 'text-dark' }}">
                                {{ $loan->due_date?->translatedFormat('d M Y') }}
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex flex-column text-sm">
                            <span class="text-secondary mb-1">Catatan / Keperluan</span>
                            <p class="text-dark mb-0" style="white-space: pre-line;">{{ $loan->notes ?: '-' }}</p>
                        </li>
                    </ul>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <a href="{{ route('loans.index') }}" class="btn btn-sm btn-outline-secondary mb-0">
                            Kembali
                        </a>

                        @if ($isAdmin)
                            <div class="d-flex flex-wrap gap-2">
                                @if ($loan->status === 'menunggu')
                                    <form method="POST" action="{{ route('loans.approve', $loan) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm bg-gradient-success mb-0">
                                            Setujui Peminjaman
                                        </button>
                                    </form>

                                    <button
                                        type="button"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'reject-loan-detail')"
                                        class="btn btn-sm bg-gradient-warning mb-0"
                                    >
                                        Tolak
                                    </button>

                                    <x-modal name="reject-loan-detail" maxWidth="sm">
                                        <form method="POST" action="{{ route('loans.reject', $loan) }}" class="text-start">
                                            @csrf
                                            @method('PATCH')

                                            <h6 class="font-weight-bolder mb-2">Tolak Pengajuan?</h6>
                                            <p class="text-sm text-secondary mb-3">
                                                Masukkan alasan penolakan (opsional) untuk peminjaman <strong>{{ $loan->item?->name }}</strong>.
                                            </p>

                                            <div class="mb-3">
                                                <x-input-label for="rejection_reason" value="Alasan Penolakan" />
                                                <input
                                                    id="rejection_reason"
                                                    type="text"
                                                    name="rejection_reason"
                                                    class="form-control form-control-sm"
                                                    placeholder="Contoh: Stok sedang dipakai ujian"
                                                >
                                            </div>

                                            <div class="d-flex justify-content-end gap-2">
                                                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                                                <x-danger-button>Tolak Pengajuan</x-danger-button>
                                            </div>
                                        </form>
                                    </x-modal>
                                @elseif (in_array($loan->status, ['dipinjam', 'terlambat'], true))
                                    <a href="{{ route('loans.return.create', $loan) }}" class="btn btn-sm bg-gradient-info mb-0">
                                        Catat Pengembalian
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0 font-weight-bolder">Status Pengembalian</h6>
                    <p class="text-xs text-secondary mb-0">Rincian pencatatan pengembalian barang</p>
                </div>

                <div class="card-body">
                    @if ($loan->returnLog)
                        @php
                            $returnConditionBadge = match ($loan->returnLog->condition_on_return) {
                                'baik' => 'success',
                                'rusak ringan' => 'warning',
                                'rusak berat' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                                <span class="text-secondary">Tanggal Dikembalikan</span>
                                <span class="font-weight-bold text-dark">
                                    {{ $loan->returnLog->returned_at?->translatedFormat('d M Y') }}
                                </span>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                                <span class="text-secondary">Kondisi Saat Kembali</span>
                                <span class="badge badge-sm bg-gradient-{{ $returnConditionBadge }}">
                                    {{ $loan->returnLog->condition_on_return }}
                                </span>
                            </li>
                            <li class="list-group-item px-0 d-flex flex-column text-sm">
                                <span class="text-secondary mb-1">Catatan Pengembalian (Admin)</span>
                                <p class="text-dark mb-0">{{ $loan->returnLog->notes ?: '-' }}</p>
                            </li>
                        </ul>
                    @else
                        <div class="p-3 bg-gray-100 border-radius-lg text-sm text-secondary">
                            @if ($loan->status === 'menunggu')
                                Pengajuan ini masih <strong>menunggu verifikasi Admin</strong>.
                            @elseif ($loan->status === 'ditolak')
                                Pengajuan peminjaman ini telah <strong>ditolak</strong>.
                            @else
                                Barang saat ini masih berstatus <strong>{{ $loan->status }}</strong> dan belum tercatat dikembalikan.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

