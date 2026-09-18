<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tautan_publik_disposisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surat')->cascadeOnDelete();

            // Disposisi yang lembarnya digabung ke PDF publik ini. Nullable & nullOnDelete
            // agar tautan tidak ikut hilang kalau disposisi terkait dihapus (jarang terjadi).
            $table->foreignId('disposisi_id')->nullable()->constrained('disposisi')->nullOnDelete();

            $table->string('token', 64)->unique();

            // Hanya Staff Umum yang boleh membuat tautan; dicatat untuk audit/log aktivitas.
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();

            $table->timestamp('kadaluarsa_at')->nullable();
            $table->timestamp('terakhir_diakses_at')->nullable();
            $table->unsignedInteger('jumlah_akses')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tautan_publik_disposisi');
    }
};
