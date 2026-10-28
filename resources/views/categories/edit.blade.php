<x-app-layout title="Edit Kategori" :breadcrumbs="['Kategori' => route('categories.index'), 'Edit Kategori' => null]">
    <div class="row">
        <div class="col-lg-7 col-md-9 col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h5 class="mb-0 font-weight-bolder">Edit Kategori</h5>
                    <p class="text-sm text-secondary mb-0">
                        Perbarui nama atau deskripsi untuk kategori <strong>{{ $category->name }}</strong>
                    </p>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('categories.update', $category) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <x-input-label for="name" value="Nama Kategori" :required="true" />
                            <x-text-input
                                id="name"
                                name="name"
                                type="text"
                                :value="old('name', $category->name)"
                                placeholder="Contoh: Alat Laboratorium"
                                required
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('name')" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="description" value="Deskripsi" />
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Masukkan deskripsi singkat kategori barang (opsional)"
                            >{{ old('description', $category->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" />
                        </div>

                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary mb-0">
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

