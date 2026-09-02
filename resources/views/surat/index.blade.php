@extends('layouts.app')

@section('title', 'Daftar Surat')

@section('content')
    <div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Daftar Surat</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola surat masuk &amp; keluar beserta status disposisinya.</p>
        </div>

        <div class="flex items-center gap-2">
            @if (auth()->user()->isStaff())
                <a href="{{ route('surat.create') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Input Surat Baru
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

    {{-- ============ KOLOM UTAMA: DAFTAR SURAT ============ --}}
    <div class="lg:col-span-3">

    @if ($tanggal)
        <div class="mb-4">
            <a href="{{ route('surat.index') }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-100">
                {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('surat.hapus') }}"
          x-data="{ selected: [], allIds: {{ $surat->pluck('id')->map(fn ($id) => (string) $id)->toJson() }} }">
        @csrf

        @if (auth()->user()->isStaff())
            <div class="mb-3 flex items-center justify-between" x-show="selected.length > 0" x-cloak>
                <p class="text-sm text-slate-500"><span x-text="selected.length"></span> surat dipilih</p>
                <button type="submit"
                        onclick="return confirm('Pindahkan surat yang dipilih ke tempat sampah?')"
                        class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Pindahkan ke Sampah
                </button>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            @if (auth()->user()->isStaff())
                                <th class="w-10 px-5 py-3">
                                    <input type="checkbox"
                                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                           :checked="allIds.length > 0 && selected.length === allIds.length"
                                           @change="selected = $event.target.checked ? [...allIds] : []">
                                </th>
                            @endif
                            <th class="px-5 py-3 text-left font-semibold text-slate-600"><span class="sr-only">Prioritas</span></th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Nomor Surat</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Tanggal</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Perihal</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Arah</th>
                            <th class="px-5 py-3 text-left font-semibold text-slate-600">Status</th>
                            <th class="px-5 py-3 text-right font-semibold text-slate-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($surat as $item)
                            @php
                                $prioritasTerakhir = $item->disposisi->last()?->prioritas;
                                $dotColor = match ($prioritasTerakhir?->value) {
                                    'sangat_segera' => 'bg-red-500',
                                    'segera' => 'bg-yellow-400',
                                    'biasa' => 'bg-green-500',
                                    'tunggu_petunjuk' => 'bg-blue-500',
                                    default => 'bg-slate-300',
                                };
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                @if (auth()->user()->isStaff())
                                    <td class="px-5 py-3.5">
                                        <input type="checkbox" name="ids[]" value="{{ $item->id }}" x-model="selected"
                                               class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    </td>
                                @endif
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full {{ $dotColor }}"
                                          title="Prioritas: {{ $prioritasTerakhir?->label() ?? 'Belum ada disposisi' }}"></span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-medium text-slate-800">{{ $item->nomor_surat }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $item->tanggal_surat?->format('d-m-Y') }}</td>
                                <td class="max-w-xs px-5 py-3.5 text-slate-600">
                                    <span class="line-clamp-2">{{ $item->perihal }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    @if ($item->arah_surat->value === 'masuk')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16l-4-4m0 0l4-4m-4 4h18" /></svg>
                                            {{ $item->arah_surat->label() }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                            {{ $item->arah_surat->label() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    @php
                                        $statusColor = match ($item->status->value) {
                                            'baru' => 'bg-amber-50 text-amber-700',
                                            'diterima' => 'bg-emerald-50 text-emerald-700',
                                            'ditolak' => 'bg-rose-50 text-rose-700',
                                            'perlu_revisi' => 'bg-orange-50 text-orange-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusColor }}">
                                        {{ $item->status->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                    <a href="{{ route('surat.show', $item) }}"
                                       class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-brand-700 transition hover:bg-brand-50">
                                        Detail
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        <p class="text-sm">
                                            @if ($tanggal)
                                                Tidak ada surat pada tanggal ini.
                                            @else
                                                Belum ada surat.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <div class="mt-5">
        {{ $surat->links() }}
    </div>

    </div>
    {{-- /kolom utama --}}

    {{-- ============ KOLOM SAMPING: KALENDER ============ --}}
    <div class="lg:col-span-1">
        @php
            $bulanAwal = \Carbon\Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
            $bulanSebelumnya = $bulanAwal->copy()->subMonth()->format('Y-m');
            $bulanSesudahnya = $bulanAwal->copy()->addMonth()->format('Y-m');
            $mulaiGrid = $bulanAwal->copy()->startOfWeek(\Carbon\Carbon::SUNDAY);
            $selesaiGrid = $bulanAwal->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SATURDAY);
            $hariIni = now()->format('Y-m-d');
        @endphp

        <div class="sticky top-20 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <a href="{{ route('surat.index', array_filter(['bulan' => $bulanSebelumnya])) }}"
                   class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <p class="text-sm font-semibold text-slate-700">{{ $bulanAwal->translatedFormat('F Y') }}</p>
                <a href="{{ route('surat.index', array_filter(['bulan' => $bulanSesudahnya])) }}"
                   class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-slate-400">
                <span>M</span><span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span>
            </div>

            <div class="mt-1 grid grid-cols-7 gap-1">
                @php $kursor = $mulaiGrid->copy(); @endphp
                @while ($kursor->lte($selesaiGrid))
                    @php
                        $iso = $kursor->format('Y-m-d');
                        $dalamBulanIni = $kursor->format('Y-m') === $bulan;
                        $adaSurat = $tanggalBersurat->contains($iso);
                        $terpilih = $tanggal === $iso;
                        $hariBerjalan = $iso === $hariIni;
                    @endphp
                    <a href="{{ route('surat.index', array_filter(['bulan' => $bulan, 'tanggal' => $terpilih ? null : $iso])) }}"
                       class="relative flex h-8 items-center justify-center rounded-lg text-xs transition
                              {{ ! $dalamBulanIni ? 'text-slate-300' : 'text-slate-600 hover:bg-slate-100' }}
                              {{ $terpilih ? 'bg-brand-700 text-white hover:bg-brand-700' : '' }}
                              {{ $hariBerjalan && ! $terpilih ? 'font-bold text-brand-700 ring-1 ring-inset ring-brand-200' : '' }}">
                        {{ $kursor->day }}
                        @if ($adaSurat)
                            <span class="absolute bottom-0.5 h-1 w-1 rounded-full {{ $terpilih ? 'bg-white' : 'bg-brand-600' }}"></span>
                        @endif
                    </a>
                    @php $kursor->addDay(); @endphp
                @endwhile
            </div>

            <p class="mt-3 flex items-center gap-1.5 text-[11px] text-slate-400">
                <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                Tanggal dengan surat
            </p>
        </div>
    </div>

    </div>
@endsection
