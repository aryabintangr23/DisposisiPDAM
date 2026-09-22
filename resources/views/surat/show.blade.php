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

        $bisaReviewRevisi = (auth()->user()->isKasubag() || auth()->user()->isKabag())
            && $dispoTerakhir
            && $dispoTerakhir->penerima_id === auth()->id()
            && $surat->status->value === 'perlu_revisi';

        $bisaReviewBaru = (auth()->user()->isKasubag() || auth()->user()->isKabag())
            && $dispoTerakhir
            && $dispoTerakhir->penerima_id === auth()->id()
            && in_array($dispoTerakhir->pengirim?->role?->nama_role, ['staff_umum', 'kasubag_umum'], true)
            && $surat->status->value === 'baru';

        // Tahap penerusan review (Kasubag -> Kabag, Kabag -> Direktur)
        $tujuanTahapBerikutnya = auth()->user()?->isKasubag() ? 'Kabag Umum' : 'Direktur';
    @endphp

    @if (session('tautanPublikUrl'))
        <div x-data="{ show: true, disalin: false }" x-show="show" x-cloak class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-brand-800">Tautan publik siap dibagikan</p>
                    <p class="mt-1 text-xs text-brand-700">
                        Berisi lembar disposisi &amp; seluruh lampiran surat ini sebagai satu file PDF. Siapa pun yang memegang tautan ini bisa membukanya tanpa perlu login, dan berlaku 30 hari.
                    </p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <input type="text" readonly value="{{ session('tautanPublikUrl') }}" x-ref="tautanInput" onclick="this.select()" class="w-full min-w-0 flex-1 rounded-lg border border-brand-200 bg-white px-3 py-2 text-xs text-slate-700 sm:w-auto">
                        <button type="button" @click="navigator.clipboard.writeText($refs.tautanInput.value); disalin = true; setTimeout(() => disalin = false, 2000)" class="shrink-0 rounded-lg bg-brand-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-800">
                            <span x-show="!disalin">Salin Link</span>
                            <span x-show="disalin" x-cloak>Tersalin!</span>
                        </button>
                        <a href="https://wa.me/?text={{ urlencode('Lembar disposisi & lampiran surat: '.session('tautanPublikUrl')) }}" target="_blank" data-turbo="false" class="shrink-0 rounded-lg border border-brand-200 bg-white px-3 py-2 text-xs font-semibold text-brand-700 transition hover:bg-brand-50">
                            Kirim via WhatsApp
                        </a>
                    </div>
                </div>
                <button type="button" @click="show = false" class="shrink-0 rounded-lg p-1 text-brand-400 transition hover:bg-white hover:text-brand-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    @endif

    @if ($perluRevisiUntukStaff)
        @php
            $pengajuRevisi = $dispoTerakhir->pengirim;
            $labelPengajuRevisi = ucwords(str_replace('_', ' ', (string) $pengajuRevisi?->role?->nama_role));
        @endphp
        <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-orange-800">Perlu Revisi</h3>
            <p class="mt-1 text-sm text-orange-800/80">
                {{ $pengajuRevisi?->nama ?? 'Pihak terkait' }} ({{ $labelPengajuRevisi }}) meminta Anda merevisi surat ini
                @if ($dispoTerakhir->instruksi)
                    dengan catatan: &ldquo;{{ $dispoTerakhir->instruksi }}&rdquo;.
                @else
                    .
                @endif
                Silakan edit data surat, lalu kirim kembali sebagai revisi.
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
                {{ $dispoTerakhir->pengirim?->nama ?? 'Staff' }} ({{ ucwords(str_replace('_', ' ', (string) $dispoTerakhir->pengirim?->role?->nama_role)) }}) mengirim surat ini kepada Anda.
                Pilih <strong>Approve</strong> untuk menyetujui &mdash; surat otomatis diteruskan ke {{ $tujuanTahapBerikutnya }} &mdash; atau <strong>Revisi</strong> untuk mengirimkannya kembali ke Staff pembuat untuk diperbaiki.
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
                {{ $dispoTerakhir->pengirim?->nama ?? 'Staff' }} ({{ ucwords(str_replace('_', ' ', (string) $dispoTerakhir->pengirim?->role?->nama_role)) }}) sudah mengirim kembali surat yang direvisi.
                Periksa perubahannya, lalu tandai <strong>Diterima</strong> kalau sudah sesuai (surat otomatis diteruskan ke {{ $tujuanTahapBerikutnya }}), atau <strong>Minta Revisi Lagi</strong> kalau masih belum.
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
                Surat ini menunggu keputusan Anda. Pilih jabatan tujuan disposisi, lalu pilih <strong>Terima</strong> atau <strong>Tolak</strong> — status surat akan diperbarui menjadi final dan seluruh pihak yang terlibat dalam alur akan diberi tahu.
            </p>
            <form method="POST" action="{{ route('disposisi.keputusan', $surat) }}" class="mt-4 space-y-3" x-data="{ jabatan: '', jabatanLain: '' }">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Disposisi keputusan ke jabatan</label>
                    <select name="tujuan_jabatan" required x-model="jabatan" class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <option value="" disabled selected>-- Pilih jabatan tujuan disposisi --</option>
                        @foreach ($tujuanJabatan as $jab)
                            <option value="{{ $jab }}">{{ $jab }}</option>
                        @endforeach
                        <option value="{{ $jabatanLainnya }}">Lainnya... </option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Pilih dari daftar dropdown; untuk ganti jabatan tinggal klik kembali.</p>
                </div>
                <div x-show="jabatan === '{{ $jabatanLainnya }}'" x-cloak>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Jabatan lainnya</label>
                    <input type="text" name="tujuan_jabatan_lain" x-model="jabatanLain" :required="jabatan === '{{ $jabatanLainnya }}'" placeholder="Ketik nama jabatan tujuan" class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <p class="mt-1 text-xs text-slate-500">Ketik nama jabatan yang tidak ada di daftar.</p>
                </div>
                <div x-show="jabatan === 'Kepala Unit' || jabatan === 'Kasubag' || (jabatan === '{{ $jabatanLainnya }}' && ('kepala unit' === jabatanLain.trim().toLowerCase() || 'kasubag' === jabatanLain.trim().toLowerCase()))" x-cloak>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Bagian</label>
                    <input type="text" name="tujuan_bagian" :required="jabatan === 'Kepala Unit' || jabatan === 'Kasubag' || (jabatan === '{{ $jabatanLainnya }}' && ('kepala unit' === jabatanLain.trim().toLowerCase() || 'kasubag' === jabatanLain.trim().toLowerCase()))" placeholder="Contoh: Sub Bagian Keuangan" class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <p class="mt-1 text-xs text-slate-500">Wajib diisi untuk jabatan Kepala Unit / Kasubag — tulis nama bagiannya.</p>
                </div>
                <textarea name="catatan" rows="2" placeholder="Instruksi / catatan (opsional)" class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
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

    <div class="space-y-6">
        <!-- Informasi Surat -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-brand-700">Informasi Surat</h3>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
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
                <div class="sm:col-span-2 lg:col-span-4">
                    <dt class="text-xs font-medium text-slate-400">Perihal</dt>
                    <dd class="mt-0.5 text-sm text-slate-800">{{ $surat->perihal }}</dd>
                </div>
            </dl>
        </div>

        @if ($surat->arah_surat->value === 'masuk')
            {{-- Riwayat disposisi: hanya relevan untuk surat masuk, karena surat keluar
                 tidak melewati alur disposisi. Isi kartunya dirender lewat partial agar
                 bisa dipakai ulang oleh endpoint polling real time. --}}
            <div id="riwayat-disposisi" data-riwayat-url="{{ route('disposisi.riwayat', $surat) }}">
                @include('disposisi.riwayat', ['surat' => $surat])
            </div>
        @endif

        <!-- Lampiran -->
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
                        <a href="{{ route('lampiran.tampil', $file) }}" target="_blank" class="shrink-0 text-xs font-semibold text-brand-700 hover:underline">Buka di tab baru</a>
                    </div>

                    @if ($isPdf)
                        <iframe src="{{ route('lampiran.tampil', $file) }}" class="h-[500px] w-full border-0"></iframe>
                    @elseif ($isGambar)
                        <img src="{{ route('lampiran.tampil', $file) }}" alt="{{ $file->nama_file }}" loading="lazy" class="max-h-[500px] w-full object-contain bg-slate-100">
                    @else
                        <div class="flex flex-col items-center gap-2 px-4 py-8 text-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <p class="text-sm">Berkas ini tidak bisa dipratinjau langsung di sini.</p>
                            <a href="{{ route('lampiran.tampil', $file) }}" target="_blank" class="text-xs font-semibold text-brand-700 hover:underline">Unduh / buka berkas</a>
                        </div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">Tidak ada lampiran.</p>
            @endforelse
        </div>

        <!-- Form Kirim Disposisi (khusus surat masuk; surat keluar tidak melalui alur disposisi) -->
        @php
            $sudahKirimMenunggu = $dispoTerakhir && $dispoTerakhir->pengirim_id === auth()->id();
            $isKirimRevisi = auth()->user()->isStaff() && $surat->status->value === 'perlu_revisi';
            $formTerkunci = $sudahKirimMenunggu || $sedangDitindaklanjuti;
        @endphp

        @if ($surat->arah_surat->value === 'masuk')
        @if ($bisaReviewBaru || $bisaReviewRevisi)
            {{-- Disembunyikan karena sudah ada panel Tinjau Surat Masuk / Review Revisi di atas.
                 Kasubag/Kabag tidak perlu form kirim disposisi manual: Approve akan otomatis
                 meneruskan ke tahap berikutnya, dan Revisi akan otomatis mengembalikan ke Staff. --}}
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
                        Anda sudah mengirim disposisi untuk surat ini ke {{ $dispoTerakhir->penerima?->nama ?? $dispoTerakhir->keTujuanLabel() }} dan sedang menunggu tindak lanjutnya.
                        Form kirim disposisi akan terbuka lagi begitu ada balasan.
                    @else
                        Surat ini sudah disetujui dan sedang ditindaklanjuti oleh {{ $dispoTerakhir->penerima?->nama }} ({{ ucwords(str_replace('_', ' ', (string) $dispoTerakhir->penerima?->role?->nama_role)) }}).
                        Form kirim disposisi akan terbuka lagi kalau ada tindak lanjut yang butuh perhatian Anda.
                    @endif
                </p>
            </div>
        @elseif ($penerimaOptions->isNotEmpty() && $isPenerimaSaatIni)
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-brand-700">
                    {{ $isKirimRevisi ? 'Kirim Revisi' : 'Kirim Disposisi Baru' }}
                </h3>
                @php
                    $tujuanStaffLabel = $staffTujuan
                        ? $staffTujuan->nama.' ('.ucwords(str_replace('_', ' ', (string) $staffTujuan->role?->nama_role)).')'
                        : 'penerima otomatis';
                @endphp
                <form method="POST" action="{{ route('disposisi.store', $surat) }}" class="space-y-4" x-data="{ penerimaRole: '{{ auth()->user()->isStaff() ? 'kasubag_umum' : '' }}' }">
                    @csrf
                    @if (auth()->user()->isStaff())
                        <div class="flex items-center gap-2 rounded-lg bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2h2m10 0V6a2 2 0 00-2-2H9a2 2 0 00-2 2v2m10 0H7" /></svg>
                            Akan dikirim ke <span class="font-semibold text-slate-800">{{ $tujuanStaffLabel }}</span>
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

                    @if (auth()->user()->isKasubag() || auth()->user()->isKabag())
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

                    <button type="submit" @if (auth()->user()->isKasubag() || auth()->user()->isKabag()) x-show="penerimaRole !== 'staff_umum'" @endif class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        {{ $isKirimRevisi ? 'Kirim Revisi' : 'Kirim Disposisi' }}
                    </button>
                </form>
            </div>
        @endif
        @endif
    </div>
