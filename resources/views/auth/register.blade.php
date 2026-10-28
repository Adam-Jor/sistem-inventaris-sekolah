<x-guest-layout title="Buat Akun" subtitle="Daftar untuk mulai meminjam barang sekolah">
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="name" value="Nama" :required="true" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" placeholder="Nama lengkap" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" value="Email" :required="true" />
            <x-text-input id="email" type="email" inputmode="email" name="email" :value="old('email')" placeholder="nama@sekolah.test" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Password" :required="true" />
            <x-text-input id="password" type="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" :required="true" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ulangi password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="text-center">
            <x-primary-button class="w-100 mt-3 auth-submit-btn">Daftar</x-primary-button>
        </div>
    </form>

    <x-slot name="footer">
        <p class="mb-3 mb-md-4 text-sm mx-auto">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-info text-gradient font-weight-bold">Masuk</a>
        </p>
    </x-slot>
</x-guest-layout>
