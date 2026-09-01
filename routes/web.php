<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SuratController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Aplikasi Disposisi Surat Menyurat
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'create'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'store'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/surat');

    Route::get('/surat', [SuratController::class, 'index'])->name('surat.index');
    Route::get('/surat/create', [SuratController::class, 'create'])->name('surat.create');
    Route::post('/surat', [SuratController::class, 'store'])->name('surat.store');

    // PENTING: rute /surat/sampah HARUS didaftarkan sebelum /surat/{surat},
    // kalau tidak "sampah" akan dianggap sebagai {surat} (ID surat) dan
    // langsung 404 lewat route model binding.
    Route::get('/surat/sampah', [SuratController::class, 'sampah'])->name('surat.sampah');
    Route::post('/surat/hapus', [SuratController::class, 'hapus'])->name('surat.hapus');
    Route::post('/surat/sampah/pulihkan', [SuratController::class, 'pulihkan'])->name('surat.pulihkan');
    Route::post('/surat/sampah/hapus-permanen', [SuratController::class, 'hapusPermanen'])->name('surat.hapusPermanen');

    Route::get('/surat/{surat}', [SuratController::class, 'show'])->name('surat.show');

    Route::post('/surat/{surat}/disposisi', [DisposisiController::class, 'store'])->name('disposisi.store');
    Route::post('/surat/{surat}/keputusan', [DisposisiController::class, 'keputusan'])->name('disposisi.keputusan');
    Route::post('/surat/{surat}/disposisi/{disposisi}/selesai', [DisposisiController::class, 'selesaikan'])->name('disposisi.selesaikan');
    Route::get('/surat/{surat}/disposisi/{disposisi}/cetak', [DisposisiController::class, 'cetak'])->name('disposisi.cetak');

    // Menu Pesan: pesan internal antar pengguna, mirip email sederhana.
    Route::prefix('pesan')->name('pesan.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/tulis', [MessageController::class, 'create'])->name('create');
        Route::post('/', [MessageController::class, 'store'])->name('store');

        // Sama seperti di atas: /pesan/sampah harus sebelum /pesan/{pesan}.
        Route::get('/sampah', [MessageController::class, 'sampah'])->name('sampah');
        Route::post('/hapus', [MessageController::class, 'hapus'])->name('hapus');
        Route::post('/sampah/pulihkan', [MessageController::class, 'pulihkan'])->name('pulihkan');
        Route::post('/sampah/hapus-permanen', [MessageController::class, 'hapusPermanen'])->name('hapusPermanen');

        Route::get('/{pesan}', [MessageController::class, 'show'])->name('show');
    });
});