@endsection

@push('scripts')
<script>
    /**
     * Pemantauan riwayat disposisi secara real time.
     * Setiap beberapa detik kartu riwayat diminta ulang ke server; kalau tanda tangan
     * datanya berubah (ada disposisi baru / status berubah), isinya ditukar tanpa reload.
     */
    (function () {
        function waktuRelatifDisposisi(iso) {
            if (!iso) return '';
            const detik = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
            if (detik < 60) return 'baru saja';
            const menit = Math.floor(detik / 60);
            if (menit < 60) return menit + ' menit yang lalu';
            const jam = Math.floor(menit / 60);
            if (jam < 24) return jam + ' jam yang lalu';
            const hari = Math.floor(jam / 24);
            if (hari < 30) return hari + ' hari yang lalu';
            return new Date(iso).toLocaleDateString('id-ID');
        }

        function perbaruiWaktuRelatif() {
            document.querySelectorAll('.waktu-relatif-disposisi').forEach(function (el) {
                if (el.dataset.waktu) el.textContent = waktuRelatifDisposisi(el.dataset.waktu);
            });
        }

        function tandaTanganSaatIni(wadah) {
            const el = wadah.querySelector('[data-riwayat-signature]');
            return el ? el.dataset.riwayatSignature : null;
        }

        function jalankanPantauanDisposisi() {
            const wadah = document.getElementById('riwayat-disposisi');

            if (window.__riwayatDisposisiInterval) clearInterval(window.__riwayatDisposisiInterval);
            if (window.__riwayatJamInterval) clearInterval(window.__riwayatJamInterval);
            if (!wadah) return;

            perbaruiWaktuRelatif();
            window.__riwayatJamInterval = setInterval(perbaruiWaktuRelatif, 30000);

            const tick = function () {
                if (document.hidden) return;

                fetch(wadah.dataset.riwayatUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                    .then(function (data) {
                        if (data.signature && data.signature !== tandaTanganSaatIni(wadah)) {
                            wadah.innerHTML = data.html;
                            perbaruiWaktuRelatif();

                            const kartu = wadah.querySelector('[data-riwayat-signature]');
                            if (kartu) {
                                kartu.classList.add('ring-2', 'ring-brand-300');
                                setTimeout(function () { kartu.classList.remove('ring-2', 'ring-brand-300'); }, 2500);
                            }
                        }

                        const indikator = wadah.querySelector('[data-riwayat-indikator]');
                        if (indikator) {
                            indikator.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                        }
                    })
                    .catch(function () { /* diabaikan, dicoba lagi pada siklus berikutnya */ });
            };

            window.__riwayatDisposisiInterval = setInterval(tick, 10000);
            tick();
        }

        document.addEventListener('DOMContentLoaded', jalankanPantauanDisposisi);
        document.addEventListener('turbo:render', jalankanPantauanDisposisi);
        document.addEventListener('turbo:before-cache', function () {
            if (window.__riwayatDisposisiInterval) clearInterval(window.__riwayatDisposisiInterval);
            if (window.__riwayatJamInterval) clearInterval(window.__riwayatJamInterval);
        });
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && document.getElementById('riwayat-disposisi')) jalankanPantauanDisposisi();
        });
    })();
</script>
@endpush