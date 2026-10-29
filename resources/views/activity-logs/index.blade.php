@php
    $hasFilters = $search !== '' || ($userId !== '' && $userId !== null) || $action !== '';

    $actionLabels = [
        'login' => 'Login',
        'logout' => 'Logout',
        'register' => 'Registrasi Akun',
        'create_category' => 'Tambah Kategori',
        'update_category' => 'Ubah Kategori',
        'delete_category' => 'Hapus Kategori',
        'create_item' => 'Tambah Barang',
        'update_item' => 'Ubah Barang',
        'delete_item' => 'Hapus Barang',
        'request_loan' => 'Ajukan Peminjaman',
        'verify_loan' => 'Setujui Peminjaman',
        'reject_loan' => 'Tolak Peminjaman',
        'return_loan' => 'Catat Pengembalian',
        'delete_loan' => 'Hapus Peminjaman',
        'create_user' => 'Tambah User',
        'update_user' => 'Ubah User',
        'delete_user' => 'Hapus User',
    ];
@endphp

<x-app-layout title="Activity Log" :breadcrumbs="['Activity Log' => null]">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-0 font-weight-bolder">Riwayat Aktivitas Sistem (Audit Trail)</h5>
                            <p class="text-sm text-secondary mb-0">
                                Jejak audit seluruh aktivitas penting di sistem inventaris (bersifat <em>read-only</em>)
                            </p>
                        </div>
                        <span class="badge badge-sm bg-gradient-secondary align-self-start align-self-md-center">
                            Read-Only Log
                        </span>
                    </div>

                    {{-- Form Pencarian & Filter User / Aksi --}}
                    <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-2 align-items-center bg-gray-100 p-2 border-radius-lg mb-2">
                        @if (($perPage ?? 10) !== 10)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                        @endif

                        <div class="col-lg-4 col-md-6 col-12">
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                class="form-control form-control-sm bg-white"
                                placeholder="Cari deskripsi aktivitas, aksi, atau IP..."
                            >
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
                            <select name="user_id" class="form-select form-select-sm bg-white">
                                <option value="">Semua Pengguna</option>
                                @foreach ($users as $userOption)
                                    <option value="{{ $userOption->id }}" @selected((string) $userId === (string) $userOption->id)>
                                        {{ $userOption->name }} ({{ $userOption->role }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-6 col-12">
                            <select name="action" class="form-select form-select-sm bg-white">
                                <option value="">Semua Jenis Aksi</option>
                                @foreach ($actions as $actionOption)
                                    <option value="{{ $actionOption }}" @selected($action === $actionOption)>
                                        {{ $actionLabels[$actionOption] ?? $actionOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-12 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100">Filter</button>
                            @if ($hasFilters)
                                <a href="{{ route('activity-logs.index', ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : []) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">Reset</a>
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Waktu</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pelaku</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Deskripsi Aktivitas</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Alamat IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $index => $log)
                                    @php
                                        $badgeColor = match (true) {
                                            str_starts_with($log->action, 'create_'), $log->action === 'verify_loan', $log->action === 'return_loan' => 'success',
                                            str_starts_with($log->action, 'update_'), $log->action === 'request_loan' => 'info',
                                            str_starts_with($log->action, 'delete_'), $log->action === 'reject_loan' => 'danger',
                                            $log->action === 'login', $log->action === 'register' => 'primary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold text-secondary">
                                                {{ $logs->firstItem() + $index }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-weight-bold text-dark d-block">
                                                {{ $log->created_at?->translatedFormat('d M Y') }}
                                            </span>
                                            <span class="text-xs text-secondary">
                                                {{ $log->created_at?->format('H:i:s') }} WIB
                                            </span>
                                        </td>
                                        <td>
                                            @if ($log->user)
                                                <div class="d-flex flex-column">
                                                    <h6 class="mb-0 text-sm">{{ $log->user->name }}</h6>
                                                    <span class="text-xs text-secondary">{{ $log->user->email }}</span>
                                                </div>
                                            @else
                                                <span class="text-xs text-secondary font-italic">Sistem / User Terhapus</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge badge-sm bg-gradient-{{ $badgeColor }}">
                                                {{ $actionLabels[$log->action] ?? $log->action }}
                                            </span>
                                        </td>
                                        <td>
                                            <p class="text-xs text-dark font-weight-bold mb-0 text-wrap" style="max-width: 420px;">
                                                {{ $log->description }}
                                            </p>
                                        </td>
                                        <td class="align-middle text-center pe-4">
                                            <span class="text-xs text-secondary font-weight-bold">
                                                {{ $log->ip_address ?: '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <p class="text-sm text-secondary mb-0">
                                                {{ $hasFilters ? 'Tidak ada activity log yang cocok dengan filter Anda.' : 'Belum ada data aktivitas yang tercatat.' }}
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-table-pagination :paginator="$logs" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

