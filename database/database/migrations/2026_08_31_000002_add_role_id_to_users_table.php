<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nama kolom "name" bawaan Laravel diganti "nama" sesuai requirement.
            $table->renameColumn('name', 'nama');

            $table->foreignId('role_id')
                ->after('id')
                ->constrained('roles')
                ->restrictOnDelete(); // role tidak boleh dihapus jika masih dipakai user
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->renameColumn('nama', 'name');
        });
    }
};
