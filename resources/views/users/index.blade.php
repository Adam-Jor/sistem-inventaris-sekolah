<x-app-layout title="Manajemen User" :breadcrumbs="['Manajemen User' => null]">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-2 pb-md-0">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                        <div>
                            <h5 class="mb-0 font-weight-bolder">Daftar Akun Pengguna</h5>
                            <p class="text-sm text-secondary mb-0">
                                Kelola akun Admin dan User peminjam serta pengaturan hak akses sistem
                            </p>
                        </div>

                        <div class="w-100 w-md-auto">
                            <a href="{{ route('users.create') }}" class="btn btn-sm bg-gradient-primary mb-0 w-100 w-md-auto">
                                + Tambah User
                            </a>
                        </div>
                    </div>

                    @php
                        $baseQuery = ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : [];
                        $activeFilters = [];
                        if ($search !== '') {
                            $activeFilters[] = [
                                'label' => 'Pencarian',
                                'value' => $search,
                                'url' => route('users.index', array_filter($baseQuery + ['role' => $role], fn ($v) => $v !== '')),
                            ];
                        }
                        if ($role !== '') {
                            $activeFilters[] = [
                                'label' => 'Role',
                                'value' => ucfirst($role),
                                'url' => route('users.index', array_filter($baseQuery + ['search' => $search], fn ($v) => $v !== '')),
                            ];
                        }
                    @endphp

                    {{-- Form Pencarian & Filter Role --}}
                    <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-center modern-filter-bar mb-2">
                        @if (($perPage ?? 10) !== 10)
                            <input type="hidden" name="per_page" value="{{ $perPage }}">
                        @endif

                        <div class="col-12 col-md-6 col-lg-6">
                            <x-search-input
                                name="search"
                                :value="$search"
                                placeholder="Cari nama atau email pengguna..."
                            />
                        </div>

                        <div class="col-12 col-sm-7 col-md-4 col-lg-4">
                            <select name="role" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()" class="form-select form-select-sm bg-white">
                                <option value="">Semua Role</option>
                                <option value="admin" @selected($role === 'admin')>Admin</option>
                                <option value="user" @selected($role === 'user')>User</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-5 col-md-2 col-lg-2 d-flex gap-2">
                            <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 w-100">Filter</button>

                            @if ($search !== '' || $role !== '')
                                <a href="{{ route('users.index', $baseQuery) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    <x-filter-chips :filters="$activeFilters" :resetUrl="route('users.index', $baseQuery)" />
                </div>

                <div class="card-body px-0 pt-2 pb-2">
                    <x-table-toolbar :paginator="$users" />

                    {{-- Tampilan Tabel Desktop (>= 768px) --}}
                    <div class="table-responsive p-0 d-none d-md-block">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4" style="width: 60px;">No</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Pengguna</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Email</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Role</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Riwayat Pinjam</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Terdaftar</th>
                                    <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $index => $user)
                                    @php
                                        $isCurrentUser = auth()->id() === $user->id;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="text-xs font-weight-bold text-secondary">
                                                {{ number_format($users->firstItem() + $index, 0, ',', '.') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div
                                                    class="avatar avatar-sm rounded-circle bg-gradient-{{ $user->isAdmin() ? 'primary' : 'dark' }} d-inline-flex align-items-center justify-content-center text-white font-weight-bolder text-xs flex-shrink-0"
                                                    style="width: 34px; height: 34px;"
                                                >
                                                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 text-sm d-flex align-items-center gap-1">
                                                        <span>{{ $user->name }}</span>
                                                        @if ($isCurrentUser)
                                                            <span class="badge badge-xs bg-gradient-info ms-1">Anda</span>
                                                        @endif
                                                    </h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs font-weight-bold text-dark">{{ $user->email }}</span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-{{ $user->isAdmin() ? 'primary' : 'secondary' }}">
                                                {{ $user->isAdmin() ? 'Admin' : 'User' }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-{{ $user->loans_count > 0 ? 'info' : 'secondary' }}">
                                                {{ number_format($user->loans_count, 0, ',', '.') }} Pinjaman
                                            </span>
                                            @if ($user->active_loans_count > 0)
                                                <div class="mt-1">
                                                    <span class="text-xxs text-warning font-weight-bold">
                                                        {{ number_format($user->active_loans_count, 0, ',', '.') }} masih dipinjam
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">
                                                {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <a href="{{ route('users.edit', $user) }}" class="btn btn-xs btn-outline-dark mb-0 px-3 py-1">
                                                    Edit
                                                </a>

                                                @if (! $isCurrentUser)
                                                    <button
                                                        type="button"
                                                        x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'delete-user-{{ $user->id }}')"
                                                        class="btn btn-xs bg-gradient-danger mb-0 px-3 py-1"
                                                    >
                                                        Hapus
                                                    </button>
                                                @else
                                                    <button
                                                        type="button"
                                                        class="btn btn-xs btn-outline-secondary mb-0 px-3 py-1 opacity-5"
                                                        disabled
                                                        title="Anda tidak dapat menghapus akun Anda sendiri"
                                                    >
                                                        Hapus
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <p class="text-sm text-secondary mb-2">
                                                {{ ($search !== '' || $role !== '') ? 'Tidak ada akun pengguna yang cocok dengan filter pencarian.' : 'Belum ada data pengguna.' }}
                                            </p>
                                            @if ($search === '' && $role === '')
                                                <a href="{{ route('users.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                                    + Tambah User Pertama
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
                        @forelse ($users as $user)
                            @php
                                $isCurrentUser = auth()->id() === $user->id;
                            @endphp
                            <div class="mobile-row-card">
                                <div class="mobile-row-header">
                                    <div class="mobile-row-header-main">
                                        <span class="mobile-row-caption">Nama Pengguna</span>
                                        <h6 class="mobile-row-title d-flex align-items-center flex-wrap gap-1">
                                            <span>{{ $user->name }}</span>
                                            @if ($isCurrentUser)
                                                <span class="badge badge-xs bg-gradient-info">Anda</span>
                                            @endif
                                        </h6>
                                    </div>
                                    <span class="badge badge-sm bg-gradient-{{ $user->isAdmin() ? 'primary' : 'secondary' }} flex-shrink-0">
                                        {{ $user->isAdmin() ? 'Admin' : 'User' }}
                                    </span>
                                </div>

                                <div class="mobile-row-meta">
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Email</span>
                                        <span class="mobile-field-value">{{ $user->email }}</span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Riwayat Pinjam</span>
                                        <span class="mobile-field-value">
                                            {{ number_format($user->loans_count, 0, ',', '.') }} Pinjaman
                                            @if ($user->active_loans_count > 0)
                                                <span class="text-warning d-block text-xs">
                                                    ({{ number_format($user->active_loans_count, 0, ',', '.') }} masih dipinjam)
                                                </span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="mobile-row-field">
                                        <span class="mobile-field-label">Terdaftar</span>
                                        <span class="mobile-field-value">{{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
                                    </div>
                                </div>

                                <div class="mobile-row-actions">
                                    <a href="{{ route('users.edit', $user) }}" class="btn mobile-btn-detail">
                                        Edit Pengguna
                                    </a>

                                    @if (! $isCurrentUser)
                                        <button
                                            type="button"
                                            x-data=""
                                            x-on:click.prevent="$dispatch('open-modal', 'delete-user-{{ $user->id }}')"
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
                                    {{ ($search !== '' || $role !== '') ? 'Tidak ada akun pengguna yang cocok dengan filter pencarian.' : 'Belum ada data pengguna.' }}
                                </p>
                                @if ($search === '' && $role === '')
                                    <a href="{{ route('users.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                        + Tambah User Pertama
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>

                    {{-- Modal Konfirmasi Hapus User (dipakai bersama oleh Desktop & Mobile) --}}
                    @foreach ($users as $user)
                        @if (auth()->id() !== $user->id)
                            <x-modal name="delete-user-{{ $user->id }}" maxWidth="sm">
                                <form method="POST" action="{{ route('users.destroy', $user) }}" class="text-start">
                                    @csrf
                                    @method('DELETE')

                                    <h6 class="font-weight-bolder mb-2">Hapus Akun Pengguna?</h6>
                                    <p class="text-sm text-secondary mb-3 text-wrap">
                                        Apakah Anda yakin ingin menghapus akun <strong>{{ $user->name }}</strong> ({{ $user->email }})?
                                        @if ($user->active_loans_count > 0)
                                            <span class="d-block text-danger text-xs mt-2">
                                                Perhatian: Pengguna ini masih memiliki {{ number_format($user->active_loans_count, 0, ',', '.') }} peminjaman aktif yang belum dikembalikan dan tidak dapat dihapus.
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
                    @endforeach

                    <x-table-pagination :paginator="$users" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
