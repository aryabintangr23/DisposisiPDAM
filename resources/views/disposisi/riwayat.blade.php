@php
    /**
     * Partial Riwayat Disposisi.
     *
     * Menampilkan ALUR PENUH perjalanan surat (bukan hanya bagian milik user yang login)
     * untuk Staff Umum, Kasubag Umum, Kabag Umum, dan Direktur. Dipakai juga oleh endpoint polling
     * (method riwayat pada DisposisiController) agar tampilannya ikut ter-update real time.
     */
    $userAktif = auth()->user();
    $bisaLihatAlurPenuh = $userAktif->bisaLihatAlurDisposisi();

    $semuaDisposisi = $surat->disposisi;

    $riwayat = ($bisaLihatAlurPenuh
        ? $semuaDisposisi
        : $semuaDisposisi->filter(fn ($d) => $d->pengirim_id === $userAktif->id || $d->penerima_id === $userAktif->id)
    )->values();

    $dispoTerakhir = $semuaDisposisi->last();
    $statusSurat = $surat->status->value;
    $suratFinal = in_array($statusSurat, ['diterima', 'ditolak'], true);
    $adaTerlambat = $dispoTerakhir?->isOverdue() ?? false;

    $namaRole = fn (?string $role) => match ($role) {
        'staff_umum' => 'Staff Umum',
        'kasubag_umum' => 'Kasubag Umum',
        'kabag_umum' => 'Kabag Umum',
        'direktur' => 'Direktur',
        'admin' => 'Admin',
        default => ucwords(str_replace('_', ' ', (string) $role)),
    };

    // ------- Posisi surat saat ini -------
    $rolePosisi = $suratFinal ? null : $dispoTerakhir?->penerima?->role?->nama_role;
    $pemegangSekarang = $suratFinal ? null : $dispoTerakhir?->penerima;
    $pemegangJabatan = $suratFinal ? null : ($dispoTerakhir?->penerima_id === null ? $dispoTerakhir?->keTujuanLabel() : null);

    // Kelas warna ditulis utuh (bukan hasil interpolasi) agar tetap terbaca Tailwind.
    $posisiTema = match (true) {
        $statusSurat === 'diterima' => ['bg-emerald-50/70', 'bg-emerald-100 text-emerald-700', 'text-emerald-700/80', 'Surat selesai — Diterima Direktur'],
        $statusSurat === 'ditolak' => ['bg-rose-50/70', 'bg-rose-100 text-rose-700', 'text-rose-700/80', 'Surat selesai — Ditolak'],
        $adaTerlambat => ['bg-rose-50/70', 'bg-rose-100 text-rose-700', 'text-rose-700/80', 'Melewati batas waktu disposisi'],
        $statusSurat === 'perlu_revisi' => ['bg-orange-50/70', 'bg-orange-100 text-orange-700', 'text-orange-700/80', 'Menunggu perbaikan surat'],
        default => ['bg-brand-50/70', 'bg-brand-100 text-brand-700', 'text-brand-700/80', 'Sedang berjalan'],
    };
    [$posisiLatar, $posisiIkon, $posisiTeks, $posisiKeterangan] = $posisiTema;

    // ------- Tahapan alur (stepper) -------
    $tahapan = [
        ['key' => 'staff_umum', 'label' => 'Staff Umum', 'sub' => 'Input & arsip surat'],
        ['key' => 'kasubag_umum', 'label' => 'Kasubag Umum', 'sub' => 'Verifikasi pertama'],
        ['key' => 'kabag_umum', 'label' => 'Kabag Umum', 'sub' => 'Verifikasi & teruskan'],
        ['key' => 'direktur', 'label' => 'Direktur', 'sub' => 'Keputusan akhir'],
    ];

    $rolePernahDilalui = $semuaDisposisi
        ->flatMap(fn ($d) => [$d->pengirim?->role?->nama_role, $d->penerima?->role?->nama_role])
        ->filter()
        ->unique();

    // ------- Tahap yang sedang meminta revisi (Kasubag/Kabag) -------
    // Selama status surat masih "perlu_revisi", cari langkah TERAKHIR yang berupa
    // permintaan revisi (dikirim oleh Kasubag/Kabag KEMBALI ke Staff/pembuat surat).
    // Tahap (Kasubag/Kabag) yang memintanya ditandai orange + tanda seru pada
    // stepper — BUKAN centang hijau — selama surat masih dalam proses revisi,
    // walaupun Staff sudah mengirim ulang surat yang direvisi tersebut.
    $rolePemintaRevisi = null;

    if ($statusSurat === 'perlu_revisi') {
        $dispoPermintaanRevisi = $semuaDisposisi
            ->reverse()
            ->first(fn ($d) => in_array($d->pengirim?->role?->nama_role, ['kasubag_umum', 'kabag_umum'], true)
                && $d->penerima?->role?->nama_role === 'staff_umum');

        $rolePemintaRevisi = $dispoPermintaanRevisi?->pengirim?->role?->nama_role;
    }

    // ------- Palet warna -------
    $prioritasColor = fn ($p) => match ($p) {
        'sangat_segera' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200',
        'segera' => 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-200',
        'biasa' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
        'tunggu_petunjuk' => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200',
        default => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
    };

    $dispoStatusColor = fn ($s) => match ($s) {
        'selesai' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        'ditindaklanjuti' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
        'dibaca' => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200',
        'diterima' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
        default => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
    };

    // Tanda tangan data: dipakai JS untuk mendeteksi apakah ada perubahan alur.
    $riwayatSignature = md5(implode('|', [
        $statusSurat,
        $semuaDisposisi->count(),
        (string) $semuaDisposisi->max('updated_at'),
    ]));
