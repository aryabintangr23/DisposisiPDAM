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

            @if (auth()->user()->isStaff() && $surat->created_by === auth()->id() && $surat->status->value === 'baru')
                <a href="{{ route('surat.edit', $surat) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Edit Surat
                </a>
            @endif
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ $surat->perihal }}</p>
    </div>

    @php
        $dispoTerakhir = $surat->disposisiTerakhir();
        $bisaMemutuskan = auth()->user()->isDirektur()
            && $dispoTerakhir
            && $dispoTerakhir->penerima_id === auth()->id()
            && $surat->status->value === 'baru';
    @endphp

    @if ($bisaMemutuskan)
        <div class="mb-6 rounded-xl border border-brand-200 bg-brand-50 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-800">Keputusan Surat</h3>
            <p class="mt-1 text-sm text-brand-800/80">
                Surat ini menunggu keputusan Anda. Pilih Terima atau Tolak — keputusan akan otomatis dikirim
                sebagai disposisi balasan ke {{ $dispoTerakhir->pengirim->nama }} (Kabag) dan status surat akan diperbarui.
            </p>
            <form method="POST" action="{{ route('disposisi.keputusan', $surat) }}" class="mt-4 space-y-3">
                @csrf
                <textarea name="catatan" rows="2" placeholder="Catatan (opsional)"
                    class="w-full rounded-lg border border-brand-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"></textarea>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" name="keputusan" value="diterima"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Terima
                    </button>
                    <button type="submit" name="keputusan" value="ditolak"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        Tolak
                    </button>
                </div>
            </form>
        </div>
    @endif

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
                    @php
                        $ekstensi = strtolower(pathinfo($file->nama_file, PATHINFO_EXTENSION));
                        $isGambar = in_array($ekstensi, ['jpg', 'jpeg', 'png'], true);
                        $isPdf = $ekstensi === 'pdf';
                        $iconColor = match (true) {
                            $isPdf => 'text-rose-500',
                            $isGambar => 'text-emerald-500',
                            default => 'text-blue-500', // docx & lainnya
                        };
                    @endphp
                    <div class="mb-5 overflow-hidden rounded-lg border border-slate-200 last:mb-0">
                        <div class="flex items-center justify-between gap-3 bg-slate-50 px-4 py-2.5">
                            <div class="flex min-w-0 items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="truncate text-sm font-medium text-slate-700">{{ $file->nama_file }}</span>
                                <span class="shrink-0 text-xs text-slate-400">({{ number_format($file->ukuran_file / 1024, 0) }} KB)</span>
                            </div>
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank"
                               class="shrink-0 text-xs font-semibold text-brand-700 hover:underline">Buka di tab baru</a>
                        </div>

                        @if ($isPdf)
                            {{-- Preview inline untuk PDF --}}
                            <iframe src="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" class="h-[500px] w-full border-0"></iframe>
                        @elseif ($isGambar)
                            {{-- Preview inline untuk gambar (JPG/PNG) --}}
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" alt="{{ $file->nama_file }}" class="max-h-[500px] w-full object-contain bg-slate-100">
                        @else
                            {{-- DOCX & tipe lain tidak bisa dipratinjau langsung di browser --}}
                            <div class="flex flex-col items-center gap-2 px-4 py-8 text-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <p class="text-sm">Berkas Word tidak bisa dipratinjau di sini.</p>
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($file->path_file) }}" target="_blank"
                                   class="text-xs font-semibold text-brand-700 hover:underline">Unduh / buka berkas</a>
                            </div>
                        @endif
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
    
                    $prioritasDotColor = fn ($p) => match ($p) {
                        'sangat_segera' => 'bg-red-500 ring-red-100',
                        'segera' => 'bg-yellow-400 ring-yellow-100',
                        'biasa' => 'bg-green-500 ring-green-100',
                        'tunggu_petunjuk' => 'bg-blue-500 ring-blue-100',
                        default => 'bg-slate-300 ring-slate-100',
                    };
                @endphp

                <ol class="relative space-y-6 border-l-2 border-slate-100 pl-5">
                    @foreach ($surat->disposisi as $d)
                        <li class="relative">
                            <span
                                class="absolute -left-[27px] top-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-white ring-2 {{ $prioritasDotColor($d->prioritas->value) }}"
                                title="Prioritas: {{ $d->prioritas->label() }}"></span>

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
                    <form method="POST" action="{{ route('disposisi.store', $surat) }}" class="space-y-4" x-data="{ penerimaRole: '' }">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Kirim ke</label>
                            <select name="penerima_id" required
                                x-on:change="penerimaRole = $event.target.options[$event.target.selectedIndex].dataset.role"
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                <option value="" data-role="" disabled selected>-- Pilih penerima --</option>
                                @foreach ($penerimaOptions as $opt)
                                    <option value="{{ $opt->id }}" data-role="{{ $opt->role->nama_role }}">{{ $opt->nama }} ({{ ucwords(str_replace('_',' ',$opt->role->nama_role)) }})</option>
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

                        @if (auth()->user()->isKabag())
                            {{-- Kabag -> Staff: opsi menandai surat "Perlu Revisi". --}}
                            <div x-show="penerimaRole === 'staff_umum'" x-cloak
                                 class="rounded-lg border border-orange-200 bg-orange-50 px-3.5 py-3">
                                <label class="flex items-start gap-2 text-sm text-orange-800">
                                    <input type="checkbox" name="keputusan_surat" value="perlu_revisi"
                                           class="mt-0.5 h-4 w-4 rounded border-orange-300 text-orange-600 focus:ring-orange-500">
                                    <span>Tandai status surat ini sebagai <strong>Perlu Revisi</strong></span>
                                </label>
                            </div>
                        @endif

                        @if (auth()->user()->isDirektur())
                            {{-- Keputusan Diterima/Ditolak sekarang menggunakan tombol khusus
                                 di bagian atas halaman (kartu "Keputusan Surat"), bukan di sini. --}}
                            <div x-show="penerimaRole === 'kabag_umum'" x-cloak
                                 class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-3 text-xs text-slate-500">
                                Untuk menyetujui atau menolak surat ini, gunakan tombol <strong>Terima</strong> / <strong>Tolak</strong>
                                di bagian atas halaman. Form ini hanya untuk mengirim disposisi tambahan tanpa mengubah status.
                            </div>
                        @endif

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
