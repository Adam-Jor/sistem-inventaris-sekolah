<x-guest-layout title="Lupa Password" subtitle="Masukkan email Anda, kami kirimkan tautan untuk mengatur ulang password">
    <x-auth-session-status class="mb-3" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" value="Email" :required="true" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" placeholder="Email" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="text-center">
            <x-primary-button class="w-100 mt-3">Kirim Tautan Reset Password</x-primary-button>
        </div>
    </form>

    <x-slot name="footer">
        <p class="mb-4 text-sm mx-auto">
            <a href="{{ route('login') }}" class="text-info text-gradient font-weight-bold">Kembali ke halaman masuk</a>
        </p>
    </x-slot>
</x-guest-layout>
