@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')

    <div class="mt-6 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Log Aktivitas</h2>
            <p class="mt-1 text-sm text-slate-500">Jejak aktivitas seluruh pengguna, diperbarui otomatis.</p>
        </div>

        <div class="flex items-center gap-3" x-data="{ aktif: true }">
            <span class="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                <span class="relative flex h-2 w-2">
                    <span x-show="aktif" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full" :class="aktif ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                </span>
                <span id="indikator-log-update">Menunggu data…</span>
            </span>

            <button type="button"
                    @click="aktif = !aktif; window.__logAktivitasState && (window.__logAktivitasState.berjalan = aktif)"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">
                <span x-show="aktif">Jeda Otomatis</span>
                <span x-show="!aktif" x-cloak>Lanjutkan</span>
            </button>

            <button type="button" onclick="window.__logAktivitasTick && window.__logAktivitasTick()"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-800">
                Muat Sekarang
            </button>
        </div>
    </div>

    {{-- ============ FILTER ============ --}}
    <form method="GET" action="{{ route('logAktivitas.index') }}" id="form-filter-log"
          class="mb-5 flex flex-wrap items-center gap-2">
        <select name="aksi" onchange="this.form.submit()"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500">
            <option value="">Semua Aksi</option>
            @foreach ($daftarAksi as $a)
                <option value="{{ $a }}" @selected($filters['aksi'] === $a)>{{ ucwords(str_replace('_', ' ', $a)) }}</option>
            @endforeach
        </select>

        <select name="user_id" onchange="this.form.submit()"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500">
            <option value="">Semua Pengguna</option>
            @foreach ($daftarUser as $u)
                <option value="{{ $u->id }}" @selected((string) $filters['user_id'] === (string) $u->id)>{{ $u->nama }}</option>
            @endforeach
        </select>

        <input type="date" name="tanggal" value="{{ $filters['tanggal'] }}" onchange="this.form.submit()"
               class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500">

        <input type="search" name="cari" value="{{ $filters['cari'] }}" placeholder="Cari deskripsi…"
               class="w-full max-w-xs rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-brand-500">

        <button type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900">
            Cari
        </button>

        @if ($filters['aksi'] || $filters['user_id'] || $filters['tanggal'] || $filters['cari'])
            <a href="{{ route('logAktivitas.index') }}" class="text-sm font-semibold text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200" id="tabel-log-aktivitas">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Waktu</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Pengguna</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Deskripsi</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" data-last-id="{{ $logs->first()->id ?? 0 }}">
                @if ($logs->isEmpty())
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                            <p class="text-sm">Belum ada aktivitas yang tercatat.</p>
                        </td>
                    </tr>
                @else
                    @include('log-aktivitas._baris', ['logs' => $logs, 'sorot' => false])
                @endif
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $logs->links() }}
    </div>

    @push('scripts')
    <script>
        // Polling ringan (bukan WebSocket) tiap 10 detik untuk menarik log
        // baru tanpa reload halaman. Dibungkus fungsi + di-restart di setiap
        // turbo:render supaya tetap jalan setelah navigasi Turbo, dan
        // di-clear di turbo:before-cache supaya tidak ada interval "bocor"
        // yang terus jalan di halaman lain.
        function jalankanPollingLogAktivitas() {
            const tabel = document.getElementById('tabel-log-aktivitas');
            if (!tabel) return;

            if (window.__logAktivitasInterval) {
                clearInterval(window.__logAktivitasInterval);
            }

            const state = { berjalan: true };
            window.__logAktivitasState = state;

            const ambilParamFilter = () => {
                const form = document.getElementById('form-filter-log');
                return form ? new URLSearchParams(new FormData(form)) : new URLSearchParams();
            };

            const tick = () => {
                const tbody = tabel.querySelector('tbody');
                if (!tbody) return;

                const lastId = tbody.dataset.lastId || 0;
                const params = ambilParamFilter();
                params.set('after', lastId);

                fetch(`{{ route('logAktivitas.data') }}?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then((r) => r.json())
                    .then((data) => {
                        const indikator = document.getElementById('indikator-log-update');
                        if (indikator) {
                            indikator.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
                        }

                        if (data.jumlah_baru > 0) {
                            tbody.insertAdjacentHTML('afterbegin', data.html);
                            tbody.dataset.lastId = data.last_id;

                            requestAnimationFrame(() => {
                                requestAnimationFrame(() => {
                                    tbody.querySelectorAll('[data-sorot="1"]').forEach((tr) => {
                                        tr.classList.remove('bg-amber-50');
                                        tr.removeAttribute('data-sorot');
                                    });
                                });
                            });
                        }
                    })
                    .catch(() => {
                        const indikator = document.getElementById('indikator-log-update');
                        if (indikator) indikator.textContent = 'Gagal memuat, akan dicoba lagi…';
                    });
            };

            window.__logAktivitasTick = tick;
            window.__logAktivitasInterval = setInterval(() => {
                if (window.__logAktivitasState && window.__logAktivitasState.berjalan) tick();
            }, 10000);

            // Muat sekali di awal supaya indikator langsung terisi.
            tick();
        }

        document.addEventListener('DOMContentLoaded', jalankanPollingLogAktivitas);
        document.addEventListener('turbo:render', jalankanPollingLogAktivitas);
        document.addEventListener('turbo:before-cache', () => {
            if (window.__logAktivitasInterval) clearInterval(window.__logAktivitasInterval);
        });
    </script>
    @endpush

@endsection
