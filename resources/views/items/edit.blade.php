<x-app-layout title="Edit Barang" :breadcrumbs="['Data Barang' => route('items.index'), 'Edit Barang' => null]">
    <div class="row">
        <div class="col-lg-8 col-md-10 col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0 font-weight-bolder">Edit Data Barang</h5>
                    <p class="text-sm text-secondary mb-0">
                        Perbarui informasi untuk barang <strong>{{ $item->name }} ({{ $item->code }})</strong>
                    </p>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('items.update', $item) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-7 mb-3">
                                <x-input-label for="name" value="Nama Barang" :required="true" />
                                <x-text-input
                                    id="name"
                                    name="name"
                                    type="text"
                                    :value="old('name', $item->name)"
                                    placeholder="Contoh: Proyektor LCD Epson"
                                    required
                                    autofocus
                                />
                                <x-input-error :messages="$errors->get('name')" />
                            </div>

                            <div class="col-md-5 mb-3">
                                <x-input-label for="code" value="Kode Barang (SKU)" :required="true" />
                                <x-text-input
                                    id="code"
                                    name="code"
                                    type="text"
                                    :value="old('code', $item->code)"
                                    placeholder="Contoh: ELK-005"
                                    required
                                />
                                <x-input-error :messages="$errors->get('code')" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <x-input-label for="category_id" value="Kategori" :required="true" />
                                <select
                                    id="category_id"
                                    name="category_id"
                                    class="form-select @error('category_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="" disabled>-- Pilih Kategori --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id', $item->category_id) === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('category_id')" />
                            </div>

                            <div class="col-md-3 mb-3">
                                <x-input-label for="stock" value="Stok Tersedia" :required="true" />
                                <x-text-input
                                    id="stock"
                                    name="stock"
                                    type="number"
                                    min="0"
                                    :value="old('stock', $item->stock)"
                                    placeholder="Masukkan jumlah stok"
                                    required
                                />
                                <x-input-error :messages="$errors->get('stock')" />
                            </div>

                            <div class="col-md-4 mb-3">
                                <x-input-label for="condition" value="Kondisi Barang" :required="true" />
                                <select
                                    id="condition"
                                    name="condition"
                                    class="form-select @error('condition') is-invalid @enderror"
                                    required
                                >
                                    <option value="baik" @selected(old('condition', $item->condition) === 'baik')>Baik</option>
                                    <option value="rusak ringan" @selected(old('condition', $item->condition) === 'rusak ringan')>Rusak Ringan</option>
                                    <option value="rusak berat" @selected(old('condition', $item->condition) === 'rusak berat')>Rusak Berat</option>
                                </select>
                                <x-input-error :messages="$errors->get('condition')" />
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                            <a href="{{ route('items.index') }}" class="btn btn-outline-secondary mb-0">
                                Batal
                            </a>
                            <x-primary-button class="bg-gradient-primary">
                                Simpan Perubahan
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

