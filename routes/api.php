<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Belum dipakai di aplikasi ini (semua fitur disposisi lewat routes/web.php).
| File ini hanya perlu ADA karena dipanggil oleh RouteServiceProvider.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
