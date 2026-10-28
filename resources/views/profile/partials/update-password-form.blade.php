<section>
    <header class="mb-4">
        <h6 class="mb-1">Ubah Password</h6>
        <p class="text-sm text-secondary mb-0">Gunakan password yang panjang dan acak agar akun Anda tetap aman.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" value="Password Saat Ini" :required="true" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" placeholder="Password saat ini" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" value="Password Baru" :required="true" />
            <x-text-input id="update_password_password" name="password" type="password" placeholder="Password baru" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password_confirmation" value="Konfirmasi Password" :required="true" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password baru" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="d-flex align-items-center">
            <x-primary-button>Simpan</x-primary-button>

            @if (session('status') === 'password-updated')
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-success ms-3"
                >Tersimpan.</span>
            @endif
        </div>
    </form>
</section>
