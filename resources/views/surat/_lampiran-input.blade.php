@php
    $existing = $existing ?? collect();
    $label = $label ?? 'Lampiran';
    $petunjuk = $petunjuk ?? 'Ambil foto langsung dari kamera (bisa berkali-kali) atau pilih beberapa berkas sekaligus.';
    $existingLabel = $existingLabel ?? 'Lampiran Saat Ini';

    $existingData = $existing->map(function ($file) {
        $ekstensi = strtolower(pathinfo((string) $file->nama_file, PATHINFO_EXTENSION));

        return [
            'id' => (string) $file->id,
            'nama' => $file->nama_file,
            'url' => route('lampiran.tampil', $file),
            'ukuran' => (int) ($file->ukuran_file ?? 0),
            'gambar' => in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true),
            'foto' => in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp', 'heic'], true),
            'ekstensi' => $ekstensi,
        ];
    })->values();
@endphp

<div
    class="mt-5"
    x-data="{
        existing: @js($existingData),
        dihapus: [],
        files: [],
        gantiIndex: null,
        gantiExistingIndex: null,

        tambahFiles(event) {
            Array.from(event.target.files || []).forEach((file) => this.files.push(this.buatItem(file)));
            event.target.value = '';
            this.sinkron();
        },

        buatItem(file) {
            return {
                file: file,
                url: file.type && file.type.indexOf('image/') === 0 ? URL.createObjectURL(file) : null,
                gambar: !!(file.type && file.type.indexOf('image/') === 0),
            };
        },

        sinkron() {
            try {
                const dt = new DataTransfer();
                this.files.forEach((item) => dt.items.add(item.file));
                this.$refs.berkas.files = dt.files;
            } catch (e) {
                // Browser sangat lama tanpa DataTransfer tidak bisa menggabungkan beberapa
                // jepretan kamera; pemilihan lewat “Pilih Berkas” tetap menjadi cadangan.
            }
        },

        hapusFile(index) {
            const item = this.files[index];
            if (item && item.url) URL.revokeObjectURL(item.url);
            this.files.splice(index, 1);
            this.sinkron();
        },

        mulaiGanti(index) {
            this.gantiIndex = index;
            const item = this.files[index];
            if (item && item.gambar) {
                this.$refs.gantiFoto.click();
            } else {
                this.$refs.gantiBerkas.click();
            }
        },

        gantiFile(event) {
            const file = (event.target.files || [])[0];
            event.target.value = '';
            if (!file || this.gantiIndex === null) {
                this.gantiIndex = null;
                return;
            }

            const lama = this.files[this.gantiIndex];
            if (lama && lama.url) URL.revokeObjectURL(lama.url);
            this.files[this.gantiIndex] = this.buatItem(file);
            this.gantiIndex = null;
            this.sinkron();
        },

        toggleHapusExisting(index) {
            const id = this.existing[index].id;
            const posisi = this.dihapus.indexOf(id);

            if (posisi === -1) {
                this.dihapus.push(id);
            } else {
                this.dihapus.splice(posisi, 1);
            }
        },

        gantiExisting(index) {
            const item = this.existing[index];

            // Jangan langsung hapus lampiran lama. Tandai setelah pengganti dipilih,
            // supaya membatalkan kamera/pemilih berkas tidak menghapus lampiran.
            this.gantiExistingIndex = index;

            this.$nextTick(() => {
                if (item.foto) {
                    this.$refs.gantiExistingFoto.click();
                } else {
                    this.$refs.gantiExistingBerkas.click();
                }
            });
        },

        gantiExistingFile(event) {
            const daftar = Array.from(event.target.files || []);
            event.target.value = '';

            if (daftar.length === 0 || this.gantiExistingIndex === null) {
                this.gantiExistingIndex = null;
                return;
            }

            daftar.forEach((file) => this.files.push(this.buatItem(file)));

            const id = this.existing[this.gantiExistingIndex].id;
            if (this.dihapus.indexOf(id) === -1) this.dihapus.push(id);

            this.gantiExistingIndex = null;
            this.sinkron();
        },

        formatUkuran(bytes) {
            if (!bytes) return '';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB';
            return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        },
    }"
