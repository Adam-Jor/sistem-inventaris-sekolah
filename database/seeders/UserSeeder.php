<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@sekolah.test';
    public const BUDI_EMAIL = 'budi@sekolah.test';
    public const SITI_EMAIL = 'siti@sekolah.test';

    public const PASSWORD = 'password';

    public function run(): void
    {
        $users = [
            ['name' => 'Admin Inventaris', 'email' => self::ADMIN_EMAIL, 'role' => 'admin'],
            ['name' => 'Budi Santoso', 'email' => self::BUDI_EMAIL, 'role' => 'user'],   // guru
            ['name' => 'Siti Rahmawati', 'email' => self::SITI_EMAIL, 'role' => 'user'], // siswa
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => self::PASSWORD, 'role' => $data['role']]
            );
        }
    }
}
