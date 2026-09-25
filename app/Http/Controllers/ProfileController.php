<?php

namespace App\Http\Controllers;

use App\Enums\StatusSurat;
use App\Models\Surat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Form edit profil kini digabung ke halaman Pengaturan (tab Edit Profil).
     */
    public function edit(Request $request): RedirectResponse
    {
        return redirect()->route('profil.pengaturan');
    }

    /**
     * Update data profil (nama & email).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return redirect()->route('profil.pengaturan')->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Tampilkan halaman pengaturan (edit profil, ganti password, dll).
     *
     * Statistik pada kartu profil dihitung dengan scope Surat::untukRole()
     * yang sama untuk kelima role (Staff, Kasubag, Kabag, Direktur, Admin),
     * supaya cara hitungnya konsisten:
     * - Surat Dibuat  : seluruh surat dalam lingkup user (yang ia buat / tangani).
     * - Surat Diterima: dari lingkup tsb, yang berstatus "Diterima".
     * - Surat Ditolak : dari lingkup tsb, yang berstatus "Ditolak".
     */
    public function pengaturan(Request $request): View
    {
        $user = $request->user();

        $lingkupSurat = fn () => Surat::untukRole($user);

        return view('profile.pengaturan', [
            'user' => $user,
            'jumlahSuratDibuat' => $lingkupSurat()->count(),
            'jumlahSuratDiterima' => $lingkupSurat()->where('status', StatusSurat::Diterima)->count(),
            'jumlahSuratDitolak' => $lingkupSurat()->where('status', StatusSurat::Ditolak)->count(),
        ]);
    }

    /**
     * Update password user.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'password_saat_ini' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password_saat_ini.current_password' => 'Kata sandi saat ini tidak sesuai.',
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('profil.pengaturan')->with('status', 'Kata sandi berhasil diperbarui.');
    }
}