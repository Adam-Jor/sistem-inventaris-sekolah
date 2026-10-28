<section>
    <header class="mb-4">
        <h6 class="mb-1 text-danger">Hapus Akun</h6>
        <p class="text-sm text-secondary mb-0">
            Setelah akun dihapus, seluruh data dan sumber dayanya akan dihapus permanen.
            Simpan data yang ingin Anda pertahankan sebelum menghapus akun.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()">
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <h6 class="mb-1">Yakin ingin menghapus akun Anda?</h6>
            <p class="text-sm text-secondary">
                Seluruh data akan dihapus permanen. Masukkan password Anda untuk mengonfirmasi.
            </p>

            <div class="mb-3">
                <x-input-label for="delete_account_password" value="Password" class="visually-hidden" />
                <x-text-input id="delete_account_password" name="password" type="password" placeholder="Password" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="d-flex justify-content-end">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-danger-button class="ms-2">Hapus Akun</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
