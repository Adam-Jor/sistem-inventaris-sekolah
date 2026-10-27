<?php

namespace Database\Seeders;

use App\Models\Loan;
use App\Models\ReturnLog;
use Illuminate\Database\Seeder;

class ReturnLogSeeder extends Seeder
{
    public function run(): void
    {
        // Dicocokkan lewat kode barang dari peminjaman berstatus "dikembalikan".
        $returns = [
            'ELK-001' => ['returned' => -18, 'condition' => 'baik', 'notes' => 'Dikembalikan lengkap bersama kabel HDMI dan remote.'],
            'OLR-001' => ['returned' => -12, 'condition' => 'baik', 'notes' => 'Kondisi bola masih baik.'],
            'OLR-003' => ['returned' => -5, 'condition' => 'rusak ringan', 'notes' => 'Terdapat sobekan kecil di salah satu sisi matras.'],
        ];

        foreach (Loan::returned()->with('item')->get() as $loan) {
            $row = $returns[$loan->item->code] ?? null;

            if (! $row) {
                continue;
            }

            $returnedAt = now()->addDays($row['returned']);
            $createdAt = $returnedAt->copy()->setTime(14, 0);

            ReturnLog::unguarded(fn () => ReturnLog::create([
                'loan_id' => $loan->id,
                'returned_at' => $returnedAt->toDateString(),
                'condition_on_return' => $row['condition'],
                'notes' => $row['notes'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]));
        }
    }
}
