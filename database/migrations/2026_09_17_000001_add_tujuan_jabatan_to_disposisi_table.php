<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposisi', function (Blueprint $table) {
            $table->dropForeign(['penerima_id']);
            $table->unsignedBigInteger('penerima_id')->nullable()->change();
            $table->string('tujuan_jabatan')->nullable()->after('penerima_id');
            $table->string('tujuan_bagian')->nullable()->after('tujuan_jabatan');
        });

        Schema::table('disposisi', function (Blueprint $table) {
            $table->foreign('penerima_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disposisi', function (Blueprint $table) {
            $table->dropForeign(['penerima_id']);
            $table->dropColumn(['tujuan_jabatan', 'tujuan_bagian']);
            $table->unsignedBigInteger('penerima_id')->nullable(false)->change();
        });

        Schema::table('disposisi', function (Blueprint $table) {
            $table->foreign('penerima_id')->references('id')->on('users')->restrictOnDelete();
        });
    }
};
