<?php

namespace App\Http\Controllers;

use App\Enums\Prioritas;
use App\Enums\StatusDisposisi;
use App\Enums\StatusSurat;
use App\Http\Requests\StoreDisposisiRequest;
use App\Models\Disposisi;
use App\Models\LogAktivitas;
use App\Models\Message;
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
        // DIPERBAIKI: guard sisi server, senada dengan disembunyikannya form
        // "Kirim Disposisi" di halaman surat.show saat surat sudah
        // berstatus "Diterima" (final). Tanpa ini, endpoint masih bisa
        // diakses langsung (mis. lewat request manual) walau formnya sudah
        // tidak ditampilkan di UI.
        abort_if(
            $surat->status->value === 'diterima',
            403,
            'Surat ini sudah disetujui (final) dan tidak bisa lagi dikirim disposisi baru.'
        );

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
                'status' => StatusSurat::from($keputusan),
            ]);
        }

        LogAktivitas::catat(
            'disposisi_dikirim',
            "{$pengirim->nama} mengirim disposisi surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) ke {$penerima->nama}.",
            'surat',
            $surat->id
        );

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
            'status' => StatusDisposisi::Selesai,
        ]);

        LogAktivitas::catat(
            'disposisi_selesai',
            "{$request->user()->nama} menandai disposisi selesai untuk surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}).",
            'surat',
            $surat->id
        );

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
                Rule::in(['diterima', 'ditolak']),
            ],
            'catatan' => [
                'nullable',
                'string',
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
            'status' => StatusSurat::from($data['keputusan']),
        ]);

        $label = $data['keputusan'] === 'diterima'
            ? 'Diterima'
            : 'Ditolak';

        LogAktivitas::catat(
            'surat_keputusan_'.$data['keputusan'],
            "{$user->nama} (Direktur) menandai surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) \"{$label}\" dan mengirimnya kembali ke {$kabag->nama}.",
            'surat',
            $surat->id
        );

        return redirect()
            ->route('surat.show', $surat)
            ->with(
                'status',
                "Surat ditandai \"{$label}\" dan dikirim kembali ke {$kabag->nama}."
            );
    }

    /**
     * Tombol "Approve" / "Revisi" khusus Kabag di halaman detail surat,
     * dipakai saat Kabag pertama kali menerima surat baru dari Staff
     * (status Surat masih "Baru", dan disposisi terakhir adalah
     * Staff -> Kabag ini).
     *
     * - "Approve": surat disetujui. Staff pengirim otomatis diberi
     *   notifikasi lewat menu Pesan bahwa suratnya disetujui, dan surat
     *   otomatis diteruskan (dibuat satu disposisi baru Kabag -> Direktur)
     *   tanpa Kabag perlu memilih penerima secara manual lewat form "Kirim
     *   Disposisi Baru".
     * - "Revisi": surat dikirim balik ke Staff yang sama sebagai disposisi
     *   balasan, dan status Surat diubah menjadi "Perlu Revisi" (Staff akan
     *   melihat kartu "Perlu Revisi" di halaman surat ini dan bisa langsung
     *   edit & kirim ulang).
     */
    public function reviewBaru(
        Request $request,
        Surat $surat,
        DisposisiRuleService $rule
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user->isKabag(),
            403,
            'Hanya Kabag yang boleh menandai surat Approve/Revisi.'
        );

        $data = $request->validate([
            'keputusan' => [
                'required',
                Rule::in(['approve', 'revisi']),
            ],
            'catatan' => [
                'nullable',
                'string',
            ],
        ]);

        $dispoTerakhir = $surat->disposisiTerakhir();

        abort_unless(
            $dispoTerakhir
                && $dispoTerakhir->penerima_id === $user->id
                && $dispoTerakhir->pengirim?->isStaff()
                && $surat->status->value === 'baru',
            403,
            'Surat ini tidak sedang menunggu review Anda sebagai Kabag.'
        );

        $staff = $dispoTerakhir->pengirim;
        $prioritas = $dispoTerakhir->prioritas;
        $tanggalDisposisi = now();

        if ($data['keputusan'] === 'approve') {
            $direktur = User::whereHas(
                'role',
                fn ($q) => $q->where('nama_role', 'direktur')
            )->first();

            abort_unless(
                $direktur,
                422,
                'Tidak ada akun Direktur yang terdaftar untuk meneruskan surat ini.'
            );

            abort_unless(
                $rule->bolehDisposisi($user, $direktur),
                403,
                'Tujuan penerusan disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $direktur->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            // Disposisi di atas otomatis mengirim Pesan ke Direktur (lihat
            // Disposisi::booted()), bukan ke Staff — jadi Staff pengirim
            // perlu diberi tahu terpisah bahwa suratnya sudah disetujui.
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $staff->id,
                'surat_id' => $surat->id,
                'subject' => 'Surat Disetujui: '.$surat->nomor_surat,
                'body' => "Surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) yang Anda kirim telah "
                    ."disetujui oleh {$user->nama} (Kabag) dan otomatis diteruskan ke {$direktur->nama} (Direktur).",
            ]);

            $pesanStatus = "Surat disetujui dan otomatis diteruskan ke {$direktur->nama} (Direktur).";
        } else {
            abort_unless(
                $rule->bolehDisposisi($user, $staff),
                403,
                'Tujuan pengembalian disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $staff->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            $surat->update([
                'status' => StatusSurat::PerluRevisi,
            ]);

            $pesanStatus = "Surat dikirim kembali ke {$staff->nama} untuk direvisi.";
        }

        LogAktivitas::catat(
            'review_baru_'.$data['keputusan'],
            "{$user->nama} (Kabag) me-review surat baru \"{$surat->perihal}\" (No. {$surat->nomor_surat}) dari {$staff->nama}: {$pesanStatus}",
            'surat',
            $surat->id
        );

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', $pesanStatus);
    }

    /**
     * Tombol "Diterima" / "Minta Revisi Lagi" khusus Kabag di halaman detail
     * surat, dipakai untuk mereview revisi yang baru dikirim balik oleh
     * Staff (status Surat masih "Perlu Revisi", dan disposisi terakhir
     * adalah Staff -> Kabag ini).
     *
     * - "Diterima": revisi sudah sesuai. Sama seperti reviewBaru() saat
     *   Approve surat baru, surat ini otomatis diteruskan (dibuat satu
     *   disposisi baru Kabag -> Direktur) tanpa Kabag perlu membuka form
     *   "Kirim Disposisi Baru" secara manual, dan Staff pengirim revisi
     *   diberi notifikasi terpisah lewat menu Pesan bahwa revisinya
     *   diterima & surat diteruskan ke Direktur. Status Surat tetap "baru"
     *   (dipakai bersama $sedangDitindaklanjuti di halaman surat.show untuk
     *   menyembunyikan form kirim disposisi Staff & menampilkan label
     *   "Sedang Ditindaklanjuti").
     * - "Revisi": masih belum sesuai, status Surat tetap/kembali "Perlu
     *   Revisi" dan dikirim ulang sebagai disposisi balasan Kabag -> Staff
     *   yang sama, seperti sebelumnya.
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
            'Hanya Kabag yang boleh menandai revisi Diterima/Revisi.'
        );

        $data = $request->validate([
            'keputusan' => [
                'required',
                Rule::in(['diterima', 'revisi']),
            ],
            'catatan' => [
                'nullable',
                'string',
            ],
        ]);

        $dispoTerakhir = $surat->disposisiTerakhir();

        abort_unless(
            $dispoTerakhir
                && $dispoTerakhir->penerima_id === $user->id
                && $surat->status->value === 'perlu_revisi',
            403,
            'Surat ini tidak sedang menunggu review revisi dari Anda.'
        );

        $staff = $dispoTerakhir->pengirim;
        $prioritas = Prioritas::Biasa;
        $tanggalDisposisi = now();

        if ($data['keputusan'] === 'diterima') {
            $direktur = User::whereHas(
                'role',
                fn ($q) => $q->where('nama_role', 'direktur')
            )->first();

            abort_unless(
                $direktur,
                422,
                'Tidak ada akun Direktur yang terdaftar untuk meneruskan surat ini.'
            );

            abort_unless(
                $rule->bolehDisposisi($user, $direktur),
                403,
                'Tujuan penerusan disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $direktur->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            // Disposisi di atas otomatis mengirim Pesan ke Direktur (lihat
            // Disposisi::booted()), bukan ke Staff — jadi Staff pengirim
            // revisi perlu diberi tahu terpisah bahwa revisinya diterima.
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $staff->id,
                'surat_id' => $surat->id,
                'subject' => 'Revisi Diterima: '.$surat->nomor_surat,
                'body' => "Revisi surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) yang Anda kirim telah "
                    ."diterima oleh {$user->nama} (Kabag) dan otomatis diteruskan ke {$direktur->nama} (Direktur).",
            ]);

            $surat->update([
                'status' => StatusSurat::Baru,
            ]);

            $pesanStatus = "Revisi dari {$staff->nama} ditandai \"Diterima\" dan surat otomatis diteruskan ke {$direktur->nama} (Direktur).";
        } else {
            abort_unless(
                $rule->bolehDisposisi($user, $staff),
                403,
                'Tujuan pengembalian disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $staff->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            $surat->update([
                'status' => StatusSurat::PerluRevisi,
            ]);

            $pesanStatus = "Revisi dari {$staff->nama} ditandai \"diminta revisi kembali\".";
        }

        LogAktivitas::catat(
            'review_revisi_'.$data['keputusan'],
            "{$user->nama} (Kabag) me-review revisi surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) dari {$staff->nama}: {$pesanStatus}",
            'surat',
            $surat->id
        );

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', $pesanStatus);
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

        // Izin cetak disamakan dengan izin buka surat (lihat
        // SuratController::authorizeAkses): admin, pembuat surat, atau yang
        // terlibat di salah satu disposisi surat ini boleh mencetak lembar
        // disposisi apa pun di riwayatnya.
        $terlibat = $user->isAdmin()
            || $surat->created_by === $user->id
            || $surat->disposisi()
                ->where('pengirim_id', $user->id)
                ->orWhere('penerima_id', $user->id)
                ->exists();

        abort_unless(
            $terlibat,
            403,
            'Anda tidak memiliki akses untuk mencetak lembar disposisi ini.'
        );

        $disposisi->load([
            'pengirim.role',
            'penerima.role',
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