@extends('layouts.app')

@section('title', isset($user) ? 'Edit User' : 'Tambah User')

@section('content')
    <div class="mt-6 mb-6">
        <a href="{{ route('pengguna.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Daftar Pengguna
        </a>
        <h2 class="mt-2 text-2xl font-bold text-slate-800">{{ isset($user) ? 'Edit User' : 'Tambah User' }}</h2>
        <p class="mt-1 text-sm text-slate-500">Kelola data akun, peran (role), dan kata sandi.</p>
    </div>

    <form method="POST"
          action="{{ isset($user) ? route('pengguna.update', $user) : route('pengguna.store') }}"
          class="space-y-6">
        @csrf
        @if (isset($user))
            @method('PUT')
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-brand-700">Data Akun</h3>
            <p class="mb-5 text-sm text-slate-500">Informasi dasar akun pengguna.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input type="text" name="nama" value="{{ old('nama', $user->nama ?? '') }}" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    @error('nama') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
                    <select name="role_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        @foreach ($roles as $r)
                            <option value="{{ $r->id }}" @selected(old('role_id', $user->role_id ?? null) == $r->id)>
                                {{ ucwords(str_replace('_', ' ', $r->nama_role)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div class="{{ isset($user) ? 'sm:col-span-2' : '' }}">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">
                        Kata Sandi
                        <span class="font-normal text-slate-400">{{ isset($user) ? '(kosongkan jika tidak diubah)' : '' }}</span>
                    </label>
                    <input type="password" name="password" {{ isset($user) ? '' : 'required' }}
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi</label>
                    <input type="password" name="password_confirmation" {{ isset($user) ? '' : 'required' }}
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('pengguna.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                Batal
            </a>
            <button type="submit"
                    class="rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                {{ isset($user) ? 'Simpan Perubahan' : 'Tambah User' }}
            </button>
        </div>
    </form>
@endsection
