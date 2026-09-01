@extends('layouts.app')

@section('title', 'Detail Pesan')

@section('content')
    <div class="mt-6 mb-6">
        <a href="{{ route('pesan.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Pesan
        </a>
    </div>

    <div class="max-w-2xl overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-xl font-bold text-slate-800">{{ $pesan->subject }}</h2>
            @if ($pesan->surat_id)
                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Notifikasi Disposisi
                </span>
            @endif
        </div>

        <div class="mt-4 flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                {{ strtoupper(substr($pesan->pengirim->nama, 0, 1)) }}
            </div>
            <div class="min-w-0 leading-tight">
                <p class="text-sm font-semibold text-slate-800">
                    {{ $pesan->pengirim->nama }}
                    <span class="font-normal text-slate-400">({{ ucwords(str_replace('_',' ',$pesan->pengirim->role->nama_role)) }})</span>
                </p>
                <p class="text-xs text-slate-400">
                    kepada {{ $pesan->penerima->nama }} &middot; {{ $pesan->created_at->format('d-m-Y H:i') }}
                </p>
            </div>
        </div>

        <div class="mt-5 whitespace-pre-line rounded-lg bg-slate-50 px-4 py-4 text-sm leading-relaxed text-slate-700">
            {{ $pesan->body }}
        </div>

        <div class="mt-5 flex flex-wrap gap-3">
            @if ($pesan->surat_id)
                <a href="{{ route('surat.show', $pesan->surat_id) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    Lihat Surat
                </a>
            @endif
            <a href="{{ route('pesan.create', ['to' => $pesan->sender_id === auth()->id() ? $pesan->receiver_id : $pesan->sender_id, 'subjek' => 'Re: '.$pesan->subject]) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Balas
            </a>
        </div>
    </div>
@endsection
