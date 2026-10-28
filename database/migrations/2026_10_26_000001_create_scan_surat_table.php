<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Token acak yang dikirim balik ke form. Dipakai saat surat disimpan
            // supaya berkas hasil scan bisa dipindahkan menjadi lampiran permanen.
            $table->uuid('token')->unique();

            $table->string('nama_file');
            $table->string('path_file');
            $table->string('tipe_file', 120);
            $table->unsignedBigInteger('ukuran_file');

            // Hasil ekstraksi (fields + peringatan) dan teks OCR mentah.
            // Teks hanya disimpan sementara sampai berkas dipakai/kedaluwarsa.
            $table->json('hasil_json')->nullable();
            $table->longText('teks_ocr')->nullable();

            $table->string('status', 20)->default('diproses'); // diproses | selesai | gagal
            $table->text('pesan_error')->nullable();

            // Diisi saat berkas sudah dipindahkan menjadi lampiran surat.
            $table->timestamp('dipakai_at')->nullable();
            $table->timestamp('expires_at')->index();

            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_surat');
    }
};
