<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();

            // Nullable untuk mencatat percobaan login gagal atau jika user dihapus
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Kode aksi ringkas (misal: surat_dibuat, login_gagal)
            $table->string('aksi', 60)->index();

            $table->text('deskripsi');

            // Referensi opsional ke entitas terkait (misal: entitas="surat", entitas_id=12)
            $table->string('entitas', 30)->nullable();
            $table->unsignedBigInteger('entitas_id')->nullable();

            $table->string('ip_address', 45)->nullable();

            // Hanya mencatat timestamp pembuatan
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};