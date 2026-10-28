<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLoanRequest extends FormRequest
{
    /**
     * Pengguna yang sudah login dapat mengajukan peminjaman.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Aturan validasi pengajuan peminjaman (LOA-01 & LOA-02).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'loan_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:loan_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Validasi lanjutan: pastikan barang kondisi baik dan jumlah tidak melebihi stok (LOA-02).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $item = Item::find($this->input('item_id'));

                if (! $item) {
                    $validator->errors()->add('item_id', 'Barang yang dipilih tidak ditemukan.');

                    return;
                }

                if ($item->condition !== 'baik') {
                    $validator->errors()->add(
                        'item_id',
                        "Barang \"{$item->name}\" sedang dalam kondisi {$item->condition} dan tidak dapat dipinjam."
                    );

                    return;
                }

                $requestedQty = (int) $this->input('quantity', 0);

                if ($requestedQty > $item->stock) {
                    $validator->errors()->add(
                        'quantity',
                        "Jumlah peminjaman ({$requestedQty} unit) tidak boleh melebihi stok tersedia ({$item->stock} unit)."
                    );
                }
            },
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
            'item_id.required' => 'Barang yang ingin dipinjam wajib dipilih.',
            'item_id.exists' => 'Barang yang dipilih tidak valid.',
            'quantity.required' => 'Jumlah pinjam wajib diisi.',
            'quantity.integer' => 'Jumlah pinjam harus berupa angka bulat.',
            'quantity.min' => 'Jumlah pinjam minimal :min unit.',
            'loan_date.required' => 'Tanggal pinjam wajib diisi.',
            'loan_date.date' => 'Format tanggal pinjam tidak valid.',
            'due_date.required' => 'Batas tanggal pengembalian wajib diisi.',
            'due_date.date' => 'Format batas tanggal pengembalian tidak valid.',
            'due_date.after_or_equal' => 'Batas tanggal pengembalian harus sama dengan atau setelah tanggal pinjam.',
            'notes.max' => 'Catatan keperluan maksimal :max karakter.',
        ];
    }
}

