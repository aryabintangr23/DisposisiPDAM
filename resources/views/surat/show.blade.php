@extends('layouts.app')

@section('title', $surat->nomor_surat)

@section('content')
    @php
        $dispoTerakhir = $surat->disposisiTerakhir();

        $prioritasBadgeColor = fn ($p) => match ($p) {
            'sangat_segera' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200',
            'segera' => 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-200',
            'biasa' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
            'tunggu_petunjuk' => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200',
            default => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
        };

        $sedangDitindaklanjuti = $surat->status->value === 'baru'
            && $dispoTerakhir
            && $dispoTerakhir->pengirim?->isKabag()
            && $dispoTerakhir->penerima?->isDirektur();

        // Untuk status "perlu revisi", edit hanya boleh selama disposisi masih di tangan
        // Staff (belum dikirim balik ke Kabag).
        $bisaEditSekarang = $surat->status->value === 'baru'
            || ($surat->status->value === 'perlu_revisi' && $dispoTerakhir && $dispoTerakhir->penerima_id === auth()->id());
    @endphp

    <div class="mt-6 mb-6">
        <a href="{{ route('surat.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-brand-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
            Kembali ke Daftar Surat
        </a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-bold text-slate-800">{{ $surat->nomor_surat }}</h2>
            @php
                $statusColor = $sedangDitindaklanjuti
                    ? 'bg-sky-50 text-sky-700'
                    : match ($surat->status->value) {
                        'baru' => 'bg-amber-50 text-amber-700',
                        'diterima' => 'bg-emerald-50 text-emerald-700',
                        'ditolak' => 'bg-rose-50 text-rose-700',
                        'perlu_revisi' => 'bg-orange-50 text-orange-700',
                        default => 'bg-slate-100 text-slate-600',
                    };

                $statusLabel = $sedangDitindaklanjuti
                    ? 'Sedang Ditindaklanjuti'
                    : $surat->status->label();
            @endphp
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                {{ $statusLabel }}
            </span>

            @if ($dispoTerakhir)
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $prioritasBadgeColor($dispoTerakhir->prioritas->value) }}" title="Prioritas berdasarkan disposisi terakhir">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Prioritas: {{ $dispoTerakhir->prioritas->label() }}
                </span>
            @endif

            @if (auth()->user()->isStaff() && $surat->created_by === auth()->id() && ! $sedangDitindaklanjuti && $bisaEditSekarang && in_array($surat->status->value, ['baru', 'perlu_revisi'], true))
                <a href="{{ route('surat.edit', $surat) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Surat
                </a>
            @endif
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $surat->perihal }}</p>

        @if ($dispoTerakhir && $dispoTerakhir->prioritas->batasHari())
            <p class="mt-1 text-xs text-slate-400">
                Target penyelesaian disposisi berjalan: {{ $dispoTerakhir->prioritas->batasHari() }} hari kalender sejak tanggal disposisi
                @if ($dispoTerakhir->batas_waktu)
                    (batas waktu {{ $dispoTerakhir->batas_waktu->format('d-m-Y') }})
                @endif
                .
            </p>
        @endif
    </div>

    @php
        $bisaMemutuskan = auth()->user()->isDirektur()
            && $dispoTerakhir
            && $dispoTerakhir->penerima_id === auth()->id()
            && $surat->status->value === 'baru';

        $perluRevisiUntukStaff = auth()->user()->isStaff()
            && $surat->created_by === auth()->id()
            && $surat->status->value === 'perlu_revisi'
            && $dispoTerakhir
            && $dispoTerakhir->penerima_id === auth()->id();

        $bisaReviewRevisi = auth()->user()->isKabag()
            && $dispoTerakhir
            && $dispoTerakhir->penerima_id === auth()->id()
            && $surat->status->value === 'perlu_revisi';

        $bisaReviewBaru = auth()->user()->isKabag()
            && $dispoTerakhir
            && $dispoTerakhir->penerima_id === auth()->id()
            && $dispoTerakhir->pengirim?->isStaff()
            && $surat->status->value === 'baru';
    @endphp

    @if ($perluRevisiUntukStaff)
        <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-orange-800">Perlu Revisi</h3>
            <p class="mt-1 text-sm text-orange-800/80">
                {{ $dispoTerakhir->pengirim->nama }} (Kabag) meminta Anda merevisi surat ini
                @if ($dispoTerakhir->instruksi)
                    dengan catatan: &ldquo;{{ $dispoTerakhir->instruksi }}&rdquo;.
                @else
                    .
                @endif
                Silakan edit data surat, lalu kirim kembali sebagai revisi ke Kabag.
            </p>
            <a href="{{ route('surat.edit', $surat) }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit &amp; Perbaiki Surat
            </a>
        </div>
    @endif

    @if ($bisaReviewBaru)
        <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800">Tinjau Surat Masuk</h3>
            <p class="mt-1 text-sm text-brand-800/80">
                {{ $dispoTerakhir->pengirim->nama }} (Staff) mengirim surat ini kepada Anda.
                Pilih <strong>Approve</strong> untuk menyetujui &mdash; {{ $dispoTerakhir->pengirim->nama }} akan diberi tahu dan surat otomatis diteruskan ke Direktur &mdash; atau <strong>Revisi</strong> untuk mengirimkannya kembali ke {{ $dispoTerakhir->pengirim->nama }} untuk diperbaiki.
            </p>
            <form method="POST" action="{{ route('disposisi.reviewBaru', $surat) }}" class="mt-4 space-y-3">
                @csrf
                <textarea name="catatan" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="keputusan" value="approve" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Approve
                    </button>
                    <button type="submit" name="keputusan" value="revisi" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Revisi
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($bisaReviewRevisi)
        <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800">Review Revisi</h3>
            <p class="mt-1 text-sm text-brand-800/80">
                {{ $dispoTerakhir->pengirim->nama }} (Staff) sudah mengirim kembali surat yang direvisi.
                Periksa perubahannya, lalu tandai <strong>Diterima</strong> kalau sudah sesuai, atau <strong>Minta Revisi Lagi</strong> kalau masih belum.
            </p>
            <form method="POST" action="{{ route('disposisi.reviewRevisi', $surat) }}" class="mt-4 space-y-3">
                @csrf
                <textarea name="catatan" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="keputusan" value="diterima" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Diterima
                    </button>
                    <button type="submit" name="keputusan" value="revisi" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Minta Revisi Lagi
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($bisaMemutuskan)
        <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800">Keputusan Surat</h3>
            <p class="mt-1 text-sm text-brand-800/80">
                Surat ini menunggu keputusan Anda. Pilih Terima atau Tolak — keputusan akan otomatis dikirim sebagai disposisi balasan ke {{ $dispoTerakhir->pengirim->nama }} (Kabag) dan status surat akan diperbarui.
            </p>
            <form method="POST" action="{{ route('disposisi.keputusan', $surat) }}" class="mt-4 space-y-3">
                @csrf
                <textarea name="catatan" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="keputusan" value="diterima" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Terima
                    </button>
                    <button type="submit" name="keputusan" value="ditolak" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        Tolak
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <!-- Kolom Kiri: Informasi Surat & Lampiran -->
        <div class="space-y-6 xl:col-span-3">
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
                    @php
                        $ekstensi = strtolower(pathinfo($file->nama_file, PATHINFO_EXTENSION));
                        $isGambar = in_array($ekstensi, ['jpg', 'jpeg', 'png'], true);
                        $isPdf = $ekstensi === 'pdf';
                        $iconColor = match (true) {
                            $isPdf => 'text-rose-500',
                            $isGambar => 'text-emerald-500',
                            default => 'text-blue-500',
                        };
                    @endphp
                    <div class="mb-5 overflow-hidden rounded-lg border border-slate-200 last:mb-0">
                        <div class="flex items-center justify-between gap-3 bg-slate-50 px-4 py-2.5">
                            <div class="flex min-w-0 items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="truncate text-sm font-medium text-slate-700">{{ $file->nama_file }}</span>
                                <span class="shrink-0 text-xs text-slate-400">({{ number_format($file->ukuran_file / 1024, 0) }} KB)</span>
                            </div>
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank" class="shrink-0 text-xs font-semibold text-brand-700 hover:underline">Buka di tab baru</a>
                        </div>

                        @if ($isPdf)
                            <iframe src="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" class="h-[500px] w-full border-0"></iframe>
                        @elseif ($isGambar)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" alt="{{ $file->nama_file }}" class="max-h-[500px] w-full object-contain bg-slate-100">
                        @else
                            <div class="flex flex-col items-center gap-2 px-4 py-8 text-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <p class="text-sm">Berkas Word tidak bisa dipratinjau di sini.</p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank" class="text-xs font-semibold text-brand-700 hover:underline">Unduh / buka berkas</a>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Tidak ada lampiran.</p>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: Riwayat Disposisi & Form Kirim Baru -->
        <div class="space-y-6 xl:col-span-2">
            @php
                $riwayatDisposisi = auth()->user()->isAdmin()
                    ? $surat->disposisi
                    : $surat->disposisi->filter(
                        fn ($d) => $d->pengirim_id === auth()->id() || $d->penerima_id === auth()->id()
                    );
            @endphp

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

                    $prioritasDotColor = fn ($p) => match ($p) {
                        'sangat_segera' => 'bg-red-500 ring-red-100',
                        'segera' => 'bg-yellow-400 ring-yellow-100',
                        'biasa' => 'bg-green-500 ring-green-100',
                        'tunggu_petunjuk' => 'bg-blue-500 ring-blue-100',
                        default => 'bg-slate-300 ring-slate-100',
                    };
                @endphp

                @if ($riwayatDisposisi->isNotEmpty())
                    <div class="-mx-1 overflow-x-auto pb-2 xl:overflow-visible">
                        <ol class="flex min-w-max items-start px-1 xl:min-w-0 xl:flex-wrap xl:gap-y-6">
                            @foreach ($riwayatDisposisi as $d)
                                <li class="flex w-64 shrink-0 flex-col items-stretch sm:w-72 xl:w-full 2xl:w-[calc(50%-0.5rem)]">
                                    <div class="flex items-center">
                                        <div class="h-0.5 flex-1 {{ $loop->first ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                        <span class="relative flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 border-white ring-2 {{ $prioritasDotColor($d->prioritas->value) }}" title="Prioritas: {{ $d->prioritas->label() }}">
                                            @if ($d->status->value !== 'selesai')
                                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $prioritasDotColor($d->prioritas->value) }} opacity-60"></span>
                                            @endif
                                        </span>
                                        <div class="h-0.5 flex-1 {{ $loop->last ? 'bg-transparent' : 'bg-slate-200' }}"></div>
                                    </div>

                                    <div class="mt-3 flex-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <div class="flex flex-wrap items-center gap-1.5">
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

                                        <div class="mt-2 flex flex-wrap items-center gap-3">
                                            <a href="{{ route('disposisi.cetak', [$surat, $d]) }}" target="_blank" data-turbo="false" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-8 4h8v-6H8v6z" /></svg>
                                                Cetak PDF
                                            </a>

                                            @if (auth()->user()->isStaff() && $d->penerima_id === auth()->id() && $d->status->value !== 'selesai')
                                                <form method="POST" action="{{ route('disposisi.selesaikan', [$surat, $d]) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:underline">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        Tandai Selesai
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @else
                    <p class="text-sm text-slate-400">Belum ada riwayat disposisi untuk Anda pada surat ini.</p>
                @endif
            </div>

            @php
                $sudahKirimMenunggu = $dispoTerakhir && $dispoTerakhir->pengirim_id === auth()->id();
                $isKirimRevisi = auth()->user()->isStaff() && $surat->status->value === 'perlu_revisi';
                $formTerkunci = $sudahKirimMenunggu || $sedangDitindaklanjuti;
            @endphp

            @if ($bisaReviewBaru)
                {{-- Disembunyikan karena sudah ada panel Tinjau Surat Masuk di atas --}}
            @elseif ($penerimaOptions->isNotEmpty() && $formTerkunci)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-6 text-sm text-slate-500">
                    <p class="flex items-center gap-2 font-medium text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @if ($sudahKirimMenunggu)
                            Disposisi sudah dikirim
                        @else
                            Sedang ditindaklanjuti
                        @endif
                    </p>
                    <p class="mt-1">
                        @if ($sudahKirimMenunggu)
                            Anda sudah mengirim disposisi untuk surat ini ke {{ $dispoTerakhir->penerima->nama }} dan sedang menunggu tindak lanjutnya.
                            Form kirim disposisi akan terbuka lagi begitu ada balasan.
                        @else
                            Surat ini sudah disetujui Kabag dan sedang ditindaklanjuti oleh {{ $dispoTerakhir->penerima->nama }} (Direktur).
                            Form kirim disposisi akan terbuka lagi kalau ada tindak lanjut yang butuh perhatian Anda.
                        @endif
                    </p>
                </div>
            @elseif ($penerimaOptions->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-brand-700">
                        {{ $isKirimRevisi ? 'Kirim Revisi' : 'Kirim Disposisi Baru' }}
                    </h3>
                    <form method="POST" action="{{ route('disposisi.store', $surat) }}" class="space-y-4" x-data="{ penerimaRole: '{{ auth()->user()->isStaff() ? 'kabag_umum' : '' }}' }">
                        @csrf
                        @if (auth()->user()->isStaff())
                            <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2h2m10 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7" /></svg>
                                Akan dikirim ke <span class="font-semibold text-slate-800">Kabag Umum</span>
                            </div>
                        @else
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kirim ke</label>
                                <select name="penerima_id" required x-on:change="penerimaRole = $event.target.options[$event.target.selectedIndex].dataset.role" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                    <option value="" data-role="" disabled selected>-- Pilih penerima --</option>
                                    @foreach ($penerimaOptions as $opt)
                                        <option value="{{ $opt->id }}" data-role="{{ $opt->role->nama_role }}">{{ $opt->nama }} ({{ ucwords(str_replace('_',' ',$opt->role->nama_role)) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Prioritas</label>
                            <select name="prioritas" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                <option value="sangat_segera">Sangat Segera (3 hari)</option>
                                <option value="segera">Segera (5 hari)</option>
                                <option value="biasa">Biasa (7 hari)</option>
                                <option value="tunggu_petunjuk">Tunggu Petunjuk</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Instruksi</label>
                            <textarea name="instruksi" rows="3" class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                        </div>

                        @if (auth()->user()->isKabag())
                            <div x-show="penerimaRole === 'staff_umum'" x-cloak class="flex flex-col gap-3 sm:flex-row">
                                <button type="submit" name="keputusan_surat" value="" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Approve
                                </button>
                                <button type="submit" name="keputusan_surat" value="perlu_revisi" class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    Revisi
                                </button>
                            </div>
                        @endif

                        <button type="submit" @if (auth()->user()->isKabag()) x-show="penerimaRole !== 'staff_umum'" @endif class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            {{ $isKirimRevisi ? 'Kirim Revisi' : 'Kirim Disposisi' }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection