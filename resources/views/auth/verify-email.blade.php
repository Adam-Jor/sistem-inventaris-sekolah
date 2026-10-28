<x-guest-layout title="Verifikasi Email" subtitle="Klik tautan yang kami kirim ke email Anda untuk memverifikasi akun. Belum menerima email? Kirim ulang di bawah.">
    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success text-white text-sm" role="alert">
            Tautan verifikasi baru sudah dikirim ke alamat email Anda.
        </div>
    @endif

    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2 mt-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-100 w-sm-auto">Kirim Ulang Email Verifikasi</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="btn btn-link text-secondary mb-0 px-0">Keluar</button>
        </form>
    </div>
</x-guest-layout>
