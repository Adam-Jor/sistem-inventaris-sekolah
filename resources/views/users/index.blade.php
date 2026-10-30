<x-app-layout title="Manajemen User" :breadcrumbs="['Manajemen User' => null]">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div>
                            <h5 class="mb-0 font-weight-bolder">Daftar Akun Pengguna</h5>
                            <p class="text-sm text-secondary mb-0">
                                Kelola akun Admin dan User peminjam serta pengaturan hak akses sistem
                            </p>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <form method="GET" action="{{ route('users.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                                @if (($perPage ?? 10) !== 10)
                                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                                @endif

                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    class="form-control form-control-sm"
                                    placeholder="Cari nama atau email..."
                                    style="width: 210px;"
                                >

                                <select name="role" class="form-select form-select-sm" style="width: 150px;">
                                    <option value="">Semua Role</option>
                                    <option value="admin" @selected($role === 'admin')>Admin</option>
                                    <option value="user" @selected($role === 'user')>User</option>
                                </select>

                                <button type="submit" class="btn btn-sm bg-gradient-dark mb-0 px-3">Filter</button>

                                @if ($search !== '' || $role !== '')
                                    <a href="{{ route('users.index', ($perPage ?? 10) !== 10 ? ['per_page' => $perPage] : []) }}" class="btn btn-sm btn-outline-secondary mb-0 px-3">
                                        Reset
                                    </a>
                                @endif
                            </form>

                            <a href="{{ route('users.create') }}" class="btn btn-sm bg-gradient-primary mb-0">
                                + Tambah User
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body px-0 pt-3 pb-2">
                    <div class="table-responsive p-0">
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
                                                {{ $users->firstItem() + $index }}
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
                                                            <span class="badge badge-sm bg-gradient-info ms-1" style="font-size: 0.6rem;">Anda</span>
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
                                                {{ $user->loans_count }} Pinjaman
                                            </span>
                                            @if ($user->active_loans_count > 0)
                                                <div class="mt-1">
                                                    <span class="text-xxs text-warning font-weight-bold">
                                                        {{ $user->active_loans_count }} masih dipinjam
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

                                            @if (! $isCurrentUser)
                                                {{-- Modal Konfirmasi Hapus User (Alpine.js) --}}
                                                <x-modal name="delete-user-{{ $user->id }}" maxWidth="sm">
                                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="text-start">
                                                        @csrf
                                                        @method('DELETE')

                                                        <h6 class="font-weight-bolder mb-2">Hapus Akun Pengguna?</h6>
                                                        <p class="text-sm text-secondary mb-3 text-wrap">
                                                            Apakah Anda yakin ingin menghapus akun <strong>{{ $user->name }}</strong> ({{ $user->email }})?
                                                            @if ($user->active_loans_count > 0)
                                                                <span class="d-block text-danger text-xs mt-2">
                                                                    Perhatian: Pengguna ini masih memiliki {{ $user->active_loans_count }} peminjaman aktif yang belum dikembalikan dan tidak dapat dihapus.
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

                    <x-table-pagination :paginator="$users" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

