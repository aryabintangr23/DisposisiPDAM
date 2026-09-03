@extends('layouts.app')

@section('title', 'Input Surat Baru')

@section('content')
    <div class="mt-6 mb-6">
        <a href="{{ route('surat.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Daftar Surat
        </a>
        <h2 class="mt-2 text-2xl font-bold text-slate-800">Input Surat Baru</h2>
        <p class="mt-1 text-sm text-slate-500">Lengkapi data surat dan kirim lembar disposisi pertama ke Kabag Umum.</p>
    </div>

    <form method="POST" action="{{ route('surat.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Section: Data Surat --}}
        {{-- x-data di sini mengatur tampil/sembunyinya field "Surat Dari" &
             "Tujuan Surat" mengikuti pilihan Arah Surat: kalau surat masuk,
             field "Tujuan Surat" tidak perlu diisi (disembunyikan); kalau
             surat keluar, field "Surat Dari" yang disembunyikan. --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ arahSurat: '{{ old('arah_surat', 'masuk') }}' }">
            <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-brand-700">Data Surat</h3>
            <p class="mb-5 text-sm text-slate-500">Informasi dasar mengenai surat yang diterima/dikirim.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Arah Surat</label>
                    <select name="arah_surat" required x-model="arahSurat"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <option value="masuk">Surat Masuk</option>
                        <option value="keluar">Surat Keluar</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Jenis Surat</label>
                    <input type="text" name="jenis_surat" value="{{ old('jenis_surat') }}" required
                        placeholder="Contoh: Surat Undangan"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nomor Surat</label>
                    <input type="text" name="nomor_surat" value="{{ old('nomor_surat') }}" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nomor Agenda <span class="font-normal text-slate-400">(manual)</span></label>
                    <input type="text" name="nomor_agenda" value="{{ old('nomor_agenda') }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal Surat</label>
                    <input type="date" name="tanggal_surat" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal Diterima</label>
                    <input type="date" name="tanggal_diterima"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div x-show="arahSurat === 'masuk'" x-cloak>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Surat Dari</label>
                    <input type="text" name="surat_dari" :required="arahSurat === 'masuk'"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div x-show="arahSurat === 'keluar'" x-cloak>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tujuan Surat</label>
                    <input type="text" name="tujuan_surat" :required="arahSurat === 'keluar'"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>
            </div>

            <div class="mt-5">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Perihal</label>
                <textarea name="perihal" required rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">{{ old('perihal') }}</textarea>
            </div>

            <div class="mt-5">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Lampiran <span class="font-normal text-slate-400">(PDF, boleh lebih dari satu)</span></label>
                <div class="rounded-lg border-2 border-dashed border-slate-300 px-4 py-6 text-center transition hover:border-brand-400">
                    <input type="file" name="lampiran[]" multiple accept="application/pdf"
                        class="w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                </div>
            </div>
        </div>

        {{-- Section: Disposisi Pertama --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-brand-700">Lembar Disposisi Pertama</h3>
            <p class="mb-5 text-sm text-slate-500">Dikirim ke Kabag Umum untuk ditindaklanjuti.</p>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kirim ke</label>
                    <select name="penerima_id" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        @foreach ($kabagList as $kabag)
                            <option value="{{ $kabag->id }}">{{ $kabag->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Prioritas</label>
                    <select name="prioritas" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <option value="sangat_segera">Sangat Segera (3 hari)</option>
                        <option value="segera">Segera (5 hari)</option>
                        <option value="biasa">Biasa (7 hari)</option>
                        <option value="tunggu_petunjuk">Tunggu Petunjuk</option>
                    </select>
                </div>
            </div>

            <div class="mt-5">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Instruksi</label>
                <textarea name="instruksi" rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('surat.index') }}"
               class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                Simpan &amp; Kirim Disposisi
            </button>
        </div>
    </form>
@endsection
