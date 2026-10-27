<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Dirancang untuk dijalankan di database kosong:
     *   php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        // Data peminjaman & stok saling terkait, jadi jangan di-seed dua kali.
        if (User::where('email', UserSeeder::ADMIN_EMAIL)->exists()) {
            $this->command?->warn('Data seeder sudah ada. Jalankan "php artisan migrate:fresh --seed" untuk mengulang dari awal.');

            return;
        }

        // Urutan penting: tabel yang direferensikan harus terisi lebih dulu.
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
            LoanSeeder::class,
            ReturnLogSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