>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="$refs.kamera.click()" class="inline-flex items-center gap-2 rounded-lg bg-brand-700 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h1.343a2 2 0 001.789-1.106l.736-1.474A2 2 0 0110.657 3h2.686a2 2 0 011.789 1.106l.736 1.474A2 2 0 0017.657 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><circle cx="12" cy="13" r="3.5" /></svg>
                Ambil Foto
            </button>
            <button type="button" @click="$refs.berkasPilih.click()" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                Pilih Berkas
            </button>
            <span class="text-xs font-medium text-slate-500" x-show="files.length > 0" x-cloak>
                <span x-text="files.length"></span> berkas baru dipilih
            </span>
        </div>

        {{-- Input utama yang benar-benar dikirim: berisi gabungan semua foto/berkas. --}}
        <input x-ref="berkas" type="file" name="lampiran[]" multiple class="sr-only" tabindex="-1">

        {{-- Picker biasa (juga bernama lampiran[] agar tetap berfungsi tanpa JavaScript). --}}
        <input x-ref="berkasPilih" type="file" name="lampiran[]" multiple accept="{{ $accept }}" class="sr-only" tabindex="-1" @change="tambahFiles($event)">

        {{-- Kamera belakang; dipakai berulang kali, setiap jepretan ditambahkan ke daftar. --}}
        <input x-ref="kamera" type="file" accept="image/*" capture="environment" class="sr-only" tabindex="-1" @change="tambahFiles($event)">

        {{-- Pengganti untuk berkas baru yang sudah ada di daftar. --}}
        <input x-ref="gantiFoto" type="file" accept="image/*" capture="environment" class="sr-only" tabindex="-1" @change="gantiFile($event)">
        <input x-ref="gantiBerkas" type="file" accept="{{ $accept }}" class="sr-only" tabindex="-1" @change="gantiFile($event)">

        {{-- Pengganti untuk lampiran lama: berkas lama baru ditandai hapus setelah ini dipilih. --}}
        <input x-ref="gantiExistingFoto" type="file" accept="image/*" capture="environment" class="sr-only" tabindex="-1" @change="gantiExistingFile($event)">
        <input x-ref="gantiExistingBerkas" type="file" accept="{{ $accept }}" class="sr-only" tabindex="-1" @change="gantiExistingFile($event)">

        @if ($existing->isNotEmpty())
            <div class="mt-4 border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $existingLabel }}</p>
                <ul class="mt-2 space-y-2">
                    @foreach ($existing as $file)
                        @php
                            $ekstensi = strtolower(pathinfo((string) $file->nama_file, PATHINFO_EXTENSION));
                            $gambar = in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true);
                        @endphp
                        <li class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2" :class="dihapus.includes('{{ $file->id }}') ? 'opacity-50' : ''">
                            <div class="flex items-center gap-3">
                                @if ($gambar)
                                    <img src="{{ route('lampiran.tampil', $file) }}" alt="{{ $file->nama_file }}" class="h-12 w-12 shrink-0 rounded-lg border border-slate-200 object-cover">
                                @else
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </span>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('lampiran.tampil', $file) }}" target="_blank" class="block truncate text-sm font-medium text-slate-700 hover:text-brand-700 hover:underline" :class="dihapus.includes('{{ $file->id }}') ? 'line-through' : ''">{{ $file->nama_file }}</a>
                                    <p class="text-xs text-slate-400">
                                        {{ $gambar ? 'Foto' : strtoupper($ekstensi ?: 'BERKAS') }}
                                        @if ($file->ukuran_file)
                                            &middot; {{ number_format(((int) $file->ukuran_file) / 1024, 0, ',', '.') }} KB
                                        @endif
                                        <span x-show="dihapus.includes('{{ $file->id }}')" x-cloak class="font-semibold text-rose-600">&middot; akan dihapus</span>
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" @click="gantiExisting({{ $loop->index }})" title="Foto ulang / ganti berkas" aria-label="Foto ulang atau ganti {{ $file->nama_file }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-brand-50 hover:text-brand-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button type="button" @click="toggleHapusExisting({{ $loop->index }})" :title="dihapus.includes('{{ $file->id }}') ? 'Batalkan hapus' : 'Hapus lampiran'" aria-label="Hapus {{ $file->nama_file }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                            <input type="checkbox" name="hapus_lampiran[]" value="{{ $file->id }}" x-model="dihapus" class="hidden">
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4 border-t border-slate-100 pt-4" x-show="files.length > 0" x-cloak>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Berkas yang Akan Diunggah</p>
            <ul class="mt-2 space-y-2">
                <template x-for="(item, index) in files" :key="index">
                    <li class="rounded-lg border border-brand-100 bg-brand-50/40 px-3 py-2">
                        <div class="flex items-center gap-3">
                            <template x-if="item.gambar">
                                <img :src="item.url" alt="" class="h-12 w-12 shrink-0 rounded-lg border border-brand-100 object-cover">
                            </template>
                            <template x-if="! item.gambar">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-brand-100 bg-white text-brand-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </span>
                            </template>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-700" x-text="item.file.name"></p>
                                <p class="text-xs text-slate-400" x-text="formatUkuran(item.file.size)"></p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" @click="mulaiGanti(index)" title="Foto ulang / ganti berkas" aria-label="Foto ulang atau ganti berkas" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white hover:text-brand-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button type="button" @click="hapusFile(index)" title="Hapus dari daftar" aria-label="Hapus berkas dari daftar" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        <p class="mt-3 text-xs text-slate-400">{{ $petunjuk }}</p>
    </div>
</div>
