<x-guest-layout title="Atur Ulang Password" subtitle="Buat password baru untuk akun Anda">
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <x-input-label for="email" value="Email" :required="true" />
            <x-text-input id="email" type="email" name="email" :value="old('email', $request->email)" placeholder="Email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Password Baru" :required="true" />
            <x-text-input id="password" type="password" name="password" placeholder="Password baru" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password_confirmation" value="Konfirmasi Password" :required="true" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ulangi password baru" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="text-center">
            <x-primary-button class="w-100 mt-3">Atur Ulang Password</x-primary-button>
        </div>
    </form>
</x-guest-layout>
