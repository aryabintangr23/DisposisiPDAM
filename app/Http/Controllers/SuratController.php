<?php

namespace App\Http\Controllers;

use App\Enums\ArahSurat;
use App\Enums\Prioritas;
use App\Enums\StatusDisposisi;
use App\Http\Requests\StoreSuratRequest;
use App\Http\Requests\UpdateSuratRequest;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Models\User;
use App\Services\DisposisiRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SuratController extends Controller
{
    /**
     * Menampilkan daftar surat berdasarkan role user dan filter yang dipilih.
     */
    public function index(Request $request, DisposisiRuleService $rule): View
    {
        $user = $request->user();
        $tanggal = $request->query('tanggal');

        // Filter arah surat (masuk/keluar)
        $arah = $request->query('arah');
        if (! in_array($arah, array_column(ArahSurat::cases(), 'value'), true)) {
            $arah = null;
        }

        // Filter prioritas berdasarkan disposisi
        $prioritas = $request->query('prioritas');
        if (! in_array($prioritas, array_column(Prioritas::cases(), 'value'), true)) {
            $prioritas = null;
        }

        // Pencarian kata kunci (nomor, agenda, perihal, asal, tujuan, jenis)
        $cari = trim((string) $request->query('cari', ''));
        $cari = $cari !== '' ? $cari : null;

        // Scope query sesuai role user yang login
        $scope = fn () => Surat::untukRole($user);

        $query = $scope()->with('disposisi.pengirim.role', 'disposisi.penerima.role');

        if ($tanggal) {
            $query->whereDate('tanggal_surat', $tanggal);
        }

        if ($arah) {
            $query->where('arah_surat', $arah);
        }

        if ($prioritas) {
            $query->whereHas('disposisi', fn ($q) => $q->where('prioritas', $prioritas));
        }

        if ($cari) {
            $query->where(function ($q) use ($cari) {
                $q->where('nomor_surat', 'like', "%{$cari}%")
                    ->orWhere('nomor_agenda', 'like', "%{$cari}%")
                    ->orWhere('perihal', 'like', "%{$cari}%")
                    ->orWhere('jenis_surat', 'like', "%{$cari}%")
                    ->orWhere('surat_dari', 'like', "%{$cari}%")
                    ->orWhere('tujuan_surat', 'like', "%{$cari}%");
            });
        }

        $surat = $query->latest()->paginate(15)->withQueryString();

        // Tandai otomatis surat yang sudah melewati batas waktu prioritas sebagai "Ditolak".
        foreach ($surat as $item) {
            $rule->tandaiOtomatisJikaTerlambat($item);
        }

        // Data tanggal untuk penanda titik pada kalender dashboard
        $bulan = $request->query('bulan', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $bulan)) {
            $bulan = now()->format('Y-m');
        }

        $tanggalBersurat = $scope()
            ->whereYear('tanggal_surat', substr($bulan, 0, 4))
            ->whereMonth('tanggal_surat', substr($bulan, 5, 2))
            ->selectRaw('DATE(tanggal_surat) as tgl')
            ->distinct()
            ->pluck('tgl');

        // Ringkasan per tanggal untuk pop up peringatan saat kursor berada di atas tanggal kalender.
        $peringatanKalender = $scope()
            ->whereYear('tanggal_surat', substr($bulan, 0, 4))
            ->whereMonth('tanggal_surat', substr($bulan, 5, 2))
            ->with('disposisi')
            ->get()
            ->groupBy(fn (Surat $item) => $item->tanggal_surat->format('Y-m-d'))
            ->map(fn ($grup) => $grup->map(function (Surat $item) {
                $dispoTerakhir = $item->disposisi->last();

                return [
                    'nomor_surat' => $item->nomor_surat,
                    'perihal' => $item->perihal,
                    'status' => $item->status->label(),
                    'terlambat' => $dispoTerakhir?->isOverdue() ?? false,
                    'prioritas' => $dispoTerakhir?->prioritas?->label(),
                ];
            })->values());

        return view('surat.index', compact('surat', 'tanggal', 'bulan', 'tanggalBersurat', 'peringatanKalender', 'arah', 'prioritas', 'cari'));
    }

    /**
     * Tampilkan form input surat baru (khusus Staff).
     */
    public function create(Request $request): View
    {
        $this->authorizeStaffOnly($request);

        $kabag = User::whereHas('role', fn ($q) => $q->where('nama_role', 'kabag_umum'))->first();

        return view('surat.create', compact('kabag'));
    }

    /**
     * Simpan surat baru beserta lampiran dan disposisi awalnya.
     */
    public function store(StoreSuratRequest $request, DisposisiRuleService $rule): RedirectResponse
    {
        $this->authorizeStaffOnly($request);

        $data = $request->validated();

        // Tanggal diterima hanya relevan untuk surat masuk; surat keluar selalu dikosongkan.
        if ($data['arah_surat'] === 'keluar') {
            $data['tanggal_diterima'] = null;
        }

        // Warning jika nomor surat atau agenda sudah pernah digunakan
        $this->tandaiJikaNomorSudahDipakai($data['nomor_surat'], $data['nomor_agenda'] ?? null);

        $surat = Surat::create([
            'created_by' => $request->user()->id,
            'arah_surat' => $data['arah_surat'],
            'jenis_surat' => $data['jenis_surat'],
            'nomor_surat' => $data['nomor_surat'],
            'nomor_agenda' => $data['nomor_agenda'] ?? null,
            'tanggal_surat' => $data['tanggal_surat'],
            'tanggal_diterima' => $data['tanggal_diterima'] ?? null,
            'surat_dari' => $data['surat_dari'] ?? null,
            'tujuan_surat' => $data['tujuan_surat'] ?? null,
            'perihal' => $data['perihal'],
        ]);

        if ($request->hasFile('lampiran')) {
            foreach ($request->file('lampiran') as $file) {
                $path = $file->store('lampiran', 'public');

                $surat->lampiran()->create([
                    'nama_file' => $file->getClientOriginalName(),
                    'path_file' => $path,
                    'tipe_file' => $file->getClientMimeType(),
                    'ukuran_file' => $file->getSize(),
                ]);
            }
        }

        // Buat lembar disposisi pertama dari Staff ke Kabag (tujuan selalu Kabag, tidak perlu dipilih manual)
        $penerima = User::whereHas('role', fn ($q) => $q->where('nama_role', 'kabag_umum'))->first();
        abort_unless($penerima, 422, 'Tidak ada akun Kabag Umum yang terdaftar untuk menerima disposisi ini.');
        abort_unless($rule->bolehDisposisi($request->user(), $penerima), 403, 'Tujuan disposisi tidak sesuai alur yang diizinkan.');

        $prioritas = Prioritas::from($data['prioritas']);
        $tanggalDisposisi = now();

        $surat->disposisi()->create([
            'pengirim_id' => $request->user()->id,
            'penerima_id' => $penerima->id,
            'tanggal_disposisi' => $tanggalDisposisi,
            'prioritas' => $prioritas,
            'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
            'instruksi' => $data['instruksi'] ?? null,
            'status' => StatusDisposisi::Terkirim,
        ]);

        LogAktivitas::catat(
            'surat_dibuat',
            "{$request->user()->nama} membuat surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) dan mengirim disposisi awal ke {$penerima->nama}.",
            'surat',
            $surat->id
        );

        return redirect()->route('surat.show', $surat)->with('status', 'Surat dan lembar disposisi berhasil dibuat.');
    }

    /**
     * Form edit data surat.
     */
    public function edit(Request $request, Surat $surat, DisposisiRuleService $rule): View
    {
        $surat->load('disposisi');
        $rule->tandaiOtomatisJikaTerlambat($surat);

        $this->authorizeEdit($request, $surat);

        $surat->load('lampiran');

        return view('surat.edit', compact('surat'));
    }

    /**
     * Update data surat dan simpan lampiran tambahan jika ada.
     */
    public function update(UpdateSuratRequest $request, Surat $surat): RedirectResponse
    {
        $this->authorizeEdit($request, $surat);

        $data = $request->validated();

        // Tanggal diterima hanya relevan untuk surat masuk; surat keluar selalu dikosongkan.
        if ($data['arah_surat'] === 'keluar') {
            $data['tanggal_diterima'] = null;
        }

        $this->tandaiJikaNomorSudahDipakai($data['nomor_surat'], $data['nomor_agenda'] ?? null, $surat->id);

        $surat->update([
            'arah_surat' => $data['arah_surat'],
            'jenis_surat' => $data['jenis_surat'],
            'nomor_surat' => $data['nomor_surat'],
            'nomor_agenda' => $data['nomor_agenda'] ?? null,
            'tanggal_surat' => $data['tanggal_surat'],
            'tanggal_diterima' => $data['tanggal_diterima'] ?? null,
            'surat_dari' => $data['surat_dari'] ?? null,
            'tujuan_surat' => $data['tujuan_surat'] ?? null,
            'perihal' => $data['perihal'],
        ]);

        // Hapus lampiran yang ditandai user (untuk diganti dengan lampiran baru).
        $idLampiranDihapus = collect($request->input('hapus_lampiran', []))->map(fn ($id) => (int) $id);
        if ($idLampiranDihapus->isNotEmpty()) {
            $lampiranDihapus = $surat->lampiran()->whereIn('id', $idLampiranDihapus)->get();

            foreach ($lampiranDihapus as $file) {
                Storage::disk('public')->delete($file->path_file);
                $file->delete();
            }
        }

        if ($request->hasFile('lampiran')) {
            foreach ($request->file('lampiran') as $file) {
                $path = $file->store('lampiran', 'public');

                $surat->lampiran()->create([
                    'nama_file' => $file->getClientOriginalName(),
                    'path_file' => $path,
                    'tipe_file' => $file->getClientMimeType(),
                    'ukuran_file' => $file->getSize(),
                ]);
            }
        }

        LogAktivitas::catat(
            'surat_diubah',
            "{$request->user()->nama} mengubah data surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}).",
            'surat',
            $surat->id
        );

        return redirect()->route('surat.show', $surat)->with('status', 'Data surat berhasil diperbarui.');
    }

    /**
     * Tampilkan detail surat dan riwayat disposisinya.
     */
    public function show(Request $request, Surat $surat, DisposisiRuleService $rule): View
    {
        $this->authorizeAkses($request, $surat);

        // Tandai disposisi sebagai sudah dibaca saat surat dibuka
        $this->tandaiDisposisiTerkaitDibaca($request->user(), $surat);

        $surat->load(['lampiran', 'disposisi.pengirim.role', 'disposisi.penerima.role', 'pembuat']);

        // Tandai otomatis surat yang sudah melewati batas waktu prioritas sebagai "Ditolak".
        $rule->tandaiOtomatisJikaTerlambat($surat);

        $penerimaOptions = $this->penerimaOptionsUntuk($request->user(), $surat);

        return view('surat.show', compact('surat', 'penerimaOptions'));
    }

    /**
     * Memindahkan surat yang dipilih ke tempat sampah (soft delete).
     */
    public function hapus(Request $request): RedirectResponse
    {
        $this->authorizeHapusSurat($request);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:surat,id'],
        ]);

        $query = Surat::untukRole($request->user())->whereIn('id', $data['ids']);
        $jumlah = $query->count();
        $daftarNomor = $query->pluck('nomor_surat')->implode(', ');
        $query->delete();

        LogAktivitas::catat(
            'surat_dihapus',
            "{$request->user()->nama} memindahkan {$jumlah} surat ke tempat sampah ({$daftarNomor})."
        );

        return redirect()->route('surat.index')->with('status', "{$jumlah} surat dipindahkan ke tempat sampah.");
    }

    /**
     * Tampilkan daftar surat di tempat sampah.
     */
    public function sampah(Request $request): View
    {
        $this->authorizeHapusSurat($request);

        $surat = Surat::untukRole($request->user())
            ->onlyTrashed()
            ->latest('deleted_at')
            ->paginate(15);

        return view('surat.sampah', compact('surat'));
    }

    /**
     * Pulihkan surat dari tempat sampah.
     */
    public function pulihkan(Request $request): RedirectResponse
    {
        $this->authorizeHapusSurat($request);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $query = Surat::untukRole($request->user())->onlyTrashed()->whereIn('id', $data['ids']);
        $jumlah = $query->count();
        $query->restore();

        LogAktivitas::catat(
            'surat_dipulihkan',
            "{$request->user()->nama} memulihkan {$jumlah} surat dari tempat sampah."
        );

        return redirect()->route('surat.sampah')->with('status', "{$jumlah} surat dipulihkan.");
    }

    /**
     * Hapus surat dan file lampirannya secara permanen.
     */
    public function hapusPermanen(Request $request): RedirectResponse
    {
        $this->authorizeHapusSurat($request);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $suratList = Surat::untukRole($request->user())
            ->onlyTrashed()
            ->whereIn('id', $data['ids'])
            ->with('lampiran')
            ->get();

        foreach ($suratList as $surat) {
            foreach ($surat->lampiran as $file) {
                Storage::disk('public')->delete($file->path_file);
            }
            $surat->forceDelete();
        }

        LogAktivitas::catat(
            'surat_dihapus_permanen',
            "{$request->user()->nama} menghapus permanen {$suratList->count()} surat beserta lampirannya."
        );

        return redirect()->route('surat.sampah')->with('status', $suratList->count().' surat dihapus permanen.');
    }

    /**
     * Cek AJAX real-time untuk mengecek apakah nomor surat atau agenda sudah terpakai.
     */
    public function cekNomor(Request $request): JsonResponse
    {
        $nomorSurat = trim((string) $request->query('nomor_surat', ''));
        $nomorAgenda = trim((string) $request->query('nomor_agenda', ''));
        $kecuali = $request->query('kecuali');

        $cekDuplikat = function (string $kolom, string $nilai) use ($kecuali) {
            if ($nilai === '') {
                return false;
            }

            return Surat::withTrashed()
                ->where($kolom, $nilai)
                ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
                ->exists();
        };

        return response()->json([
            'nomor_surat' => [
                'sudah_dipakai' => $cekDuplikat('nomor_surat', $nomorSurat),
            ],
            'nomor_agenda' => [
                'sudah_dipakai' => $cekDuplikat('nomor_agenda', $nomorAgenda),
            ],
        ]);
    }

    /**
     * Tandai status disposisi menjadi 'Dibaca' ketika user membuka detail surat.
     */
    private function tandaiDisposisiTerkaitDibaca(User $user, Surat $surat): void
    {
        $surat->disposisi()
            ->whereHas('penerima', fn ($q) => $q->where('role_id', $user->role_id))
            ->whereIn('status', [StatusDisposisi::Terkirim, StatusDisposisi::Diterima])
            ->update(['status' => StatusDisposisi::Dibaca]);
    }

    /**
     * Soft warning via session flash jika nomor surat atau agenda terindikasi duplikat.
     */
    private function tandaiJikaNomorSudahDipakai(string $nomorSurat, ?string $nomorAgenda, ?int $kecualiId = null): void
    {
        $pesan = [];

        $suratDuplikat = Surat::withTrashed()
            ->where('nomor_surat', $nomorSurat)
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->exists();

        if ($suratDuplikat) {
            $pesan[] = "Nomor surat '{$nomorSurat}'";
        }

        if (! empty($nomorAgenda)) {
            $agendaDuplikat = Surat::withTrashed()
                ->where('nomor_agenda', $nomorAgenda)
                ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
                ->exists();

            if ($agendaDuplikat) {
                $pesan[] = "Nomor agenda '{$nomorAgenda}'";
            }
        }

        if (! empty($pesan)) {
            $daftar = implode(' dan ', $pesan);
            session()->flash('warning', "Peringatan: {$daftar} sudah pernah digunakan pada surat lain.");
        }
    }

    /**
     * Opsi penerima disposisi berdasarkan status surat dan role user.
     */
    private function penerimaOptionsUntuk(User $user, Surat $surat)
    {
        // Jika surat sudah disetujui (diterima), alur disposisi ditutup
        if ($surat->status->value === 'diterima') {
            return collect();
        }

        $roleTujuan = match (true) {
            $user->isStaff() => ['kabag_umum'],
            $user->isKabag() => ['staff_umum', 'direktur'],
            $user->isDirektur() || $user->isAdmin() => [],
            default => [],
        };

        return User::whereHas('role', fn ($q) => $q->whereIn('nama_role', $roleTujuan))->get();
    }

    private function authorizeStaffOnly(Request $request): void
    {
        abort_unless($request->user()->isStaff(), 403, 'Hanya Staff Umum yang boleh menginput surat baru.');
    }

    private function authorizeHapusSurat(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $user->isStaff() || $user->isKabag(),
            403,
            'Anda tidak memiliki akses untuk mengelola tempat sampah surat.'
        );
    }

    private function authorizeEdit(Request $request, Surat $surat): void
    {
        $user = $request->user();

        abort_unless($user->isStaff() && $user->sameRoleAs($surat->pembuat), 403, 'Anda tidak memiliki akses untuk mengedit surat ini.');

        abort_unless(in_array($surat->status->value, ['baru', 'perlu_revisi'], true), 403, 'Surat yang sudah diputuskan (diterima/ditolak) tidak bisa diedit lagi.');

        // Saat status "perlu revisi", edit hanya boleh selama disposisi masih di tangan Staff
        // (belum dikirim balik ke Kabag). Begitu dikirim ke Kabag, akses edit ditutup sampai
        // Kabag me-review lagi dan mengembalikannya.
        if ($surat->status->value === 'perlu_revisi') {
            $dispoTerakhir = $surat->disposisiTerakhir();

            abort_unless(
                $dispoTerakhir && $dispoTerakhir->penerima_id === $user->id,
                403,
                'Revisi surat ini sudah dikirim ke Kabag dan sedang menunggu review, tidak bisa diedit lagi.'
            );
        }
    }

    private function authorizeAkses(Request $request, Surat $surat): void
    {
        $user = $request->user();

        $terlibat = $user->isAdmin()
            || $user->sameRoleAs($surat->pembuat)
            || $surat->disposisi()
                ->whereHas('pengirim', fn ($q) => $q->where('role_id', $user->role_id))
                ->orWhereHas('penerima', fn ($q) => $q->where('role_id', $user->role_id))
                ->exists();

        abort_unless($terlibat, 403, 'Anda tidak memiliki akses ke surat ini.');
    }
}