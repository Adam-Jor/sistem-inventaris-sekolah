<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class StoreReturnLogRequest extends FormRequest
{
    /**
     * Hanya Admin yang diizinkan mencatat pengembalian barang (RET-01).
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Aturan validasi pencatatan pengembalian.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Loan|null $loan */
        $loan = $this->route('loan');
        $minDateRule = $loan?->loan_date
            ? 'after_or_equal:'.$loan->loan_date->toDateString()
            : 'date';

        return [
            'returned_at' => ['required', 'date', $minDateRule],
            'condition_on_return' => ['required', 'in:baik,rusak ringan,rusak berat'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'returned_at.required' => 'Tanggal pengembalian wajib diisi.',
            'returned_at.date' => 'Format tanggal pengembalian tidak valid.',
            'returned_at.after_or_equal' => 'Tanggal pengembalian tidak boleh lebih awal dari tanggal pinjam.',
            'condition_on_return.required' => 'Kondisi barang saat dikembalikan wajib dipilih.',
            'condition_on_return.in' => 'Kondisi barang harus salah satu dari: baik, rusak ringan, atau rusak berat.',
            'notes.max' => 'Catatan pengembalian maksimal :max karakter.',
        ];
    }
}

