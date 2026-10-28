<x-guest-layout title="Selamat Datang" subtitle="Masukkan email dan password untuk masuk">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" value="Email" :required="true" />
            <x-text-input id="email" type="email" inputmode="email" name="email" :value="old('email')" placeholder="nama@sekolah.test" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Password" :required="true" />
            <x-text-input id="password" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="form-check form-switch mb-0 d-flex align-items-center">
                <input class="form-check-input mt-0" type="checkbox" id="remember_me" name="remember">
                <label class="form-check-label mb-0 ms-2 text-sm cursor-pointer" for="remember_me">Ingat saya</label>
            </div>

            @if (Route::has('password.request'))
                <a class="text-sm text-secondary font-weight-bold" href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <div class="text-center">
            <x-primary-button class="w-100 mt-4 auth-submit-btn">Masuk</x-primary-button>
        </div>
    </form>

    <x-slot name="footer">
        <p class="mb-3 mb-md-4 text-sm mx-auto">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-info text-gradient font-weight-bold">Daftar</a>
        </p>
    </x-slot>
</x-guest-layout>
