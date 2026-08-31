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

            $table->string('arah_surat'); // masuk | keluar — dikonfirmasi terpisah dari jenis_surat
            $table->string('jenis_surat');
            $table->string('nomor_surat');

            // Diisi manual oleh Staff. Tidak diberi unique constraint karena
            // sifatnya input manual dan berpotensi human error — sebaiknya
            // divalidasi "soft" (peringatan jika duplikat) di level aplikasi,
            // bukan dipaksa unik di level database.
            $table->string('nomor_agenda')->nullable();
            $table->index('nomor_agenda');

            $table->date('tanggal_surat');
            $table->date('tanggal_diterima')->nullable();

            $table->string('surat_dari')->nullable();  // relevan untuk surat masuk
            $table->string('tujuan_surat')->nullable(); // relevan untuk surat keluar

            $table->text('perihal');

            // baru | diterima | ditolak | perlu_revisi (perlu_revisi hanya untuk surat keluar)
            $table->string('status')->default('baru');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat');
    }
};
