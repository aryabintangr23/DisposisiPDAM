<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surat')->cascadeOnDelete();
            $table->foreignId('pengirim_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('penerima_id')->constrained('users')->restrictOnDelete();

            $table->date('tanggal_disposisi');
            $table->string('prioritas'); // sangat_segera | segera | biasa | tunggu_petunjuk
            $table->date('batas_waktu')->nullable(); // null jika tunggu_petunjuk. Dihitung hari kalender.

            $table->text('instruksi')->nullable();

            // terkirim | diterima | dibaca | ditindaklanjuti | selesai
            // Status "selesai" hanya boleh diset oleh Staff (dikonfirmasi).
            $table->string('status')->default('terkirim');

            $table->date('tanggal_diterima')->nullable();

            $table->timestamps();

            // Alur dipastikan linear (tidak bercabang), jadi cukup urut
            // berdasarkan created_at per surat_id tanpa kolom induk tambahan.
            $table->index(['surat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi');
    }
};
