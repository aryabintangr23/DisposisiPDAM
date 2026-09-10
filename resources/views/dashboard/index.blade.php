@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Dashboard</h2>
            <p class="mt-1 text-sm text-slate-500">
                @if ($lihatStatistik)
                    Ringkasan surat masuk &amp; keluar dalam cakupan role Anda.
                @else
                    Peringatan surat yang mendekati batas waktu prioritas.
                @endif
            </p>
        </div>

        <a href="{{ route('surat.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Lihat Daftar Surat
        </a>
    </div>

    {{-- Peringatan Surat Mendekati Batas Waktu --}}
    @if ($disposisiMendekati->isNotEmpty())
        <div class="mb-6 overflow-hidden rounded-xl border border-amber-200 bg-amber-50 shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-amber-200 bg-amber-100/60 px-5 py-3">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <h3 class="text-sm font-bold text-amber-800">Surat Mendekati Batas Waktu Prioritas</h3>
                </div>
                <span class="rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-bold text-white">{{ number_format($disposisiMendekati->count()) }}</span>
            </div>

            <ul class="divide-y divide-amber-200/60">
                @foreach ($disposisiMendekati as $d)
                    @php
                        $sisaHari = (int) now()->startOfDay()->diffInDays($d->batas_waktu, false);
                        $sisaHariText = $sisaHari <= 0
                            ? 'Tenggat hari ini'
                            : ($sisaHari === 1 ? 'Besok' : $sisaHari.' hari lagi');
                    @endphp
                    <li>
                        <a href="{{ route('surat.show', $d->surat) }}" class="group flex flex-col gap-1 px-5 py-3 transition hover:bg-amber-100/60 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800 group-hover:text-amber-800">
                                    {{ $d->surat->nomor_surat }}
                                    <span class="font-normal text-slate-400">&middot;</span>
                                    <span class="font-normal text-slate-600">{{ $d->surat->perihal }}</span>
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Prioritas {{ $d->prioritas->label() }} &middot; Batas waktu {{ $d->batas_waktu->translatedFormat('d F Y') }}
                                </p>
                            </div>
                            <div class="mt-1 flex shrink-0 items-center gap-2 sm:mt-0">
                                <span class="rounded-full bg-amber-500 px-2.5 py-1 text-[11px] font-bold text-white">{{ $sisaHariText }}</span>
                                <span class="text-xs font-semibold text-amber-700 group-hover:underline">Detail &rarr;</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif (!$lihatStatistik)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            <p>Tidak ada surat yang mendekati batas waktu prioritas saat ini.</p>
        </div>
    @endif

    {{-- Kartu Ringkasan & Grafik (Staff, Admin, Kabag) --}}
    @if ($lihatStatistik)
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <a href="{{ route('surat.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Surat</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($jumlahSurat) }}</p>
                <p class="mt-1 text-xs text-slate-400">Semua arah</p>
            </a>
            <a href="{{ route('surat.index', ['arah' => 'masuk']) }}" class="rounded-xl border border-sky-200 bg-white p-5 shadow-sm transition hover:shadow">
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-600">Surat Masuk</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($jumlahMasuk) }}</p>
                <p class="mt-1 text-xs text-slate-400">Diterima dari luar</p>
            </a>
            <a href="{{ route('surat.index', ['arah' => 'keluar']) }}" class="rounded-xl border border-violet-200 bg-white p-5 shadow-sm transition hover:shadow">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-600">Surat Keluar</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($jumlahKeluar) }}</p>
                <p class="mt-1 text-xs text-slate-400">Dikirim ke luar</p>
            </a>
        </div>

        {{-- Grafik Statistik --}}
        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            {{-- Grafik Surat per Bulan --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-bold text-slate-700">Jumlah Surat per Bulan</h3>
                    <div class="flex items-center gap-3 text-[11px] text-slate-500">
                        <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-sky-500"></span> Masuk</span>
                        <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-violet-500"></span> Keluar</span>
                    </div>
                </div>

                @php
                    $maksBulan = max(1, collect($statistikPerBulan)->max(fn ($b) => $b['masuk'] + $b['keluar']));
                @endphp
                <div class="mt-4 flex h-40 items-end gap-2">
                    @foreach ($statistikPerBulan as $bulanData)
                        @php
                            $tinggiMasuk = $bulanData['masuk'] > 0 ? max(6, round($bulanData['masuk'] / $maksBulan * 100)) : 0;
                            $tinggiKeluar = $bulanData['keluar'] > 0 ? max(6, round($bulanData['keluar'] / $maksBulan * 100)) : 0;
                            $totalBulan = $bulanData['masuk'] + $bulanData['keluar'];
                        @endphp
                        <div class="flex flex-1 flex-col items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-700" title="Total {{ $totalBulan }} surat">{{ $totalBulan }}</span>
                            <div class="flex w-full flex-1 items-end justify-center gap-0.5">
                                @if ($tinggiKeluar > 0)
                                    <div class="w-3 rounded-t bg-violet-500" style="height: {{ $tinggiKeluar }}px" title="{{ $bulanData['label'] }}: {{ $bulanData['keluar'] }} surat keluar"></div>
                                @endif
                                @if ($tinggiMasuk > 0)
                                    <div class="w-3 rounded-t bg-sky-500" style="height: {{ $tinggiMasuk }}px" title="{{ $bulanData['label'] }}: {{ $bulanData['masuk'] }} surat masuk"></div>
                                @endif
                            </div>
                            <span class="text-[10px] font-medium text-slate-400">{{ $bulanData['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Sebaran Status Surat --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold text-slate-700">Sebaran Status Surat</h3>

                @if ($statistikStatus->isNotEmpty())
                    @php $maksStatus = max(1, $statistikStatus->max('jumlah')); @endphp
                    <div class="mt-4 space-y-3">
                        @foreach ($statistikStatus as $item)
                            @php
                                $warnaStatus = match ($item['status']->value) {
                                    'baru' => 'bg-amber-400',
                                    'diterima' => 'bg-emerald-500',
                                    'ditolak' => 'bg-rose-500',
                                    'perlu_revisi' => 'bg-orange-500',
                                    default => 'bg-slate-400',
                                };
                            @endphp
                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-600">{{ $item['status']->label() }}</span>
                                    <span class="font-semibold text-slate-700">{{ number_format($item['jumlah']) }} surat</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full {{ $warnaStatus }}" style="width: {{ round($item['jumlah'] / $maksStatus * 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-400">Belum ada data surat.</p>
                @endif
            </div>
        </div>
    @endif
@endsection