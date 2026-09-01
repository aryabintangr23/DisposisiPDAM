@extends('layouts.app')

@section('title', 'Tempat Sampah Surat')

@section('content')
    <div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Tempat Sampah Surat</h2>
            <p class="mt-1 text-sm text-slate-500">Surat di sini bisa dipulihkan, atau dihapus permanen.</p>
        </div>

        <a href="{{ route('surat.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Daftar Surat
        </a>
    </div>

    <div x-data="{ selected: [], allIds: {{ $surat->pluck('id')->map(fn ($id) => (string) $id)->toJson() }} }">
        <div class="mb-3 flex items-center justify-between" x-show="selected.length > 0" x-cloak>
            <p class="text-sm text-slate-500"><span x-text="selected.length"></span> surat dipilih</p>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('surat.pulihkan') }}" x-ref="formPulihkan">
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
                <form method="POST" action="{{ route('surat.hapusPermanen') }}"
                      onsubmit="return confirm('Hapus permanen? File lampiran dan seluruh riwayat disposisinya akan ikut terhapus dan TIDAK BISA dikembalikan lagi.')">
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

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-10 px-5 py-3">
                                <input type="checkbox"
                                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                       :checked="allIds.length > 0 && selected.length === allIds.length"
                                       @change="selected = $event.target.checked ? [...allIds] : []">
                            </th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Nomor Surat</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Perihal</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Dihapus pada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($surat as $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3.5">
                                    <input type="checkbox" value="{{ $item->id }}" x-model="selected"
                                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-slate-800">{{ $item->nomor_surat }}</td>
                                <td class="max-w-md px-5 py-3.5 text-slate-600">
                                    <span class="line-clamp-2">{{ $item->perihal }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $item->deleted_at?->format('d-m-Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                                    <p class="text-sm">Tempat sampah kosong.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-5">
        {{ $surat->links() }}
    </div>
@endsection
