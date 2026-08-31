<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lampiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_id')->constrained('surat')->cascadeOnDelete();

            $table->string('nama_file');
            $table->string('path_file');
            $table->string('tipe_file'); // dibatasi PDF saja sesuai konfirmasi
            $table->unsignedBigInteger('ukuran_file'); // dalam byte

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lampiran');
    }
};
