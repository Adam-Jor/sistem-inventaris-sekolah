<x-app-layout title="Edit User" :breadcrumbs="['Manajemen User' => route('users.index'), 'Edit User' => null]">
    @php
        $isCurrentUser = auth()->id() === $user->id;
    @endphp

    <div class="row">
        <div class="col-lg-8 col-md-10 col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0 font-weight-bolder">Edit Akun Pengguna</h5>
                    <p class="text-sm text-secondary mb-0">
                        Perbarui informasi profil, hak akses, atau password untuk <strong>{{ $user->name }}</strong>
                    </p>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="name" value="Nama Lengkap" :required="true" />
                                <x-text-input
                                    id="name"
                                    name="name"
                                    type="text"
                                    :value="old('name', $user->name)"
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
                                    :value="old('email', $user->email)"
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
                                    <option value="user" @selected(old('role', $user->role) === 'user') @disabled($isCurrentUser)>
                                        User (Guru / Siswa Peminjam)
                                    </option>
                                    <option value="admin" @selected(old('role', $user->role) === 'admin')>
                                        Admin (Pengelola Inventaris)
                                    </option>
                                </select>
                                @if ($isCurrentUser)
                                    <small class="text-xs text-secondary d-block mt-1">
                                        Anda sedang menggunakan akun ini sehingga peran Admin tidak dapat diturunkan.
                                    </small>
                                @endif
                                <x-input-error :messages="$errors->get('role')" />
                            </div>
                        </div>

                        <hr class="horizontal dark my-3">
                        <h6 class="text-sm font-weight-bolder mb-1">Ubah Password (Opsional)</h6>
                        <p class="text-xs text-secondary mb-3">
                            Biarkan kosong jika tidak ingin mengubah password akun ini.
                        </p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="password" value="Password Baru" />
                                <x-text-input
                                    id="password"
                                    name="password"
                                    type="password"
                                    placeholder="Minimal 8 karakter (opsional)"
                                    autocomplete="new-password"
                                />
                                <x-input-error :messages="$errors->get('password')" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
                                <x-text-input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    placeholder="Ulangi password baru"
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
                                Simpan Perubahan
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

