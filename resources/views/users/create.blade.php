<x-app-layout title="Tambah User" :breadcrumbs="['Manajemen User' => route('users.index'), 'Tambah User' => null]">
    <div class="row">
        <div class="col-lg-8 col-md-10 col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0 font-weight-bolder">Tambah Akun Pengguna Baru</h5>
                    <p class="text-sm text-secondary mb-0">
                        Buat akun baru untuk pengelola inventaris (Admin) atau peminjam (User)
                    </p>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="name" value="Nama Lengkap" :required="true" />
                                <x-text-input
                                    id="name"
                                    name="name"
                                    type="text"
                                    :value="old('name')"
                                    placeholder="Contoh: Budi Santoso"
                                    required
                                    autofocus
                                />
                                <x-input-error :messages="$errors->get('name')" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="email" value="Alamat Email" :required="true" />
                                <x-text-input
                                    id="email"
                                    name="email"
                                    type="email"
                                    :value="old('email')"
                                    placeholder="Contoh: budi@sekolah.test"
                                    required
                                />
                                <x-input-error :messages="$errors->get('email')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="role" value="Peran (Role)" :required="true" />
                                <select
                                    id="role"
                                    name="role"
                                    class="form-select @error('role') is-invalid @enderror"
                                    required
                                >
                                    <option value="user" @selected(old('role', 'user') === 'user')>User (Guru / Siswa Peminjam)</option>
                                    <option value="admin" @selected(old('role') === 'admin')>Admin (Pengelola Inventaris)</option>
                                </select>
                                <x-input-error :messages="$errors->get('role')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="password" value="Password" :required="true" />
                                <x-text-input
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="Minimal 8 karakter"
                                    required
                                    autocomplete="new-password"
                                />
                                <x-input-error :messages="$errors->get('password')" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="password_confirmation" value="Konfirmasi Password" :required="true" />
                                <x-text-input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    placeholder="Ulangi password"
                                    required
                                    autocomplete="new-password"
                                />
                                <x-input-error :messages="$errors->get('password_confirmation')" />
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary mb-0">
                                Batal
                            </a>
                            <x-primary-button class="bg-gradient-primary">
                                Simpan User
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

