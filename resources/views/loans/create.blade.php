@php
    $isAdmin = auth()->user()->isAdmin();
    $listLabel = $isAdmin ? 'Data Peminjaman' : 'Peminjaman Saya';
    $stockMap = $items->pluck('stock', 'id');
    $initialItemId = (string) old('item_id', $selectedItemId);
@endphp

<x-app-layout title="Ajukan Peminjaman" :breadcrumbs="[$listLabel => route('loans.index'), 'Ajukan Peminjaman' => null]">
    <div class="row">
        <div class="col-lg-8 col-md-10 col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0 font-weight-bolder">Formulir Pengajuan Peminjaman</h5>
                    <p class="text-sm text-secondary mb-0">
                        Pilih barang yang tersedia, tentukan jumlah serta jadwal peminjaman dan pengembalian
                    </p>
                </div>

                <div class="card-body">
                    @if ($items->isEmpty())
                        <div class="alert alert-warning text-white text-sm mb-3" role="alert">
                            Saat ini belum ada barang dengan stok tersedia dan kondisi baik yang dapat dipinjam.
                        </div>
                        <a href="{{ route('items.index') }}" class="btn btn-outline-secondary mb-0">Lihat Daftar Barang</a>
                    @else
                        <form
                            method="POST"
                            action="{{ route('loans.store') }}"
                            x-data="{
                                stocks: @js($stockMap),
                                selectedItem: @js($initialItemId),
                                get maxStock() {
                                    return this.selectedItem && this.stocks[this.selectedItem] ? this.stocks[this.selectedItem] : null;
                                }
                            }"
                        >
                            @csrf

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <x-input-label for="item_id" value="Pilih Barang" :required="true" />
                                    <select
                                        id="item_id"
                                        name="item_id"
                                        x-model="selectedItem"
                                        class="form-select @error('item_id') is-invalid @enderror"
                                        required
                                        autofocus
                                    >
                                        <option value="" disabled>-- Pilih Barang yang Akan Dipinjam --</option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}" @selected($initialItemId === (string) $item->id)>
                                                {{ $item->name }} ({{ $item->code }}) &mdash; Stok: {{ $item->stock }} unit [{{ $item->category?->name }}]
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('item_id')" />
                                </div>

                                <div class="col-md-4 mb-3">
                                    <x-input-label for="quantity" value="Jumlah Pinjam" :required="true" />
                                    <x-text-input
                                        id="quantity"
                                        name="quantity"
                                        type="number"
                                        min="1"
                                        x-bind:max="maxStock"
                                        :value="old('quantity', 1)"
                                        placeholder="Masukkan jumlah unit"
                                        required
                                    />
                                    <template x-if="maxStock !== null">
                                        <small class="text-xs text-secondary d-block mt-1">
                                            Maksimal tersedia: <strong x-text="maxStock"></strong> unit
                                        </small>
                                    </template>
                                    <x-input-error :messages="$errors->get('quantity')" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <x-input-label for="loan_date" value="Tanggal Pinjam" :required="true" />
                                    <x-text-input
                                        id="loan_date"
                                        name="loan_date"
                                        type="date"
                                        :value="old('loan_date', now()->toDateString())"
                                        placeholder="Pilih tanggal pinjam"
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('loan_date')" />
                                </div>

                                <div class="col-md-6 mb-3">
                                    <x-input-label for="due_date" value="Batas Tanggal Pengembalian" :required="true" />
                                    <x-text-input
                                        id="due_date"
                                        name="due_date"
                                        type="date"
                                        :value="old('due_date', now()->addDays(3)->toDateString())"
                                        placeholder="Pilih batas tanggal kembali"
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('due_date')" />
                                </div>
                            </div>

                            <div class="mb-4">
                                <x-input-label for="notes" value="Catatan / Keperluan Peminjaman" />
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    placeholder="Contoh: Digunakan untuk praktikum IPA kelas XI di laboratorium (opsional)"
                                >{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" />
                            </div>

                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary mb-0">
                                    Batal
                                </a>
                                <x-primary-button class="bg-gradient-primary">
                                    Kirim Pengajuan
                                </x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

