<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoanSeeder extends Seeder
{
    /**
     * Tanggal ditulis sebagai selisih hari dari hari ini (negatif = lampau),
     * jadi status "terlambat" tetap masuk akal kapan pun seeder dijalankan.
     */
    public function run(): void
    {
        $budi = User::where('email', UserSeeder::BUDI_EMAIL)->firstOrFail();
        $siti = User::where('email', UserSeeder::SITI_EMAIL)->firstOrFail();
        $itemIds = Item::pluck('id', 'code');

        $loans = [
            // --- Sudah dikembalikan (dicatat di ReturnLogSeeder) ---
            ['user' => $budi, 'item' => 'ELK-001', 'qty' => 1, 'loan' => -20, 'due' => -18, 'status' => 'dikembalikan', 'notes' => 'Presentasi tugas akhir kelas XII.'],
            ['user' => $siti, 'item' => 'OLR-001', 'qty' => 2, 'loan' => -14, 'due' => -12, 'status' => 'dikembalikan', 'notes' => 'Latihan tim basket untuk lomba antar kelas.'],
            ['user' => $siti, 'item' => 'OLR-003', 'qty' => 1, 'loan' => -9, 'due' => -6, 'status' => 'dikembalikan', 'notes' => 'Praktik senam lantai.'],
            // --- Sedang dipinjam ---
            ['user' => $budi, 'item' => 'ELK-002', 'qty' => 2, 'loan' => -3, 'due' => 4, 'status' => 'dipinjam', 'notes' => 'Praktikum Informatika kelas XI.'],
            // --- Terlambat (lewat jatuh tempo, belum dikembalikan) ---
            ['user' => $siti, 'item' => 'KBR-002', 'qty' => 1, 'loan' => -10, 'due' => -3, 'status' => 'terlambat', 'notes' => 'Membersihkan aula setelah acara sekolah.'],
            // --- Menunggu verifikasi Admin ---
            ['user' => $budi, 'item' => 'KBR-001', 'qty' => 3, 'loan' => 1, 'due' => 3, 'status' => 'menunggu', 'notes' => 'Kegiatan kerja bakti kelas.'],
            // --- Ditolak ---
            ['user' => $siti, 'item' => 'ELK-001', 'qty' => 5, 'loan' => -5, 'due' => -2, 'status' => 'ditolak', 'notes' => 'Acara pentas seni. (Ditolak: jumlah melebihi stok.)'],
        ];

        foreach ($loans as $row) {
            $loanDate = now()->addDays($row['loan']);
            $dueDate = now()->addDays($row['due']);
            $createdAt = $loanDate->isFuture() ? now() : $loanDate->copy()->setTime(8, 0);

            // unguarded: agar created_at/updated_at bisa diisi sesuai alur cerita data.
            Loan::unguarded(fn () => Loan::create([
                'user_id' => $row['user']->id,
                'item_id' => $itemIds[$row['item']],
                'quantity' => $row['qty'],
                'loan_date' => $loanDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => $row['status'],
                'notes' => $row['notes'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]));

            // Peminjaman aktif sudah mengurangi stok.
            // (Yang sudah dikembalikan: stok kembali; menunggu/ditolak: stok tidak berubah.)
            if (in_array($row['status'], ['dipinjam', 'terlambat'], true)) {
                Item::whereKey($itemIds[$row['item']])->decrement('stock', $row['qty']);
            }
        }
    }
}