@endphp

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
     data-riwayat-signature="{{ $riwayatSignature }}">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-brand-700">Riwayat Disposisi</h3>
            <p class="mt-0.5 text-xs text-slate-500">
                @if ($bisaLihatAlurPenuh)
                    Alur lengkap perjalanan surat dari awal sampai posisi terakhir.
                @else
                    Bagian alur yang melibatkan Anda.
                @endif
            </p>
        </div>

        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
            <span class="relative flex h-1.5 w-1.5">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-75"></span>
                <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            </span>
            <span data-riwayat-indikator>Pantauan real time</span>
        </span>
    </div>

    @if ($semuaDisposisi->isEmpty())
        <div class="px-6 py-12 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-9 w-9 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="mt-2 text-sm text-slate-400">Belum ada disposisi untuk surat ini.</p>
        </div>
    @else
        <!-- Posisi surat saat ini -->
        <div class="border-b border-slate-100 {{ $posisiLatar }} px-6 py-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $posisiIkon }}">
                        @if ($suratFinal)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        @endif
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide {{ $posisiTeks }}">Posisi surat saat ini</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-800">
                            @if ($suratFinal)
                                {{ $posisiKeterangan }}
                            @elseif ($pemegangSekarang)
                                Sedang berada di {{ $pemegangSekarang->nama }}
                                <span class="font-normal text-slate-500">({{ $namaRole($rolePosisi) }})</span>
                            @elseif ($pemegangJabatan)
                                Didisposisikan ke <span class="font-semibold">{{ $pemegangJabatan }}</span>
                            @else
                                Belum ditentukan
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            @if (! $suratFinal)
                                {{ $posisiKeterangan }} &middot;
                            @endif
                            Pembaruan terakhir
                            <span class="waktu-relatif-disposisi font-medium text-slate-600" data-waktu="{{ optional($semuaDisposisi->max('updated_at'))->toIso8601String() }}">
                                {{ optional($semuaDisposisi->max('updated_at'))->locale('id')->diffForHumans() }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($dispoTerakhir)
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $prioritasColor($dispoTerakhir->prioritas->value) }}">
                            {{ $dispoTerakhir->prioritas->label() }}
                        </span>
                    @endif
                    @if (! $suratFinal && $dispoTerakhir?->batas_waktu)
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $adaTerlambat ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200' : 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $adaTerlambat ? 'Lewat batas' : 'Batas' }} {{ $dispoTerakhir->batas_waktu->format('d-m-Y') }}
                        </span>
                    @endif
                    <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                        {{ $semuaDisposisi->count() }} langkah
                    </span>
                </div>
            </div>
        </div>

        <!-- Stepper alur antar bagian -->
        <div class="border-b border-slate-100 px-6 py-5">
            <ol class="flex items-start">
                @foreach ($tahapan as $i => $tahap)
                    @php
                        $aktif = ! $suratFinal && $rolePosisi === $tahap['key'];
                        $pernah = $rolePernahDilalui->contains($tahap['key']);
                        $mintaRevisi = $rolePemintaRevisi === $tahap['key'];
                        $tuntas = $pernah && ! $aktif && ! $mintaRevisi;

                        $bulatKelas = match (true) {
                            $mintaRevisi => 'bg-orange-500 text-white ring-4 ring-orange-100',
                            $aktif => 'bg-brand-600 text-white ring-4 ring-brand-100',
                            $tuntas => 'bg-emerald-500 text-white',
                            default => 'bg-slate-100 text-slate-400',
                        };
                        $garisKelas = $rolePernahDilalui->contains($tahapan[$i + 1]['key'] ?? '') ? 'bg-emerald-300' : 'bg-slate-200';
                    @endphp
                    <li class="flex flex-1 flex-col items-center text-center">
                        <div class="flex w-full items-center">
                            <div class="h-0.5 flex-1 {{ $loop->first ? 'bg-transparent' : ($pernah ? 'bg-emerald-300' : 'bg-slate-200') }}"></div>
                            <span class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold transition {{ $bulatKelas }}">
                                @if ($mintaRevisi)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                @elseif ($tuntas)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                                @if ($aktif || $mintaRevisi)
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $mintaRevisi ? 'bg-orange-400' : 'bg-brand-400' }} opacity-50"></span>
                                @endif
                            </span>
                            <div class="h-0.5 flex-1 {{ $loop->last ? 'bg-transparent' : $garisKelas }}"></div>
                        </div>
                        <p class="mt-2 text-xs font-semibold {{ $mintaRevisi ? 'text-orange-700' : ($aktif ? 'text-brand-700' : ($tuntas ? 'text-slate-700' : 'text-slate-400')) }}">
                            {{ $tahap['label'] }}
                        </p>
                        <p class="mt-0.5 hidden text-[11px] text-slate-400 sm:block">{{ $tahap['sub'] }}</p>
                        @if ($mintaRevisi)
                            <span class="mt-1 inline-flex items-center rounded-full bg-orange-50 px-2 py-0.5 text-[10px] font-semibold text-orange-700">Sedang proses revisi</span>
                        @elseif ($aktif)
                            <span class="mt-1 inline-flex items-center rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">Sedang di sini</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        <!-- Linimasa detail -->
        <div class="px-6 py-5">
            @if ($riwayat->isEmpty())
                <p class="text-sm text-slate-400">Belum ada langkah disposisi yang melibatkan Anda.</p>
            @else
                <ol class="relative space-y-5 border-l border-slate-200 pl-6">
                    @foreach ($riwayat->reverse() as $d)
                        @php
                            $terlambatItem = $d->isOverdue();
                            $langkahKe = $semuaDisposisi->search(fn ($x) => $x->id === $d->id) + 1;
                            $tujuanNama = $d->penerima?->nama ?? ($d->tujuan_jabatan ?? '-');
                            $tujuanLabel = $d->penerima
                                ? $namaRole($d->penerima?->role?->nama_role)
                                : trim(($d->tujuan_jabatan ?? '').($d->tujuan_bagian ? ' ('.$d->tujuan_bagian.')' : ''));
                            $titikWarna = match (true) {
                                $terlambatItem => 'bg-rose-500 ring-rose-100',
                                $d->status->value === 'selesai' => 'bg-emerald-500 ring-emerald-100',
                                $loop->first => 'bg-brand-600 ring-brand-100',
                                default => 'bg-slate-300 ring-slate-100',
                            };
                        @endphp
                        <li class="relative">
                            <span class="absolute -left-[1.9rem] top-1.5 flex h-3.5 w-3.5 items-center justify-center rounded-full ring-4 {{ $titikWarna }}">
                                @if ($loop->first && ! $suratFinal)
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $terlambatItem ? 'bg-rose-500' : 'bg-brand-500' }} opacity-60"></span>
                                @endif
                            </span>

                            <div class="rounded-xl border {{ $loop->first ? 'border-brand-200 bg-brand-50/40' : 'border-slate-200 bg-white' }} p-4 transition hover:shadow-sm">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500">
                                        LANGKAH {{ $langkahKe }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $prioritasColor($d->prioritas->value) }}">
                                        {{ $d->prioritas->label() }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $dispoStatusColor($d->status->value) }}">
                                        {{ $d->status->label() }}
                                    </span>
                                    @if ($terlambatItem)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                            Terlambat
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                                    <span class="font-semibold text-slate-800">{{ $d->pengirim?->nama }}</span>
                                    <span class="text-xs text-slate-400">{{ $namaRole($d->pengirim?->role?->nama_role) }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
                                    <span class="font-semibold text-slate-800">{{ $tujuanNama }}</span>
                                    <span class="text-xs text-slate-400">{{ $tujuanLabel }}</span>
                                </div>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $d->created_at?->format('d-m-Y H:i') }}
                                    <span class="mx-1">&middot;</span>
                                    <span class="waktu-relatif-disposisi" data-waktu="{{ $d->created_at?->toIso8601String() }}">{{ $d->created_at?->locale('id')->diffForHumans() }}</span>
                                    @if ($d->batas_waktu)
                                        <span class="mx-1">&middot;</span>
                                        Batas waktu {{ $d->batas_waktu->format('d-m-Y') }}
                                    @endif
                                </p>

                                @if ($d->instruksi)
                                    <p class="mt-2 rounded-lg border-l-2 border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                        {{ $d->instruksi }}
                                    </p>
                                @endif

                                <div class="mt-2.5 flex flex-wrap items-center gap-3">
                                    <a href="{{ route('disposisi.cetak', [$surat, $d]) }}" target="_blank" data-turbo="false" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-700 hover:underline">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-8 4h8v-6H8v6z" /></svg>
                                        Cetak PDF
                                    </a>

                                    {{-- @if ($userAktif->isStaff() || $userAktif->isKabag())
                                        {{-- Cetak gabungan (lembar disposisi + seluruh lampiran surat jadi 1 file PDF).
                                             Sengaja dibatasi hanya untuk Staff Umum & Kabag Umum. --}}
                                        {{-- <a href="{{ route('disposisi.cetakLengkap', [$surat, $d]) }}" target="_blank" data-turbo="false" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 hover:underline">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            Cetak Lengkap (+ Lampiran)
                                        </a>
                                    @endif --}} 

                                    @if ($userAktif->isStaff())
                                        {{-- Membuat tautan publik hanya untuk Staff Umum; hasil linknya ditampilkan
                                             lewat flash "tautanPublikUrl" di bagian atas halaman surat/show. --}}
                                        <form method="POST" action="{{ route('tautanPublik.store', [$surat, $d]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:underline">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342a2.987 2.987 0 000-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
                                                Bagikan Tautan Publik
                                            </button>
                                        </form>
                                    @endif

                                    @if ($userAktif->isStaff() && $d->penerima_id === $userAktif->id && $d->status->value !== 'selesai')
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

                <p class="mt-4 flex items-center gap-1.5 text-[11px] text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Langkah terbaru ditampilkan paling atas. Halaman ini memperbarui dirinya sendiri tanpa perlu di-refresh.
                </p>
            @endif
        </div>
    @endif
</div>