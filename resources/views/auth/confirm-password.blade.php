<x-guest-layout title="Konfirmasi Password" subtitle="Ini area aman. Masukkan password Anda untuk melanjutkan">
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="password" value="Password" :required="true" />
            <x-text-input id="password" type="password" name="password" placeholder="Password" required autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="text-center">
            <x-primary-button class="w-100 mt-3">Konfirmasi</x-primary-button>
        </div>
    </form>
</x-guest-layout>
