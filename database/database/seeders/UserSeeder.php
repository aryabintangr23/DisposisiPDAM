<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Seeder ini hanya untuk kebutuhan TESTING lokal. Password default "password"
// -- jangan dipakai di server production, ganti/hapus user ini nanti.
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['nama' => 'Staff Umum', 'email' => 'staff@tirtagemilang.test', 'role' => 'staff_umum'],
            ['nama' => 'Kabag Umum', 'email' => 'kabag@tirtagemilang.test', 'role' => 'kabag_umum'],
            ['nama' => 'Direktur', 'email' => 'direktur@tirtagemilang.test', 'role' => 'direktur'],
        ];

        foreach ($daftar as $item) {
            $role = Role::where('nama_role', $item['role'])->firstOrFail();

            User::firstOrCreate(
                ['email' => $item['email']],
                [
                    'nama' => $item['nama'],
                    'role_id' => $role->id,
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
