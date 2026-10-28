<section>
    <header class="mb-4">
        <h6 class="mb-1">Informasi Profil</h6>
        <p class="text-sm text-secondary mb-0">Perbarui nama dan alamat email akun Anda.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <x-input-label for="name" value="Nama" :required="true" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" placeholder="Nama lengkap" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" value="Email" :required="true" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" placeholder="Email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 mb-0">
                        Alamat email Anda belum terverifikasi.
                        <button form="send-verification" class="btn btn-link text-info text-sm p-0 mb-0 align-baseline">
                            Kirim ulang email verifikasi.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 mb-0 font-weight-bold text-sm text-success">
                            Tautan verifikasi baru sudah dikirim ke alamat email Anda.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center">
            <x-primary-button>Simpan</x-primary-button>

            @if (session('status') === 'profile-updated')
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
