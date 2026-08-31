<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['staff_umum', 'kabag_umum', 'direktur'] as $nama) {
            Role::firstOrCreate(['nama_role' => $nama]);
        }
    }
}
