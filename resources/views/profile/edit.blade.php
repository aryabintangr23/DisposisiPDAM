@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
    <div class="mt-6 mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Edit Profil</h2>
        <p class="mt-1 text-sm text-slate-500">Perbarui nama dan alamat email akun Anda.</p>
    </div>

    <div class="max-w-xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        {{-- Tab kecil: Profil / Pengaturan, supaya mudah berpindah --}}
        <div class="flex gap-1 border-b border-slate-200 px-4">
            <a href="{{ route('profil.edit') }}"
               class="border-b-2 border-brand-700 px-3 py-3 text-sm font-semibold text-brand-700">Edit Profil</a>
            <a href="{{ route('profil.pengaturan') }}"
               class="border-b-2 border-transparent px-3 py-3 text-sm font-semibold text-slate-500 hover:text-slate-700">Pengaturan</a>
        </div>

        <form method="POST" action="{{ route('profil.update') }}" class="space-y-4 p-6">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-700">
                    {{ strtoupper(substr($user->nama, 0, 1)) }}
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-slate-700">{{ $user->nama }}</p>
                    <p class="text-xs text-slate-400">{{ ucwords(str_replace('_', ' ', $user->role->nama_role)) }}</p>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama</label>
                <input type="text" name="nama" required value="{{ old('nama', $user->nama) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
