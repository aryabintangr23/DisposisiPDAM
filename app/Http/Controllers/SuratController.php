<?php

namespace App\Http\Controllers;

use App\Enums\Prioritas;
use App\Enums\StatusDisposisi;
use App\Http\Requests\StoreSuratRequest;
use App\Models\Disposisi;
use App\Models\Surat;
use App\Models\User;
use App\Services\DisposisiRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuratController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Staff melihat surat yang ia buat sendiri. Kabag & Direktur melihat
        // surat yang pernah masuk/keluar melalui mereka (sebagai pengirim
        // atau penerima disposisi).
        if ($user->isStaff()) {
            $surat = Surat::where('created_by', $user->id)->latest()->paginate(15);
        } else {
            $suratIds = Disposisi::where('penerima_id', $user->id)
                ->orWhere('pengirim_id', $user->id)
                ->pluck('surat_id')
                ->unique();

            $surat = Surat::whereIn('id', $suratIds)->latest()->paginate(15);
        }

        return view('surat.index', compact('surat'));
    }

    public function create(Request $request): View
    {
        $this->authorizeStaffOnly($request);

        // Asumsi (perlu dikonfirmasi kalau salah): Staff juga yang menginput
        // surat keluar, sama seperti surat masuk.
        $kabagList = User::whereHas('role', fn ($q) => $q->where('nama_role', 'kabag_umum'))->get();

        return view('surat.create', compact('kabagList'));
    }

    public function store(StoreSuratRequest $request, DisposisiRuleService $rule): RedirectResponse
    {
        $this->authorizeStaffOnly($request);

        $data = $request->validated();

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

        return redirect()->route('surat.show', $surat)->with('status', 'Surat dan lembar disposisi berhasil dibuat.');
    }

    public function show(Request $request, Surat $surat): View
    {
        $this->authorizeAkses($request, $surat);

        $surat->load(['lampiran', 'disposisi.pengirim.role', 'disposisi.penerima.role', 'pembuat']);

        $penerimaOptions = $this->penerimaOptionsUntuk($request->user());

        return view('surat.show', compact('surat', 'penerimaOptions'));
    }

    private function authorizeStaffOnly(Request $request): void
    {
        abort_unless($request->user()->isStaff(), 403, 'Hanya Staff Umum yang boleh menginput surat baru.');
    }

    private function authorizeAkses(Request $request, Surat $surat): void
    {
        $user = $request->user();

        $terlibat = $surat->created_by === $user->id
            || $surat->disposisi()
                ->where('pengirim_id', $user->id)
                ->orWhere('penerima_id', $user->id)
                ->exists();

        abort_unless($terlibat, 403, 'Anda tidak memiliki akses ke surat ini.');
    }

    private function penerimaOptionsUntuk(User $user)
    {
        $roleTujuan = match (true) {
            $user->isStaff() => ['kabag_umum'],
            $user->isKabag() => ['staff_umum', 'direktur'],
            $user->isDirektur() => ['kabag_umum'],
            default => [],
        };

        return User::whereHas('role', fn ($q) => $q->whereIn('nama_role', $roleTujuan))->get();
    }
}
