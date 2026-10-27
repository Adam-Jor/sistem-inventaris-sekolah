<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Alat Laboratorium', 'description' => 'Peralatan praktikum IPA (Fisika, Kimia, Biologi).'],
            ['name' => 'Alat Olahraga', 'description' => 'Perlengkapan pelajaran dan kegiatan olahraga.'],
            ['name' => 'Elektronik', 'description' => 'Perangkat elektronik untuk kegiatan belajar mengajar.'],
            ['name' => 'Alat Kebersihan', 'description' => 'Perlengkapan kebersihan lingkungan sekolah.'],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
