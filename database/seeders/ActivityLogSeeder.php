<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            'admin' => User::where('email', UserSeeder::ADMIN_EMAIL)->firstOrFail()->id,
            'budi' => User::where('email', UserSeeder::BUDI_EMAIL)->firstOrFail()->id,
            'siti' => User::where('email', UserSeeder::SITI_EMAIL)->firstOrFail()->id,
        ];

        // [selisih hari, jam, pelaku, aksi, deskripsi]
        $logs = [
            [-21, '08:00', 'admin', 'login', 'Admin Inventaris masuk ke sistem.'],
            [-21, '08:10', 'admin', 'create_category', 'Menambahkan kategori Alat Laboratorium.'],
            [-21, '08:15', 'admin', 'create_item', 'Menambahkan barang Mikroskop Binokuler (LAB-001).'],
            [-21, '08:20', 'admin', 'create_item', 'Menambahkan barang Proyektor LCD (ELK-001).'],
            [-20, '09:00', 'budi', 'request_loan', 'Budi Santoso mengajukan peminjaman Proyektor LCD (1 unit).'],
            [-20, '09:30', 'admin', 'verify_loan', 'Menyetujui peminjaman Proyektor LCD oleh Budi Santoso.'],
            [-18, '14:00', 'admin', 'return_loan', 'Mencatat pengembalian Proyektor LCD oleh Budi Santoso, kondisi baik.'],
            [-5, '10:00', 'siti', 'request_loan', 'Siti Rahmawati mengajukan peminjaman Proyektor LCD (5 unit).'],
            [-5, '10:20', 'admin', 'reject_loan', 'Menolak peminjaman Proyektor LCD oleh Siti Rahmawati: jumlah melebihi stok.'],
            [-5, '15:00', 'admin', 'return_loan', 'Mencatat pengembalian Matras Senam oleh Siti Rahmawati, kondisi rusak ringan.'],
            [-5, '15:05', 'admin', 'update_item', 'Mengubah kondisi Matras Senam menjadi rusak ringan.'],
            [-3, '08:30', 'budi', 'request_loan', 'Budi Santoso mengajukan peminjaman Laptop Inventaris (2 unit).'],
            [-3, '09:00', 'admin', 'verify_loan', 'Menyetujui peminjaman Laptop Inventaris oleh Budi Santoso.'],
        ];

        foreach ($logs as [$days, $time, $actor, $action, $description]) {
            [$hour, $minute] = explode(':', $time);
            $createdAt = now()->addDays($days)->setTime((int) $hour, (int) $minute);

            ActivityLog::unguarded(fn () => ActivityLog::create([
                'user_id' => $users[$actor],
                'action' => $action,
                'description' => $description,
                'ip_address' => '127.0.0.1',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]));
        }
    }
}
