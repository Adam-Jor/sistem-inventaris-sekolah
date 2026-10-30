@php
    $conditionBadge = match ($returnLog->condition_on_return) {
        'baik' => 'success',
        'rusak ringan' => 'warning',
        'rusak berat' => 'danger',
        default => 'secondary',
    };
    $loan = $returnLog->loan;
@endphp

<x-app-layout title="Detail Pengembalian" :breadcrumbs="['Data Pengembalian' => route('return-logs.index'), 'Detail Pengembalian' => null]">
    <div class="row">
        <div class="col-lg-6 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 font-weight-bolder">Catatan Pengembalian</h5>
                        <p class="text-xs text-secondary mb-0">
                            Dicatat pada {{ $returnLog->created_at?->translatedFormat('d M Y, H:i') }}
                        </p>
                    </div>
                    <span class="badge badge-sm bg-gradient-{{ $conditionBadge }}">
                        {{ ucwords($returnLog->condition_on_return) }}
                    </span>
                </div>

                <div class="card-body">
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Tanggal Dikembalikan</span>
                            <span class="font-weight-bold text-dark">
                                {{ $returnLog->returned_at?->translatedFormat('d M Y') }}
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Kondisi Saat Kembali</span>
                            <span class="badge badge-sm bg-gradient-{{ $conditionBadge }}">
                                {{ ucwords($returnLog->condition_on_return) }}
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex flex-column text-sm">
                            <span class="text-secondary mb-1">Catatan Pemeriksaan Admin</span>
                            <p class="text-dark mb-0">{{ $returnLog->notes ?: '-' }}</p>
                        </li>
                    </ul>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('return-logs.index') }}" class="btn btn-sm btn-outline-secondary mb-0">
                            Kembali ke Daftar Pengembalian
                        </a>
                        @if ($loan)
                            <a href="{{ route('loans.show', $loan) }}" class="btn btn-sm bg-gradient-dark mb-0">
                                Lihat Detail Peminjaman
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0 font-weight-bolder">Data Peminjaman Terkait</h6>
                    <p class="text-xs text-secondary mb-0">Informasi peminjam dan barang yang dikembalikan</p>
                </div>

                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Peminjam</span>
                            <span class="font-weight-bold text-dark">
                                {{ $loan?->user?->name ?? '-' }}
                                <span class="text-xs text-secondary">({{ $loan?->user?->email }})</span>
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Barang</span>
                            <span class="font-weight-bold text-dark">
                                {{ $loan?->item?->name ?? '-' }}
                                <span class="text-xs text-secondary">({{ $loan?->item?->code }})</span>
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Kategori</span>
                            <span class="font-weight-bold text-dark">{{ $loan?->item?->category?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Jumlah Dikembalikan</span>
                            <span class="badge badge-sm bg-gradient-info">{{ number_format($loan?->quantity ?? 0, 0, ',', '.') }} unit</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Periode Peminjaman</span>
                            <span class="text-dark text-xs font-weight-bold">
                                {{ $loan?->loan_date?->translatedFormat('d M Y') }} &ndash; {{ $loan?->due_date?->translatedFormat('d M Y') }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

