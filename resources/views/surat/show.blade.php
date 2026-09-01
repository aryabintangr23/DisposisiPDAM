@extends('layouts.app')

@section('title', $surat->nomor_surat)

@section('content')
    <div class="mt-6 mb-6">
        <a href="{{ route('surat.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Daftar Surat
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-bold text-slate-800">{{ $surat->nomor_surat }}</h2>
            @php
                $statusColor = match ($surat->status->value) {
                    'baru' => 'bg-amber-50 text-amber-700',
                    'diterima' => 'bg-emerald-50 text-emerald-700',
                    'ditolak' => 'bg-rose-50 text-rose-700',
                    'perlu_revisi' => 'bg-orange-50 text-orange-700',
                    default => 'bg-slate-100 text-slate-600',
                };
            @endphp
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                {{ $surat->status->label() }}
            </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $surat->perihal }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Kolom kiri: info surat + lampiran --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-brand-700">Informasi Surat</h3>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Arah</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->arah_surat->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Jenis Surat</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->jenis_surat }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Nomor Agenda</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->nomor_agenda ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Tanggal Surat</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->tanggal_surat?->format('d-m-Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Tanggal Diterima</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->tanggal_diterima?->format('d-m-Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Surat Dari</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->surat_dari ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Tujuan Surat</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->tujuan_surat ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Dibuat oleh</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->pembuat->nama }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-400">Perihal</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->perihal }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-brand-700">Lampiran</h3>
                @forelse ($surat->lampiran as $file)
                    <div class="mb-5 overflow-hidden rounded-lg border border-slate-200 last:mb-0">
                        <div class="flex items-center justify-between gap-3 bg-slate-50 px-4 py-2.5">
                            <div class="flex min-w-0 items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="truncate text-sm font-medium text-slate-700">{{ $file->nama_file }}</span>
                                <span class="shrink-0 text-xs text-slate-400">({{ number_format($file->ukuran_file / 1024, 0) }} KB)</span>
                            </div>
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank"
                               class="shrink-0 text-xs font-semibold text-brand-700 hover:underline">Buka di tab baru</a>
                        </div>
                        {{-- Preview inline, aman karena lampiran dibatasi tipe PDF saja --}}
                        <iframe src="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" class="h-[500px] w-full border-0"></iframe>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada lampiran.</p>
                @endforelse
            </div>
        </div>

        {{-- Kolom kanan: riwayat disposisi + form kirim baru --}}
        <div class="space-y-6">

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-brand-700">Riwayat Disposisi</h3>

                @php
                    $prioritasColor = fn ($p) => match ($p) {
                        'sangat_segera' => 'bg-rose-50 text-rose-700',
                        'segera' => 'bg-orange-50 text-orange-700',
                        'biasa' => 'bg-sky-50 text-sky-700',
                        default => 'bg-slate-100 text-slate-600',
                    };
                    $dispoStatusColor = fn ($s) => match ($s) {
                        'selesai' => 'bg-emerald-50 text-emerald-700',
                        'ditindaklanjuti' => 'bg-sky-50 text-sky-700',
                        'dibaca' => 'bg-indigo-50 text-indigo-700',
                        'diterima' => 'bg-amber-50 text-amber-700',
                        default => 'bg-slate-100 text-slate-600',
                    };
                @endphp

                <ol class="relative space-y-6 border-l-2 border-slate-100 pl-5">
                    @foreach ($surat->disposisi as $d)
                        <li class="relative">
                            <span class="absolute -left-[27px] top-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-white bg-brand-500 ring-2 ring-brand-100"></span>

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $prioritasColor($d->prioritas->value) }}">
                                    {{ $d->prioritas->label() }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $dispoStatusColor($d->status->value) }}">
                                    {{ $d->status->label() }}
                                </span>
                            </div>

                            <p class="mt-1.5 text-sm text-slate-700">
                                <span class="font-medium">{{ $d->pengirim->nama }}</span>
                                <span class="text-slate-400">({{ ucwords(str_replace('_',' ',$d->pengirim->role->nama_role)) }})</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-1 inline h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                <span class="font-medium">{{ $d->penerima->nama }}</span>
                                <span class="text-slate-400">({{ ucwords(str_replace('_',' ',$d->penerima->role->nama_role)) }})</span>
                            </p>

                            <p class="mt-1 text-xs text-slate-400">
                                {{ $d->tanggal_disposisi?->format('d-m-Y') }}
                                @if ($d->batas_waktu) &middot; Batas waktu {{ $d->batas_waktu->format('d-m-Y') }} @endif
                            </p>

                            @if ($d->instruksi)
                                <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ $d->instruksi }}</p>
                            @endif

                            <div class="mt-2 flex items-center gap-3">
                                <a href="{{ route('disposisi.cetak', [$surat, $d]) }}" target="_blank"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-8 4h8v-6H8v6z" /></svg>
                                    Cetak PDF
                                </a>

                                @if (auth()->user()->isStaff() && $d->penerima_id === auth()->id() && $d->status->value !== 'selesai')
                                    <form method="POST" action="{{ route('disposisi.selesaikan', [$surat, $d]) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Tandai Selesai
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if ($penerimaOptions->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-brand-700">Kirim Disposisi Baru</h3>
                    <form method="POST" action="{{ route('disposisi.store', $surat) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Kirim ke</label>
                            <select name="penerima_id" required
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                @foreach ($penerimaOptions as $opt)
                                    <option value="{{ $opt->id }}">{{ $opt->nama }} ({{ ucwords(str_replace('_',' ',$opt->role->nama_role)) }})</option>
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
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Instruksi</label>
                            <textarea name="instruksi" rows="3"
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                        </div>
                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            Kirim Disposisi
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
