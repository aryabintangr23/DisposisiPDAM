@extends('layouts.app')

@section('title', 'Edit Surat')

@section('content')
    <div class="mt-6 mb-6">
        <a href="{{ route('surat.show', $surat) }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Detail Surat
        </a>
        <h2 class="mt-2 text-2xl font-bold text-slate-800">Edit Surat</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $surat->nomor_surat }} — {{ $surat->perihal }}</p>
    </div>

    <form method="POST" action="{{ route('surat.update', $surat) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- x-data mengatur tampil/sembunyinya field "Surat Dari" & "Tujuan
             Surat" mengikuti pilihan Arah Surat, sama seperti form input. --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ arahSurat: '{{ old('arah_surat', $surat->arah_surat->value) }}' }">
            <h3 class="mb-1 text-sm font-semibold uppercase tracking-wide text-brand-700">Data Surat</h3>
            <p class="mb-5 text-sm text-slate-500">Perbarui informasi dasar mengenai surat ini.</p>

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
                    <input type="text" name="jenis_surat" value="{{ old('jenis_surat', $surat->jenis_surat) }}" required
                        placeholder="Contoh: Surat Undangan"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nomor Surat</label>
                    <input type="text" name="nomor_surat" value="{{ old('nomor_surat', $surat->nomor_surat) }}" required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nomor Agenda <span class="font-normal text-slate-400">(manual)</span></label>
                    <input type="text" name="nomor_agenda" value="{{ old('nomor_agenda', $surat->nomor_agenda) }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal Surat</label>
                    <input type="date" name="tanggal_surat" required value="{{ old('tanggal_surat', $surat->tanggal_surat?->format('Y-m-d')) }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal Diterima</label>
                    <input type="date" name="tanggal_diterima" value="{{ old('tanggal_diterima', $surat->tanggal_diterima?->format('Y-m-d')) }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div x-show="arahSurat === 'masuk'" x-cloak>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Surat Dari</label>
                    <input type="text" name="surat_dari" :required="arahSurat === 'masuk'" value="{{ old('surat_dari', $surat->surat_dari) }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>

                <div x-show="arahSurat === 'keluar'" x-cloak>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tujuan Surat</label>
                    <input type="text" name="tujuan_surat" :required="arahSurat === 'keluar'" value="{{ old('tujuan_surat', $surat->tujuan_surat) }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                </div>
            </div>

            <div class="mt-5">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Perihal</label>
                <textarea name="perihal" required rows="3"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">{{ old('perihal', $surat->perihal) }}</textarea>
            </div>

            @if ($surat->lampiran->isNotEmpty())
                <div class="mt-5">
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Lampiran Saat Ini</label>
                    <ul class="space-y-1.5">
                        @foreach ($surat->lampiran as $file)
                            <li class="flex items-center gap-2 text-sm text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <a href="{{ asset('storage/'.$file->path_file) }}" target="_blank" class="hover:text-brand-700 hover:underline">{{ $file->nama_file }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-5">
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tambah Lampiran <span class="font-normal text-slate-400">(PDF, JPG, atau DOCX, opsional, boleh lebih dari satu)</span></label>
                <div class="rounded-lg border-2 border-dashed border-slate-300 px-4 py-6 text-center transition hover:border-brand-400">
                    <input type="file" name="lampiran[]" multiple accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg,.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                        class="w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('surat.show', $surat) }}"
               class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                Batal
            </a>
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                Simpan Perubahan
            </button>
        </div>
    </form>
@endsection
