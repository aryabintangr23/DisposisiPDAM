@extends('layouts.app')

@section('title', 'Pesan')

@section('content')
    <div class="mt-6 mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Pesan</h2>
        <p class="mt-1 text-sm text-slate-500">Pesan otomatis dari notifikasi disposisi surat.</p>
    </div>

    {{-- Filter arah surat: menyaring daftar pesan di halaman ini saja. --}}
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <a href="{{ route('pesan.index') }}"
           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition {{ ! $arah ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            Semua
        </a>
        <a href="{{ route('pesan.index', ['arah' => 'masuk']) }}"
           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $arah === 'masuk' ? 'bg-sky-600 text-white' : 'bg-sky-50 text-sky-700 hover:bg-sky-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" /></svg>
            Surat Masuk
        </a>
        <a href="{{ route('pesan.index', ['arah' => 'keluar']) }}"
           class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $arah === 'keluar' ? 'bg-violet-600 text-white' : 'bg-violet-50 text-violet-700 hover:bg-violet-100' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
            Surat Keluar
        </a>
    </div>

    <form method="POST" action="{{ route('pesan.hapus') }}"
          x-data="{ selected: [], allIds: {{ $pesan->pluck('id')->map(fn ($id) => (string) $id)->toJson() }} }">
        @csrf

        <div class="mb-3 flex flex-wrap items-center justify-between gap-2" x-show="selected.length > 0" x-cloak>
            <p class="text-sm text-slate-500"><span x-text="selected.length"></span> pesan dipilih</p>
            <div class="flex items-center gap-2">
                <button type="submit" formaction="{{ route('pesan.hapus') }}"
                        onclick="return confirm('Pindahkan pesan yang dipilih ke tempat sampah?')"
                        class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Pindahkan ke Sampah
                </button>
            </div>
        </div>

        <div class="mb-2 flex items-center gap-2 px-1">
            <input type="checkbox"
                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                   :checked="allIds.length > 0 && selected.length === allIds.length"
                   @change="selected = $event.target.checked ? [...allIds] : []">
            <span class="text-xs font-medium text-slate-500">Pilih semua</span>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <ul class="divide-y divide-slate-100">
                @forelse ($pesan as $item)
                    <li class="flex items-center gap-3 px-3">
                        <input type="checkbox" name="ids[]" value="{{ $item->id }}" x-model="selected"
                               class="h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500">

                        <a href="{{ route('pesan.show', $item) }}" class="flex flex-1 items-center gap-4 py-4 transition hover:bg-slate-50">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                                {{ strtoupper(substr($item->penerima->nama, 0, 1)) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="truncate text-sm font-medium text-slate-700">
                                        Kepada: {{ $item->penerima->nama }}
                                    </p>
                                    <span class="shrink-0 text-xs text-slate-400">{{ $item->created_at->format('d-m-Y H:i') }}</span>
                                </div>
                                <p class="truncate text-sm text-slate-600">
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
                            <p class="text-sm">Belum ada pesan terkirim.</p>
                        </div>
                    </li>
                @endforelse
            </ul>
        </div>
    </form>

    <div class="mt-5">
        {{ $pesan->links() }}
    </div>
@endsection
