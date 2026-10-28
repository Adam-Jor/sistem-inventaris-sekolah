<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    /**
     * Hanya Admin yang diizinkan menambah barang.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Aturan validasi tambah barang.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:items,code'],
            'stock' => ['required', 'integer', 'min:0'],
            'condition' => ['required', 'in:baik,rusak ringan,rusak berat'],
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori barang wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'name.required' => 'Nama barang wajib diisi.',
            'name.max' => 'Nama barang maksimal :max karakter.',
            'code.required' => 'Kode barang wajib diisi.',
            'code.max' => 'Kode barang maksimal :max karakter.',
            'code.unique' => 'Kode barang tersebut sudah digunakan, masukkan kode unik lain.',
            'stock.required' => 'Stok barang wajib diisi.',
            'stock.integer' => 'Stok barang harus berupa angka bulat.',
            'stock.min' => 'Stok barang tidak boleh kurang dari :min.',
            'condition.required' => 'Kondisi barang wajib dipilih.',
            'condition.in' => 'Kondisi barang harus salah satu dari: baik, rusak ringan, atau rusak berat.',
        ];
    }
}

