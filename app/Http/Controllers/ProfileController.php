<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Form edit profil: nama & email.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);

        return redirect()->route('profil.edit')->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Halaman pengaturan: saat ini berisi ganti kata sandi. Bagian lain
     * (mis. preferensi notifikasi) bisa ditambahkan di sini nanti.
     */
    public function pengaturan(Request $request): View
    {
        return view('profile.pengaturan', ['user' => $request->user()]);
    }

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
