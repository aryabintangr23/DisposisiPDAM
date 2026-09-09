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

            // Nullable: login gagal terjadi SEBELUM user teridentifikasi,
            // dan user yang menghapus akunnya sendiri tidak boleh membuat
            // baris log lama ikut hilang (nullOnDelete, bukan cascade).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Kode aksi ringkas, mis. "surat_dibuat", "login_gagal", dst.
            // Dipakai untuk filter & pewarnaan badge di halaman log.
            $table->string('aksi', 60)->index();

            $table->text('deskripsi');

            // Referensi opsional ke record terkait (mis. entitas="surat",
            // entitas_id=123), untuk tautan "Lihat" di halaman log. Sengaja
            // string biasa (bukan morph Eloquent) supaya tetap sederhana.
            $table->string('entitas', 30)->nullable();
            $table->unsignedBigInteger('entitas_id')->nullable();

            $table->string('ip_address', 45)->nullable();

            // Hanya created_at (lihat LogAktivitas::UPDATED_AT = null) —
            // baris log tidak pernah diubah, hanya ditambahkan.
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
