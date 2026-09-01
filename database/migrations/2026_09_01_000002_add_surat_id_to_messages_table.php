<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Nullable: pesan manual (tulis sendiri) tidak terkait surat apa pun.
            // Diisi otomatis saat pesan dibuat dari notifikasi disposisi.
            $table->foreignId('surat_id')->nullable()->after('receiver_id')
                ->constrained('surat')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('surat_id');
        });
    }
};
