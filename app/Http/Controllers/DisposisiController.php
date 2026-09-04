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

    public function selesaikan(
        Request $request,
        Surat $surat,
        Disposisi $disposisi,
        DisposisiRuleService $rule
    ): RedirectResponse {
        abort_unless(
            $rule->bolehMenyelesaikan($request->user()),
            403,
            'Hanya Staff yang boleh menandai disposisi selesai.'
        );

        abort_unless(
            $disposisi->surat_id === $surat->id,
            404
        );

        abort_unless(
            $disposisi->penerima_id === $request->user()->id,
            403,
            'Hanya penerima disposisi ini yang boleh menandainya selesai.'
        );

        $disposisi->update([
            'status' => StatusDisposisi::Selesai
        ]);

        return back()
            ->with('status', 'Disposisi ditandai selesai.');
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
            $dispoTerakhir &&
            $dispoTerakhir->penerima_id === $user->id,
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
     * detail surat — satu aksi sederhana yang dipakai untuk DUA situasi
     * sekaligus, supaya Kabag tidak perlu membuka form "Kirim Disposisi
     * Baru" dan memilih-milih penerima hanya untuk memutuskan surat dari
     * Staff:
     *
     * 1. Review pertama kali atas surat baru yang dikirim Staff -> Kabag
     *    (status Surat masih "Baru").
     * 2. Review revisi yang dikirim ulang oleh Staff setelah diminta
     *    perbaikan sebelumnya (status Surat "Perlu Revisi").
     *
     * Berlaku selama disposisi terakhir surat ini mengarah ke Kabag yang
     * sedang login DAN pengirimnya adalah Staff (bukan Direktur, supaya
     * tidak tertukar dengan alur keputusan Direktur -> Kabag).
     *
     * - "Diterima": surat sudah sesuai, status Surat menjadi/tetap "Baru"
     *   supaya siap diteruskan ke Direktur lewat form "Kirim Disposisi
     *   Baru" seperti biasa.
     * - "Revisi": masih belum sesuai, status Surat menjadi "Perlu Revisi"
     *   dan dikirim ulang ke Staff yang sama.
     *
     * Sama seperti keputusan(), aksi ini otomatis membuat satu disposisi
     * balasan Kabag -> Staff supaya ada jejaknya di Riwayat Disposisi.
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
                && $dispoTerakhir->penerima_id === $user->id
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
     * Generate PDF lembar disposisi untuk satu record disposisi tertentu,
     * dipakai untuk kebutuhan arsip/cetak.
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

        $terlibat = $surat->created_by === $user->id
            || $disposisi->pengirim_id === $user->id
            || $disposisi->penerima_id === $user->id;

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

        /*
         * Nomor surat TIDAK diubah.
         *
         * Nomor surat tetap digunakan secara asli
         * di dalam isi PDF, termasuk karakter / dan \.
         *
         * Untuk nama file PDF, jangan gunakan nomor surat
         * karena Windows tidak mengizinkan / dan \ dalam
         * nama file.
         */
        $namaFile = "lembar-disposisi-{$disposisi->id}.pdf";

        return $pdf->stream($namaFile);
    }
}