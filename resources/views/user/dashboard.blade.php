<x-app-layout title="Dashboard" :breadcrumbs="['Dashboard' => null]">
    {{-- Banner Sambutan & Aksi Cepat --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="font-weight-bolder mb-1">Selamat datang, {{ auth()->user()->name }}</h4>
                        <p class="text-sm text-secondary mb-0">
                            Anda masuk sebagai <span class="badge badge-sm bg-gradient-info">User</span>.
                            Lihat ketersediaan barang sekolah, ajukan peminjaman baru, dan pantau jadwal pengembalian Anda.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                        <a href="{{ route('items.index') }}" class="btn bg-gradient-info mb-0">Lihat Barang</a>
                        <a href="{{ route('loans.create') }}" class="btn btn-outline-info mb-0">Ajukan Peminjaman</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kartu Statistik Peminjaman User (Gaya Soft UI Dashboard 3) --}}
    <div class="row">
        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100">
                <span class="mask bg-info opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-box-2 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ $stats['active_loans'] }}</h4>
                    <span class="text-white text-sm">Sedang Dipinjam</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100">
                <span class="mask bg-warning opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-bell-55 text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ $stats['waiting_loans'] }}</h4>
                    <span class="text-white text-sm">Menunggu Verifikasi</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100">
                <span class="mask bg-danger opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-time-alarm text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ $stats['overdue_loans'] }}</h4>
                    <span class="text-white text-sm">Terlambat Kembali</span>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6 mb-4">
            <div class="card h-100">
                <span class="mask bg-dark opacity-10 border-radius-lg"></span>
                <div class="card-body p-3 position-relative">
                    <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                        <i class="ni ni-check-bold text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <h4 class="text-white font-weight-bolder mb-0 mt-3">{{ $stats['returned_loans'] }}</h4>
                    <span class="text-white text-sm">Selesai Dikembalikan</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Jatuh Tempo Terdekat & Riwayat Pengajuan Milik User --}}
    <div class="row">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6 class="mb-0">Jatuh Tempo Terdekat</h6>
                    <p class="text-xs text-secondary mb-0">Barang yang sedang Anda pinjam & batas waktu pengembalian</p>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group">
                        @forelse ($nearestDueLoans as $loan)
                            @php
                                $isOverdue = $loan->isOverdue();
                            @endphp
                            <li class="list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg">
                                <div class="d-flex align-items-center">
                                    <div class="icon icon-shape icon-sm me-3 bg-gradient-{{ $isOverdue ? 'danger' : 'info' }} shadow text-center border-radius-md d-flex align-items-center justify-content-center">
                                        <i class="ni ni-box-2 text-white opacity-10"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <h6 class="mb-1 text-dark text-sm">{{ $loan->item?->name ?? '-' }}</h6>
                                        <span class="text-xs text-secondary">
                                            {{ $loan->quantity }} unit &bull; Batas: <strong>{{ $loan->due_date?->translatedFormat('d M Y') }}</strong>
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-sm bg-gradient-{{ $isOverdue ? 'danger' : 'info' }}">
                                        {{ $isOverdue ? 'Terlambat' : 'Dipinjam' }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item border-0 ps-0 text-sm text-secondary">
                                Tidak ada tanggungan peminjaman aktif saat ini.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0">Status Pengajuan & Peminjaman Saya</h6>
                        <p class="text-xs text-secondary mb-0">5 riwayat peminjaman terakhir Anda</p>
                    </div>
                    <a href="{{ route('loans.index') }}" class="btn btn-sm btn-outline-secondary mb-0">Lihat Semua</a>
                </div>
                <div class="card-body px-0 pt-2 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Barang</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pinjam</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Batas Kembali</th>
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
                                        <td>
                                            <div class="d-flex px-3 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $loan->item?->name ?? '-' }}</h6>
                                                    <p class="text-xs text-secondary mb-0">{{ $loan->quantity }} unit &bull; {{ $loan->item?->code }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $loan->loan_date?->translatedFormat('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $loan->due_date?->translatedFormat('d M Y') }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-{{ $badgeColor }}">{{ $loan->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-sm text-secondary">
                                            Anda belum pernah mengajukan peminjaman.
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

