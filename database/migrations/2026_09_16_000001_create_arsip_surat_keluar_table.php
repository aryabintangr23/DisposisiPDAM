<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arsip surat keluar: catatan surat keluar milik Staff yang HANYA berfungsi sebagai
     * arsip. Tidak terhubung ke sistem disposisi sama sekali (tabel & model terpisah dari `surat`).
     */
    public function up(): void
    {
        Schema::create('arsip_surat_keluar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->string('nomor_urut');
            $table->string('nomor_berkas');
            $table->date('tanggal_surat');
            $table->text('perihal');
            $table->date('tanggal_dikeluarkan');
            $table->text('tujuan');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arsip_surat_keluar');
    }
};
