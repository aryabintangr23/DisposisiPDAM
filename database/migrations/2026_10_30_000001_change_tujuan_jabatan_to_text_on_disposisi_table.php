<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu keputusan Direktur kini bisa diarahkan ke lebih dari satu jabatan,
        // sehingga kolom ini menyimpan JSON array dan butuh ruang lebih dari 255 karakter.
        Schema::table('disposisi', function (Blueprint $table) {
            $table->text('tujuan_jabatan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('disposisi', function (Blueprint $table) {
            $table->string('tujuan_jabatan')->nullable()->change();
        });
    }
};
