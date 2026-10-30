@php
    $isAdmin = auth()->user()->isAdmin();
    $listLabel = $isAdmin ? 'Data Barang' : 'Daftar Barang';
    $conditionBadge = match ($item->condition) {
        'baik' => 'success',
        'rusak ringan' => 'warning',
        'rusak berat' => 'danger',
        default => 'secondary',
    };
@endphp

<x-app-layout :title="'Detail: '.$item->name" :breadcrumbs="[$listLabel => route('items.index'), $item->name => null]">
    <div class="row">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 font-weight-bolder">{{ $item->name }}</h5>
                        <span class="text-xs text-secondary font-weight-bold">Kode: {{ $item->code }}</span>
                    </div>
                    <span class="badge badge-sm bg-gradient-{{ $conditionBadge }}">
                        {{ ucwords($item->condition) }}
                    </span>
                </div>

                <div class="card-body">
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Kategori</span>
                            <span class="font-weight-bold text-dark">{{ $item->category?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Stok Tersedia</span>
                            <span class="badge badge-sm bg-gradient-{{ $item->stock > 0 ? 'info' : 'secondary' }}">
                                {{ number_format($item->stock, 0, ',', '.') }} unit
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Status Peminjaman</span>
                            @if ($item->isAvailable())
                                <span class="text-success font-weight-bold text-xs">Siap Dipinjam</span>
                            @else
                                <span class="text-danger font-weight-bold text-xs">Tidak Tersedia untuk Dipinjam</span>
                            @endif
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Terakhir Diperbarui</span>
                            <span class="text-dark text-xs font-weight-bold">
                                {{ $item->updated_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </span>
                        </li>
                    </ul>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <a href="{{ route('items.index') }}" class="btn btn-sm btn-outline-secondary mb-0">
                            Kembali
                        </a>

                        <div class="d-flex gap-2">
                            @if ($isAdmin)
                                <a href="{{ route('items.edit', $item) }}" class="btn btn-sm bg-gradient-dark mb-0">
                                    Edit Barang
                                </a>
                            @elseif ($item->isAvailable())
                                <a href="{{ route('loans.create', ['item_id' => $item->id]) }}" class="btn btn-sm bg-gradient-primary mb-0">
                                    Ajukan Peminjaman
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">
                        {{ $isAdmin ? 'Riwayat Peminjaman Barang Ini' : 'Riwayat Peminjaman Saya untuk Barang Ini' }}
                    </h6>
                    <p class="text-xs text-secondary mb-0">5 transaksi peminjaman terakhir</p>
                </div>

                <div class="card-body px-0 pt-2 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    @if ($isAdmin)
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Peminjam</th>
                                    @endif
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jumlah</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pinjam</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Jatuh Tempo</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentLoans as $loan)
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
                                        @if ($isAdmin)
                                            <td class="ps-4">
                                                <span class="text-xs font-weight-bold text-dark">{{ $loan->user?->name ?? '-' }}</span>
                                            </td>
                                        @endif
                                        <td class="align-middle text-center">
                                            <span class="text-xs font-weight-bold">{{ number_format($loan->quantity, 0, ',', '.') }} unit</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs">{{ $loan->loan_date?->translatedFormat('d M Y') }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs">{{ $loan->due_date?->translatedFormat('d M Y') }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-{{ $badgeColor }}">{{ ucfirst($loan->status) }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $isAdmin ? 5 : 4 }}" class="text-center py-4 text-sm text-secondary">
                                            Belum ada riwayat peminjaman untuk barang ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

