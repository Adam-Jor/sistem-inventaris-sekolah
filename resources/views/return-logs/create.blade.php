<x-app-layout title="Catat Pengembalian" :breadcrumbs="['Data Pengembalian' => route('return-logs.index'), 'Catat Pengembalian' => null]">
    <div class="row">
        {{-- Ringkasan Peminjaman yang Dikembalikan --}}
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-0 font-weight-bolder">Ringkasan Peminjaman</h6>
                        <p class="text-xs text-secondary mb-0">Detail barang yang akan dikembalikan</p>
                    </div>
                    <span class="badge badge-sm bg-gradient-{{ $loan->status === 'terlambat' ? 'danger' : 'info' }}">
                        {{ ucfirst($loan->status) }}
                    </span>
                </div>

                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Peminjam</span>
                            <span class="font-weight-bold text-dark">{{ $loan->user?->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Barang</span>
                            <span class="font-weight-bold text-dark">
                                {{ $loan->item?->name ?? '-' }} ({{ $loan->item?->code }})
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Jumlah Dipinjam</span>
                            <span class="badge badge-sm bg-gradient-dark">{{ number_format($loan->quantity, 0, ',', '.') }} unit</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Tanggal Pinjam</span>
                            <span class="font-weight-bold text-dark">{{ $loan->loan_date?->translatedFormat('d M Y') }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center text-sm">
                            <span class="text-secondary">Batas Kembali</span>
                            <span class="font-weight-bold {{ $loan->status === 'terlambat' ? 'text-danger' : 'text-dark' }}">
                                {{ $loan->due_date?->translatedFormat('d M Y') }}
                            </span>
                        </li>
                        <li class="list-group-item px-0 d-flex flex-column text-sm">
                            <span class="text-secondary mb-1">Catatan Peminjam</span>
                            <p class="text-dark mb-0">{{ $loan->notes ?: '-' }}</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Formulir Pencatatan Pengembalian --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h5 class="mb-0 font-weight-bolder">Formulir Pengembalian Barang</h5>
                    <p class="text-sm text-secondary mb-0">
                        Periksa fisik barang dan catat kondisi saat dikembalikan. Stok akan otomatis bertambah kembali sebanyak <strong>{{ $loan->quantity }} unit</strong>.
                    </p>
                </div>

                <div class="card-body">
                    <form
                        method="POST"
                        action="{{ route('loans.return.store', $loan) }}"
                        x-data="{ condition: @js(old('condition_on_return', 'baik')) }"
                    >
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <x-input-label for="returned_at" value="Tanggal Pengembalian" :required="true" />
                                <x-text-input
                                    id="returned_at"
                                    name="returned_at"
                                    type="date"
                                    min="{{ $loan->loan_date?->toDateString() }}"
                                    :value="old('returned_at', now()->toDateString())"
                                    placeholder="Pilih tanggal pengembalian"
                                    required
                                    autofocus
                                />
                                <x-input-error :messages="$errors->get('returned_at')" />
                            </div>

                            <div class="col-md-6 mb-3">
                                <x-input-label for="condition_on_return" value="Kondisi Saat Dikembalikan" :required="true" />
                                <select
                                    id="condition_on_return"
                                    name="condition_on_return"
                                    x-model="condition"
                                    class="form-select @error('condition_on_return') is-invalid @enderror"
                                    required
                                >
                                    <option value="baik">Baik</option>
                                    <option value="rusak ringan">Rusak Ringan</option>
                                    <option value="rusak berat">Rusak Berat</option>
                                </select>
                                <x-input-error :messages="$errors->get('condition_on_return')" />
                            </div>
                        </div>

                        <template x-if="condition !== 'baik'">
                            <div class="alert alert-warning text-white text-xs py-2 px-3 mb-3" role="alert">
                                <strong>Perhatian:</strong> Karena barang dikembalikan dalam kondisi <span x-text="condition" class="font-weight-bolder"></span>, status kondisi barang di data master inventaris akan otomatis diperbarui menjadi <span x-text="condition" class="font-weight-bolder"></span>.
                            </div>
                        </template>

                        <div class="mb-4">
                            <x-input-label for="notes" value="Catatan Pemeriksaan Admin" />
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="form-control @error('notes') is-invalid @enderror"
                                placeholder="Contoh: Barang dikembalikan lengkap bersama kabel HDMI dan remote (opsional)"
                            >{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" />
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <a href="{{ route('return-logs.index') }}" class="btn btn-outline-secondary mb-0">
                                Batal
                            </a>
                            <x-primary-button class="bg-gradient-primary">
                                Simpan Pengembalian
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

