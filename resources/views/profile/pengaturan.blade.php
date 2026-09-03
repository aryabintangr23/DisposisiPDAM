@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
    <div class="mt-6 mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Pengaturan</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola keamanan akun Anda.</p>
    </div>

    <div class="max-w-xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        {{-- Tab kecil: Profil / Pengaturan --}}
        <div class="flex gap-1 border-b border-slate-200 px-4">
            <a href="{{ route('profil.edit') }}"
               class="border-b-2 border-transparent px-3 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700">Edit Profil</a>
            <a href="{{ route('profil.pengaturan') }}"
               class="border-b-2 border-brand-700 px-3 py-3 text-sm font-semibold text-brand-700">Pengaturan</a>
        </div>

        <div class="p-6">
            <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-brand-700">Ganti Kata Sandi</h3>
            <p class="mb-5 text-sm text-slate-500">Gunakan kata sandi yang kuat dan tidak dipakai di tempat lain.</p>

            <form method="POST" action="{{ route('profil.pengaturan.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kata Sandi Saat Ini</label>
                    <input type="password" name="password_saat_ini" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kata Sandi Baru</label>
                    <input type="password" name="password" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
