<?php

// CATATAN: file ini bukan pengganti routes/web.php Anda, melainkan potongan
// yang perlu DITAMBAHKAN ke routes/web.php yang sudah ada (jangan menimpa
// route bawaan Laravel yang lain kalau ada).

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DisposisiController;
use App\Http\Controllers\SuratController;
use Illuminate\Support\Facades\Route;

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
    Route::post('/surat/{surat}/disposisi/{disposisi}/selesai', [DisposisiController::class, 'selesaikan'])->name('disposisi.selesaikan');
});
