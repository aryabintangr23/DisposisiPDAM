<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->string('arah_surat'); // masuk | keluar
            $table->string('jenis_surat');
            $table->string('nomor_surat');

            // Nomor agenda divalidasi via aplikasi (soft warning) agar tidak mengunci DB jika ada duplikat
            $table->string('nomor_agenda')->nullable();
            $table->index('nomor_agenda');

            $table->date('tanggal_surat');
            $table->date('tanggal_diterima')->nullable();

            $table->string('surat_dari')->nullable();   // Asal surat (surat masuk)
            $table->string('tujuan_surat')->nullable(); // Tujuan surat (surat keluar)

            $table->text('perihal');

            // Status: baru | diterima | ditolak | perlu_revisi
            $table->string('status')->default('baru');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};