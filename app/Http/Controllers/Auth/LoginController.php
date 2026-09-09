<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Key unik berdasarkan gabungan email dan IP address
        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        // Cek apakah user telah melebihi batas percobaan (5 kali per 60 detik)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        // Coba autentikasi
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Catat percobaan gagal
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        // Bersihkan hit counter jika berhasil login
        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        // BARU: tandai supaya pop-up notifikasi pesan (lihat layouts.app)
        // ditampilkan sekali di halaman pertama setelah login berhasil.
        // Pop-up-nya sendiri hanya benar-benar muncul kalau user punya
        // pesan belum dibaca — flag ini cuma menandai "baru saja login".
        $request->session()->put('tampilkan_notif_login', true);

        return redirect()->intended(route('surat.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
