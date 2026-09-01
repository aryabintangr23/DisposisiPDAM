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
        Route::get('/{pesan}', [MessageController::class, 'show'])->name('show');
    });
});
