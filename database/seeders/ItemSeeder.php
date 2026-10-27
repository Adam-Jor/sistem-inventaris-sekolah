<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Stok di sini adalah stok AWAL. LoanSeeder akan mengurangi stok
     * untuk peminjaman yang masih aktif (dipinjam/terlambat), sehingga
     * stok akhir konsisten dengan data peminjaman.
     */
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        $items = [
            // Alat Laboratorium
            ['Alat Laboratorium', 'Mikroskop Binokuler', 'LAB-001', 10, 'baik'],
            ['Alat Laboratorium', 'Gelas Ukur 250 ml', 'LAB-002', 20, 'baik'],
            ['Alat Laboratorium', 'Tabung Reaksi (set)', 'LAB-003', 30, 'baik'],
            // Alat Olahraga
            ['Alat Olahraga', 'Bola Basket', 'OLR-001', 8, 'baik'],
            ['Alat Olahraga', 'Bola Voli', 'OLR-002', 8, 'baik'],
            ['Alat Olahraga', 'Matras Senam', 'OLR-003', 6, 'rusak ringan'],
            // Elektronik
            ['Elektronik', 'Proyektor LCD', 'ELK-001', 4, 'baik'],
            ['Elektronik', 'Laptop Inventaris', 'ELK-002', 5, 'baik'],
            ['Elektronik', 'Speaker Portable', 'ELK-003', 3, 'rusak ringan'],
            ['Elektronik', 'Proyektor Lama', 'ELK-004', 1, 'rusak berat'],
            // Alat Kebersihan
            ['Alat Kebersihan', 'Sapu Lantai', 'KBR-001', 15, 'baik'],
            ['Alat Kebersihan', 'Vacuum Cleaner', 'KBR-002', 2, 'baik'],
        ];

        foreach ($items as [$category, $name, $code, $stock, $condition]) {
            Item::updateOrCreate(
                ['code' => $code],
                [
                    'category_id' => $categories[$category],
                    'name' => $name,
                    'stock' => $stock,
                    'condition' => $condition,
                ]
            );
        }
    }
}
