@extends('layouts.app')

@section('title', 'Pesan')

@section('content')
    <div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Pesan</h2>
            <p class="mt-1 text-sm text-slate-500">Kirim dan terima pesan internal antar pengguna.</p>
        </div>

        <a href="{{ route('pesan.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tulis Pesan
        </a>
    </div>

    <div class="mb-4 flex gap-1 border-b border-slate-200">
        <a href="{{ route('pesan.index', ['tab' => 'inbox']) }}"
           class="border-b-2 px-4 py-2.5 text-sm font-semibold transition
                  {{ $tab === 'inbox' ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Kotak Masuk
        </a>
        <a href="{{ route('pesan.index', ['tab' => 'sent']) }}"
           class="border-b-2 px-4 py-2.5 text-sm font-semibold transition
                  {{ $tab === 'sent' ? 'border-brand-700 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Terkirim
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <ul class="divide-y divide-slate-100">
            @forelse ($pesan as $item)
                <li>
                    <a href="{{ route('pesan.show', $item) }}" class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-50">
                        {{-- Titik indikator belum dibaca (hanya relevan di Kotak Masuk) --}}
                        <span class="flex h-2.5 w-2.5 shrink-0 items-center justify-center">
                            @if ($tab === 'inbox' && ! $item->is_read)
                                <span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span>
                            @endif
                        </span>

                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                            {{ strtoupper(substr($tab === 'inbox' ? $item->pengirim->nama : $item->penerima->nama, 0, 1)) }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate text-sm {{ $tab === 'inbox' && ! $item->is_read ? 'font-bold text-slate-900' : 'font-medium text-slate-700' }}">
                                    {{ $tab === 'inbox' ? $item->pengirim->nama : 'Kepada: '.$item->penerima->nama }}
                                </p>
                                <span class="shrink-0 text-xs text-slate-400">{{ $item->created_at->format('d-m-Y H:i') }}</span>
                            </div>
                            <p class="truncate text-sm {{ $tab === 'inbox' && ! $item->is_read ? 'font-semibold text-slate-800' : 'text-slate-600' }}">
                                {{ $item->subject }}
                                @if ($item->surat_id)
                                    <span class="ml-1 inline-flex items-center rounded-full bg-brand-50 px-1.5 py-0.5 text-[10px] font-semibold text-brand-700 align-middle">Disposisi</span>
                                @endif
                            </p>
                            <p class="truncate text-xs text-slate-400">{{ Str::limit(strip_tags($item->body), 90) }}</p>
                        </div>
                    </a>
                </li>
            @empty
                <li class="px-5 py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm">{{ $tab === 'inbox' ? 'Belum ada pesan masuk.' : 'Belum ada pesan terkirim.' }}</p>
                    </div>
                </li>
            @endforelse
        </ul>
    </div>

    <div class="mt-5">
        {{ $pesan->links() }}
    </div>
@endsection
