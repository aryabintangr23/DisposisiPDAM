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
    public function index(Request $request): View
    {
        $user = $request->user();
        $tanggal = $request->query('tanggal');

        // Filter Arah Surat (Surat Masuk / Surat Keluar), dipicu dari link
        // "Surat Masuk" / "Surat Keluar" di sidebar. Nilai lain diabaikan.
        $arah = $request->query('arah');
        if (! in_array($arah, array_column(ArahSurat::cases(), 'value'), true)) {
            $arah = null;
        }

        // Filter Prioritas di dashboard, berdasarkan prioritas pada lembar
        // disposisi yang pernah dibuat untuk surat tersebut.
        $prioritas = $request->query('prioritas');
        if (! in_array($prioritas, array_column(Prioritas::cases(), 'value'), true)) {
            $prioritas = null;
        }

        // Pencarian bebas: cocokkan ke nomor surat, nomor agenda, perihal,
        // jenis surat, asal surat, dan tujuan surat sekaligus, supaya
        // pengguna tidak perlu tahu persis field mana yang harus dicari.
        $cari = trim((string) $request->query('cari', ''));
        $cari = $cari !== '' ? $cari : null;

        // Data dibagi per ROLE, bukan per akun: setiap akun staff_umum
        // melihat surat yang sama, setiap akun kabag_umum melihat surat
        // yang sama, dst. Admin: role manajemen — melihat semua surat.
        // Lihat Surat::scopeUntukRole() untuk detail cakupannya.
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

        // Tanggal-tanggal yang punya surat (untuk menandai bulatan pada
        // kalender di dashboard), dibatasi ke bulan yang sedang dilihat.
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

        return view('surat.index', compact('surat', 'tanggal', 'bulan', 'tanggalBersurat', 'arah', 'prioritas', 'cari'));
    }

    public function create(Request $request): View
    {
        $this->authorizeStaffOnly($request);

        // Asumsi: Staff juga yang menginput surat keluar, sama seperti surat masuk.
        $kabagList = User::whereHas('role', fn ($q) => $q->where('nama_role', 'kabag_umum'))->get();

        return view('surat.create', compact('kabagList'));
    }

    public function store(StoreSuratRequest $request, DisposisiRuleService $rule): RedirectResponse
    {
        $this->authorizeStaffOnly($request);

        $data = $request->validated();

        // Soft warning: cek duplikasi nomor surat & nomor agenda (termasuk
        // yang di-soft delete). Ini cuma peringatan (surat tetap disimpan),
        // supaya tidak menghalangi kasus sah seperti nomor agenda ganda
        // antar unit — lihat catatan di migration. Pengecekan yang sama
        // juga tersedia lewat cekNomor() untuk AJAX, dicek langsung di form
        // saat user masih mengetik, sebelum tombol kirim ditekan.
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

        // Lembar disposisi pertama dibuat bersamaan: Staff -> Kabag yang dipilih.
        $penerima = User::findOrFail($data['penerima_id']);
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
    public function edit(Request $request, Surat $surat): View
    {
        $this->authorizeEdit($request, $surat);

        $surat->load('lampiran');

        return view('surat.edit', compact('surat'));
    }

    public function update(UpdateSuratRequest $request, Surat $surat): RedirectResponse
    {
        $this->authorizeEdit($request, $surat);

        $data = $request->validated();

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

    public function show(Request $request, Surat $surat): View
    {
        $this->authorizeAkses($request, $surat);

        // Otomatis tandai disposisi terkait sebagai "Dibaca" saat halaman dibuka
        $this->tandaiDisposisiTerkaitDibaca($request->user(), $surat);

        $surat->load(['lampiran', 'disposisi.pengirim.role', 'disposisi.penerima.role', 'pembuat']);

        $penerimaOptions = $this->penerimaOptionsUntuk($request->user(), $surat);

        return view('surat.show', compact('surat', 'penerimaOptions'));
    }

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

    public function sampah(Request $request): View
    {
        $this->authorizeHapusSurat($request);

        $surat = Surat::untukRole($request->user())
            ->onlyTrashed()
            ->latest('deleted_at')
            ->paginate(15);

        return view('surat.sampah', compact('surat'));
    }

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
     * Tandai disposisi terkait sebagai "Dibaca" jika ditujukan ke role user aktif.
     */
    private function tandaiDisposisiTerkaitDibaca(User $user, Surat $surat): void
    {
        $surat->disposisi()
            ->whereHas('penerima', fn ($q) => $q->where('role_id', $user->role_id))
            ->whereIn('status', [StatusDisposisi::Terkirim, StatusDisposisi::Diterima])
            ->update(['status' => StatusDisposisi::Dibaca]);
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

    /**
     * BARU: dipanggil lewat AJAX (fetch) dari form input & edit surat setiap
     * kali field "Nomor Surat" / "Nomor Agenda" selesai diketik (debounced),
     * supaya peringatan "sudah digunakan" muncul SAAT INPUT, sebelum user
     * klik kirim — bukan sesudahnya seperti sebelumnya (yang berisiko bikin
     * user mengira gagal lalu mengirim ulang formnya, jadi dobel).
     *
     * Query param "kecuali" (opsional): ID surat yang sedang diedit, supaya
     * surat itu sendiri tidak dianggap "bentrok" dengan nomornya sendiri.
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
     * Soft warning (tidak menghalangi simpan) kalau nomor surat dan/atau
     * nomor agenda yang diinput sudah pernah dipakai surat lain — dipanggil
     * dari store()/update() sebagai jaring pengaman sisi server, senada
     * dengan pengecekan real-time di cekNomor().
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
     * DIPERBAIKI: setelah surat berstatus "Diterima" (disetujui Direktur),
     * alur disposisi dianggap sudah final/selesai. Form & tombol "Kirim
     * Disposisi" tidak lagi ditampilkan untuk siapa pun — termasuk Kabag
     * dan Staff — supaya tidak ada lagi disposisi baru yang dikirim atas
     * surat yang sudah disetujui.
     *
     * Sebaliknya, kalau surat berstatus "Ditolak" (atau status lain seperti
     * "Baru" / "Perlu Revisi"), form & tombol "Kirim Disposisi" tetap
     * tampil seperti biasa — karena surat yang ditolak Direktur otomatis
     * sudah dikirim kembali ke Kabag (lihat DisposisiController::keputusan()),
     * dan Kabag mungkin masih perlu meneruskannya (mis. ke Staff untuk
     * ditindaklanjuti ulang).
     */
    private function penerimaOptionsUntuk(User $user, Surat $surat)
    {
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
}