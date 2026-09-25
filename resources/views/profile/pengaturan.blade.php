@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
    @php
        $tabAwal = $errors->hasAny(['password_saat_ini', 'password', 'password_confirmation']) ? 'keamanan' : 'profil';
        $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $bergabungSejak = $user->created_at
            ? $user->created_at->day . ' ' . $namaBulan[(int) $user->created_at->format('n')] . ' ' . $user->created_at->year
            : '-';
    @endphp

    <div class="mt-6 mb-10" x-data="{ tab: '{{ $tabAwal }}' }">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Pengaturan Akun</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola informasi profil dan keamanan akun Anda di sini.</p>
        </div>

        <div class="grid w-full grid-cols-1 gap-6 lg:grid-cols-[22rem_1fr]">

            {{-- ===================== Kolom Kiri: Ringkasan & Navigasi ===================== --}}
            <div class="space-y-6">
                {{-- Kartu ringkasan profil --}}
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-16 bg-gradient-to-r from-brand-700 via-brand-600 to-brand-500"></div>
                    <div class="px-5 pb-5">
                        <div class="-mt-9 flex justify-center">
                            <div class="flex h-[4.5rem] w-[4.5rem] items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-800 text-xl font-bold text-white ring-4 ring-white">
                                {{ strtoupper(substr($user->nama, 0, 1)) }}
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <p class="truncate text-base font-bold text-slate-800">{{ $user->nama }}</p>
                            <p class="truncate text-sm text-slate-400">{{ $user->email }}</p>
                            <span class="mt-2 inline-block rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                                {{ ucwords(str_replace('_', ' ', $user->role->nama_role)) }}
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Bergabung sejak {{ $bergabungSejak }}
                        </div>

                        {{-- Statistik aktivitas --}}
                        <div class="mt-5 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4">
                            <div class="text-center">
                                <p class="text-lg font-bold text-slate-800">{{ $jumlahSuratDibuat }}</p>
                                <p class="text-[11px] leading-tight text-slate-400">Surat<br>Dibuat</p>
                            </div>
                            <div class="border-x border-slate-100 text-center">
                                <p class="text-lg font-bold text-emerald-600">{{ $jumlahSuratDiterima }}</p>
                                <p class="text-[11px] leading-tight text-slate-400">Surat<br>Diterima</p>
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-bold text-rose-600">{{ $jumlahSuratDitolak }}</p>
                                <p class="text-[11px] leading-tight text-slate-400">Surat<br>Ditolak</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Navigasi pengaturan --}}
                <nav class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                    <button type="button" @click="tab = 'profil'"
                        class="flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold transition"
                        :class="tab === 'profil' ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="tab === 'profil' ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-400'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                        <span class="flex-1">
                            Edit Profil
                            <span class="block text-xs font-normal" :class="tab === 'profil' ? 'text-brand-600' : 'text-slate-400'">Nama &amp; email akun</span>
                        </span>
                    </button>

                    <button type="button" @click="tab = 'keamanan'"
                        class="mt-1 flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold transition"
                        :class="tab === 'keamanan' ? 'bg-brand-50 text-brand-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg" :class="tab === 'keamanan' ? 'bg-brand-100 text-brand-700' : 'bg-slate-100 text-slate-400'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <span class="flex-1">
                            Keamanan
                            <span class="block text-xs font-normal" :class="tab === 'keamanan' ? 'text-brand-600' : 'text-slate-400'">Ganti kata sandi</span>
                        </span>
                    </button>
                </nav>
            </div>

            {{-- ===================== Kolom Kanan: Konten Panel ===================== --}}
            <div>
                {{-- Panel: Edit Profil --}}
                <div x-show="tab === 'profil'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-sm font-bold text-slate-800">Informasi Profil</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Perbarui nama dan alamat email yang digunakan untuk masuk ke sistem.</p>
                    </div>

                    <form method="POST" action="{{ route('profil.update') }}" class="space-y-5 px-6 py-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                <input type="text" name="nama" required value="{{ old('nama', $user->nama) }}"
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            </div>
                            @error('nama')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Alamat Email</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-xs text-slate-500">
                                <span class="font-semibold text-slate-600">Peran akun:</span>
                                {{ ucwords(str_replace('_', ' ', $user->role->nama_role)) }} — ditetapkan oleh admin dan tidak dapat diubah sendiri.
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                            <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Panel: Keamanan / Ganti Kata Sandi --}}
                <div x-show="tab === 'keamanan'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                    x-data="{ showLama: false, showBaru: false, showKonfirmasi: false, sandi: '' }"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-sm font-bold text-slate-800">Ganti Kata Sandi</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Gunakan kata sandi yang kuat dan tidak dipakai di layanan lain.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-0 lg:grid-cols-[1fr_14rem]">
                        <form method="POST" action="{{ route('profil.pengaturan.password') }}" class="space-y-5 px-6 py-6">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kata Sandi Saat Ini</label>
                                <div class="relative">
                                    <input :type="showLama ? 'text' : 'password'" name="password_saat_ini" required
                                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                    <button type="button" @click="showLama = !showLama" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                                        <svg x-show="!showLama" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <svg x-show="showLama" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                    </button>
                                </div>
                                @error('password_saat_ini')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kata Sandi Baru</label>
                                <div class="relative">
                                    <input :type="showBaru ? 'text' : 'password'" name="password" required x-model="sandi"
                                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                    <button type="button" @click="showBaru = !showBaru" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                                        <svg x-show="!showBaru" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <svg x-show="showBaru" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                    </button>
                                </div>

                                {{-- Indikator kekuatan kata sandi --}}
                                <div class="mt-2" x-show="sandi.length > 0" x-cloak>
                                    <div class="flex gap-1">
                                        <template x-for="i in 4" :key="i">
                                            <span class="h-1.5 flex-1 rounded-full transition-colors" :class="i <= (
                                                    (sandi.length >= 8 ? 1 : 0) +
                                                    (/[A-Z]/.test(sandi) ? 1 : 0) +
                                                    (/[0-9]/.test(sandi) ? 1 : 0) +
                                                    (/[^A-Za-z0-9]/.test(sandi) ? 1 : 0)
                                                ) ? [' bg-rose-400', ' bg-rose-400', ' bg-amber-400', ' bg-emerald-500'][(
                                                    (sandi.length >= 8 ? 1 : 0) +
                                                    (/[A-Z]/.test(sandi) ? 1 : 0) +
                                                    (/[0-9]/.test(sandi) ? 1 : 0) +
                                                    (/[^A-Za-z0-9]/.test(sandi) ? 1 : 0)
                                                ) - 1] : ' bg-slate-200'">
                                            </span>
                                        </template>
                                    </div>
                                    <p class="mt-1.5 text-[11px] text-slate-400" x-text="
                                        (
                                            (sandi.length >= 8 ? 1 : 0) +
                                            (/[A-Z]/.test(sandi) ? 1 : 0) +
                                            (/[0-9]/.test(sandi) ? 1 : 0) +
                                            (/[^A-Za-z0-9]/.test(sandi) ? 1 : 0)
                                        ) <= 1 ? 'Lemah — tambahkan huruf besar, angka, atau simbol' :
                                        (
                                            (sandi.length >= 8 ? 1 : 0) +
                                            (/[A-Z]/.test(sandi) ? 1 : 0) +
                                            (/[0-9]/.test(sandi) ? 1 : 0) +
                                            (/[^A-Za-z0-9]/.test(sandi) ? 1 : 0)
                                        ) == 2 ? 'Sedang — cukup baik, bisa lebih kuat' : 'Kuat — kata sandi ini cukup aman'
                                    "></p>
                                </div>
                                @error('password')
                                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi Kata Sandi Baru</label>
                                <div class="relative">
                                    <input :type="showKonfirmasi ? 'text' : 'password'" name="password_confirmation" required
                                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                    <button type="button" @click="showKonfirmasi = !showKonfirmasi" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600">
                                        <svg x-show="!showKonfirmasi" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <svg x-show="showKonfirmasi" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                                <button type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    Perbarui Kata Sandi
                                </button>
                            </div>
                        </form>

                        {{-- Panel tips samping --}}
                        <div class="border-t border-slate-100 bg-slate-50 px-5 py-6 lg:border-l lg:border-t-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tips Kata Sandi Kuat</p>
                            <ul class="mt-3 space-y-2.5 text-xs text-slate-500">
                                <li class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Minimal 8 karakter
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Kombinasi huruf besar &amp; kecil
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Sertakan angka &amp; simbol
                                </li>
                                <li class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Jangan gunakan ulang sandi lama
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection