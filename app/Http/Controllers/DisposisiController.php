<?php

namespace App\Http\Controllers;

use App\Enums\Prioritas;
use App\Enums\StatusDisposisi;
use App\Enums\StatusSurat;
use App\Http\Requests\StoreDisposisiRequest;
use App\Models\Disposisi;
use App\Models\Surat;
use App\Models\User;
use App\Services\DisposisiRuleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class DisposisiController extends Controller
{
    public function store(
        StoreDisposisiRequest $request,
        Surat $surat,
        DisposisiRuleService $rule
    ): RedirectResponse {
        $data = $request->validated();
        $pengirim = $request->user();
        $penerima = User::findOrFail($data['penerima_id']);

        abort_unless(
            $rule->bolehDisposisi($pengirim, $penerima),
            403,
            'Tujuan disposisi tidak sesuai alur yang diizinkan.'
        );

        $keputusan = $data['keputusan_surat'] ?? null;

        if ($keputusan) {
            abort_unless(
                $rule->bolehSetKeputusan(
                    $pengirim,
                    $penerima,
                    $keputusan
                ),
                403,
                'Keputusan surat tidak sesuai alur yang diizinkan.'
            );
        }

        $prioritas = Prioritas::from($data['prioritas']);
        $tanggalDisposisi = now();

        $surat->disposisi()->create([
            'pengirim_id' => $pengirim->id,
            'penerima_id' => $penerima->id,
            'tanggal_disposisi' => $tanggalDisposisi,
            'prioritas' => $prioritas,
            'batas_waktu' => $rule->hitungBatasWaktu(
                $tanggalDisposisi,
                $prioritas
            ),
            'instruksi' => $data['instruksi'] ?? null,
            'status' => StatusDisposisi::Terkirim,
        ]);

        if ($keputusan) {
            $surat->update([
                'status' => StatusSurat::from($keputusan)
            ]);
        }

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', 'Disposisi berhasil dikirim.');
    }

    /**
     * Tandai disposisi sedang dalam proses pengerjaan (Dibaca -> Ditindaklanjuti).
     */
    public function tindaklanjuti(
        Request $request,
        Surat $surat,
        Disposisi $disposisi
    ): RedirectResponse {
        abort_unless(
            $disposisi->surat_id === $surat->id,
            404
        );

        abort_unless(
            $request->user()->sameRoleAs($disposisi->penerima),
            403,
            'Hanya penerima disposisi ini yang boleh menindaklanjuti.'
        );

        // Hanya diperbolehkan jika status disposisi saat ini sudah "Dibaca"
        abort_unless(
            $disposisi->status === StatusDisposisi::Dibaca,
            400,
            'Disposisi hanya dapat ditindaklanjuti dari status Dibaca.'
        );

        $disposisi->update([
            'status' => StatusDisposisi::Ditindaklanjuti
        ]);

        return back()
            ->with('status', 'Disposisi ditandai sedang ditindaklanjuti.');
    }

    /**
     * Tandai disposisi selesai (Ditindaklanjuti -> Selesai).
     */
    public function selesaikan(Request $request, Surat $surat, Disposisi $disposisi): RedirectResponse
    {
        abort_unless(
            $disposisi->surat_id === $surat->id,
            404
        );

        // Cek otorisasi: hanya Staff dengan role yang sama dengan penerima
        // disposisi ini yang boleh menandai selesai (konsisten dengan cara
        // otorisasi tindaklanjuti() di atas & tombol di surat/show.blade.php).
        abort_unless(
            $request->user()->isStaff() && $request->user()->sameRoleAs($disposisi->penerima),
            403,
            'Hanya Staff penerima disposisi ini yang boleh menandai Selesai.'
        );

        // Hanya diperbolehkan jika status disposisi saat ini "Ditindaklanjuti"
        abort_unless(
            $disposisi->status === StatusDisposisi::Ditindaklanjuti,
            400,
            'Disposisi hanya dapat ditandai Selesai dari status Ditindaklanjuti.'
        );

        $disposisi->update([
            'status' => StatusDisposisi::Selesai,
        ]);

        return redirect()->back()->with('status', 'Disposisi telah ditandai Selesai.');
    }

    /**
     * Tombol "Terima" / "Tolak" khusus Direktur di halaman detail surat.
     * Otomatis membuat disposisi balasan Direktur -> Kabag yang mengirim
     * surat ini sebelumnya, sekaligus mengubah status Surat.
     */
    public function keputusan(
        Request $request,
        Surat $surat,
        DisposisiRuleService $rule
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user->isDirektur(),
            403,
            'Hanya Direktur yang boleh memberikan keputusan Terima/Tolak.'
        );

        $data = $request->validate([
            'keputusan' => [
                'required',
                Rule::in(['diterima', 'ditolak'])
            ],
            'catatan' => [
                'nullable',
                'string'
            ],
        ]);

        $dispoTerakhir = $surat->disposisiTerakhir();

        abort_unless(
            $dispoTerakhir && $user->sameRoleAs($dispoTerakhir->penerima),
            403,
            'Surat ini belum didisposisikan kepada Anda.'
        );

        $kabag = $dispoTerakhir->pengirim;

        abort_unless(
            $rule->bolehDisposisi($user, $kabag),
            403,
            'Tujuan pengembalian disposisi tidak sesuai alur yang diizinkan.'
        );

        abort_unless(
            $rule->bolehSetKeputusan(
                $user,
                $kabag,
                $data['keputusan']
            ),
            403,
            'Keputusan surat tidak sesuai alur yang diizinkan.'
        );

        $prioritas = Prioritas::Biasa;
        $tanggalDisposisi = now();

        $surat->disposisi()->create([
            'pengirim_id' => $user->id,
            'penerima_id' => $kabag->id,
            'tanggal_disposisi' => $tanggalDisposisi,
            'prioritas' => $prioritas,
            'batas_waktu' => $rule->hitungBatasWaktu(
                $tanggalDisposisi,
                $prioritas
            ),
            'instruksi' => $data['catatan'] ?? null,
            'status' => StatusDisposisi::Terkirim,
        ]);

        $surat->update([
            'status' => StatusSurat::from($data['keputusan'])
        ]);

        $label = $data['keputusan'] === 'diterima'
            ? 'Diterima'
            : 'Ditolak';

        return redirect()
            ->route('surat.show', $surat)
            ->with(
                'status',
                "Surat ditandai \"{$label}\" dan dikirim kembali ke {$kabag->nama}."
            );
    }

    /**
     * Tombol "Diterima" / "Minta Revisi (Lagi)" khusus Kabag di halaman
     * detail surat.
     */
    public function reviewRevisi(
        Request $request,
        Surat $surat,
        DisposisiRuleService $rule
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user->isKabag(),
            403,
            'Hanya Kabag yang boleh menandai surat Diterima/Revisi.'
        );

        $data = $request->validate([
            'keputusan' => [
                'required',
                Rule::in(['diterima', 'revisi'])
            ],
            'catatan' => [
                'nullable',
                'string'
            ],
        ]);

        $dispoTerakhir = $surat->disposisiTerakhir();

        abort_unless(
            $dispoTerakhir
                && $user->sameRoleAs($dispoTerakhir->penerima)
                && $dispoTerakhir->pengirim?->isStaff()
                && in_array($surat->status->value, ['baru', 'perlu_revisi'], true),
            403,
            'Surat ini tidak sedang menunggu review dari Anda.'
        );

        $staff = $dispoTerakhir->pengirim;

        abort_unless(
            $rule->bolehDisposisi($user, $staff),
            403,
            'Tujuan pengembalian disposisi tidak sesuai alur yang diizinkan.'
        );

        $prioritas = Prioritas::Biasa;
        $tanggalDisposisi = now();

        $surat->disposisi()->create([
            'pengirim_id' => $user->id,
            'penerima_id' => $staff->id,
            'tanggal_disposisi' => $tanggalDisposisi,
            'prioritas' => $prioritas,
            'batas_waktu' => $rule->hitungBatasWaktu(
                $tanggalDisposisi,
                $prioritas
            ),
            'instruksi' => $data['catatan'] ?? null,
            'status' => StatusDisposisi::Terkirim,
        ]);

        $surat->update([
            'status' => $data['keputusan'] === 'diterima'
                ? StatusSurat::Baru
                : StatusSurat::PerluRevisi,
        ]);

        $label = $data['keputusan'] === 'diterima'
            ? 'Diterima'
            : 'diminta revisi';

        return redirect()
            ->route('surat.show', $surat)
            ->with(
                'status',
                "Surat dari {$staff->nama} ditandai \"{$label}\"."
            );
    }

    /**
     * Generate PDF lembar disposisi untuk satu record disposisi tertentu.
     */
    public function cetak(
        Request $request,
        Surat $surat,
        Disposisi $disposisi
    ): Response {
        abort_unless(
            $disposisi->surat_id === $surat->id,
            404
        );

        $user = $request->user();

        $terlibat = $user->isAdmin()
            || $user->sameRoleAs($surat->pembuat)
            || $user->sameRoleAs($disposisi->pengirim)
            || $user->sameRoleAs($disposisi->penerima);

        abort_unless(
            $terlibat,
            403,
            'Anda tidak memiliki akses untuk mencetak lembar disposisi ini.'
        );

        $disposisi->load([
            'pengirim.role',
            'penerima.role'
        ]);

        $pdf = Pdf::loadView(
            'disposisi.cetak',
            compact('surat', 'disposisi')
        )->setPaper('a4');

        $namaFile = "lembar-disposisi-{$disposisi->id}.pdf";

        return $pdf->stream($namaFile);
    }
}
