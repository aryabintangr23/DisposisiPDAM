@extends('layouts.app')

@section('title', 'Tempat Sampah Pesan')

@section('content')
    <div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Tempat Sampah Pesan</h2>
            <p class="mt-1 text-sm text-slate-500">Pesan di sini bisa dipulihkan, atau dihapus permanen.</p>
        </div>

        <a href="{{ route('pesan.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Pesan
        </a>
    </div>

    <div x-data="{ selected: [], allIds: {{ $pesan->pluck('id')->map(fn ($id) => (string) $id)->toJson() }} }">
        <div class="mb-3 flex items-center justify-between" x-show="selected.length > 0" x-cloak>
            <p class="text-sm text-slate-500"><span x-text="selected.length"></span> pesan dipilih</p>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('pesan.pulihkan') }}">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Pulihkan
                    </button>
                </form>
                <form method="POST" action="{{ route('pesan.hapusPermanen') }}"
                      onsubmit="return confirm('Hapus permanen? Pesan ini tidak bisa dikembalikan lagi.')">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        Hapus Permanen
                    </button>
                </form>
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
                    <li class="flex items-center gap-4 px-5 py-4">
                        <input type="checkbox" value="{{ $item->id }}" x-model="selected"
                               class="h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate text-sm font-medium text-slate-700">
                                    {{ $item->pengirim->nama }} &rarr; {{ $item->penerima->nama }}
                                </p>
                                <span class="shrink-0 text-xs text-slate-400">Dihapus {{ $item->deleted_at?->format('d-m-Y H:i') }}</span>
                            </div>
                            <p class="truncate text-sm text-slate-600">{{ $item->subject }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-slate-400">
                        <p class="text-sm">Tempat sampah kosong.</p>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-5">
        {{ $pesan->links() }}
    </div>
@endsection
