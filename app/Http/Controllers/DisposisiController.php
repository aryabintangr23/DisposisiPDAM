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
use App\Services\SuratPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
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
        // Cek apakah surat sudah final (diterima) agar tidak bisa dikirim disposisi lagi via request manual
        abort_if(
            $surat->status->value === 'diterima',
            403,
            'Surat ini sudah disetujui (final) dan tidak bisa lagi dikirim disposisi baru.'
        );

        $data = $request->validated();
        $pengirim = $request->user();

        // Staff selalu dikirim otomatis di sisi server (bukan dari pilihan form):
        // - saat surat berstatus "perlu_revisi" dan disposisi terakhir sedang di tangan
        //   Staff, kembalikan revisi ke pihak yang memintanya (Kasubag/Kabag);
        // - selain itu kirim ke Kasubag Umum sebagai awal alur surat masuk.
        if ($pengirim->isStaff()) {
            $penerima = null;

            if ($surat->status->value === 'perlu_revisi') {
                $dispoDiTangan = $surat->disposisiTerakhir();

                if ($dispoDiTangan && $dispoDiTangan->penerima_id === $pengirim->id) {
                    $penerima = $dispoDiTangan->pengirim;
                }
            }

            $penerima ??= $rule->akunDenganRole('kasubag_umum');

            abort_unless($penerima, 422, 'Tidak ada akun Kasubag Umum yang terdaftar untuk menerima disposisi ini.');
        } else {
            $penerima = User::findOrFail($data['penerima_id']);
        }

        abort_unless(
            $rule->bolehDisposisi($pengirim, $penerima),
            403,
            'Tujuan disposisi tidak sesuai alur yang diizinkan.'
        );

        $keputusan = $data['keputusan_surat'] ?? null;

        if ($keputusan) {
            abort_unless(
                $rule->bolehSetKeputusan($pengirim, $keputusan),
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
     * Fitur keputusan (Terima/Tolak) khusus Direktur untuk surat masuk.
     * Direktur memilih jabatan tujuan disposisi (disimpan sebagai label),
     * lalu status surat diubah menjadi Diterima/Ditolak (final) dan seluruh
     * pihak yang terlibat dalam alur diberi tahu melalui pesan internal.
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
            'tujuan_jabatan' => [
                'required',
                Rule::in([...DisposisiRuleService::TUJUAN_JABATAN, DisposisiRuleService::JABATAN_LAINNYA]),
            ],
            'tujuan_jabatan_lain' => [
                'nullable',
                'string',
                'max:255',
            ],
            'tujuan_bagian' => [
                'nullable',
                'string',
                'max:255',
            ],
            'catatan' => [
                'nullable',
                'string',
            ],
        ]);

        $jabatanTujuan = $data['tujuan_jabatan'];

        if ($jabatanTujuan === DisposisiRuleService::JABATAN_LAINNYA) {
            abort_unless(
                ! empty($data['tujuan_jabatan_lain']),
                422,
                'Nama jabatan wajib diisi saat memilih "Lainnya".'
            );

            $jabatanTujuan = $data['tujuan_jabatan_lain'];
        }

        if (DisposisiRuleService::jabatanPerluBagian($jabatanTujuan)) {
            abort_unless(
                ! empty($data['tujuan_bagian']),
                422,
                'Bagian wajib diisi untuk jabatan tujuan ini.'
            );
        }

        $dispoTerakhir = $surat->disposisiTerakhir();

        abort_unless(
            $dispoTerakhir && $dispoTerakhir->penerima_id === $user->id,
            403,
            'Surat ini belum didisposisikan kepada Anda.'
        );

        abort_unless(
            $surat->status->value === 'baru',
            403,
            'Surat hanya bisa diputuskan ketika statusnya masih Baru.'
        );

        abort_unless(
            $rule->bolehSetKeputusan($user, $data['keputusan']),
            403,
            'Keputusan surat tidak sesuai alur yang diizinkan.'
        );

        $prioritas = Prioritas::Biasa;
        $tanggalDisposisi = now();

        $disposisi = $surat->disposisi()->create([
            'pengirim_id' => $user->id,
            'penerima_id' => null,
            'tujuan_jabatan' => $jabatanTujuan,
            'tujuan_bagian' => $data['tujuan_bagian'] ?? null,
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

        $label = $data['keputusan'] === 'diterima' ? 'Diterima' : 'Ditolak';
        $tujuan = $disposisi->keTujuanLabel();

        // Beri tahu seluruh pihak yang pernah terlibat dalam alur (Staff pembuat,
        // Kasubag, Kabag) tentang hasil keputusan Direktur dan arah disposisinya.
        $surat->loadMissing(['disposisi.pengirim.role', 'disposisi.penerima.role', 'pembuat']);

        $terlibat = collect([$surat->pembuat])
            ->concat($surat->disposisi->pluck('pengirim'))
            ->concat($surat->disposisi->pluck('penerima'))
            ->filter()
            ->unique('id')
            ->reject(fn (User $u) => $u->isAdmin())
            ->reject(fn (User $u) => $u->id === $user->id)
            ->values();

        $subjek = "Surat {$label} Direktur: ".$surat->nomor_surat;
        $isiDasar = "Surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) telah ditandai \"{$label}\" oleh {$user->nama} (Direktur)"
            .(! empty($data['catatan']) ? " dengan catatan: \"{$data['catatan']}\"." : '.')
            ." Disposisi hasil keputusan ditujukan ke {$tujuan}.";

        foreach ($terlibat as $penerima) {
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $penerima->id,
                'surat_id' => $surat->id,
                'subject' => $subjek,
                'body' => $isiDasar,
            ]);
        }

        LogAktivitas::catat(
            'surat_keputusan_'.$data['keputusan'],
            "{$user->nama} (Direktur) menandai surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) \"{$label}\" dan mendisposisikannya ke {$tujuan}.",
            'surat',
            $surat->id
        );

        return redirect()
            ->route('surat.show', $surat)
            ->with(
                'status',
                "Surat ditandai \"{$label}\" dan didisposisikan ke {$tujuan}."
            );
    }

    /**
     * Review surat baru oleh Kasubag/Kabag (Approve/Revisi).
     * Jika Approve, surat otomatis diteruskan ke tahap berikutnya pada alur
     * (Kasubag -> Kabag, Kabag -> Direktur); jika Revisi, dikembalikan langsung
     * ke Staff pembuat surat.
     */
    public function reviewBaru(
        Request $request,
        Surat $surat,
        DisposisiRuleService $rule
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user->isKasubag() || $user->isKabag(),
            403,
            'Hanya Kasubag/Kabag yang boleh menandai surat Approve/Revisi.'
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
                && in_array($dispoTerakhir->pengirim?->role?->nama_role, ['staff_umum', 'kasubag_umum'], true)
                && $surat->status->value === 'baru',
            403,
            'Surat ini tidak sedang menunggu review Anda.'
        );

        $staff = $dispoTerakhir->pengirim;
        $pembuat = $surat->pembuat;
        $labelRole = ucwords(str_replace('_', ' ', (string) $user->role?->nama_role));
        $prioritas = $dispoTerakhir->prioritas;
        $tanggalDisposisi = now();

        if ($data['keputusan'] === 'approve') {
            $roleTujuan = $rule->roleBerikutnya((string) $user->role?->nama_role);
            abort_unless($roleTujuan, 422, 'Tidak ada tahap berikutnya pada alur untuk diteruskan.');

            $tujuan = $rule->akunDenganRole($roleTujuan);

            abort_unless(
                $tujuan,
                422,
                'Tidak ada akun '.ucwords(str_replace('_', ' ', $roleTujuan)).' yang terdaftar untuk meneruskan surat ini.'
            );

            abort_unless(
                $rule->bolehDisposisi($user, $tujuan),
                403,
                'Tujuan penerusan disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $tujuan->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            // Kirim pesan notifikasi ke Staff pembuat bahwa suratnya telah diapprove dan diteruskan
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $staff->id,
                'surat_id' => $surat->id,
                'subject' => 'Surat Disetujui: '.$surat->nomor_surat,
                'body' => "Surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) yang Anda kirim telah "
                    ."disetujui oleh {$user->nama} ({$labelRole}) dan otomatis diteruskan ke {$tujuan->nama}.",
            ]);

            $pesanStatus = "Surat disetujui dan otomatis diteruskan ke {$tujuan->nama}.";
        } else {
            $tujuan = $pembuat ?? $staff;

            abort_unless(
                $rule->bolehDisposisi($user, $tujuan),
                403,
                'Tujuan pengembalian disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $tujuan->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            $surat->update([
                'status' => StatusSurat::PerluRevisi,
            ]);

            // Kirim pesan notifikasi ke Staff pembuat bahwa suratnya perlu direvisi
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $tujuan->id,
                'surat_id' => $surat->id,
                'subject' => 'Perlu Revisi: '.$surat->nomor_surat,
                'body' => "Surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) yang Anda kirim perlu direvisi oleh {$user->nama} ({$labelRole})"
                    .(! empty($data['catatan']) ? " dengan catatan: \"{$data['catatan']}\"." : '.'),
            ]);

            $pesanStatus = "Surat dikirim kembali ke {$tujuan->nama} untuk direvisi.";
        }

        LogAktivitas::catat(
            'review_baru_'.$data['keputusan'],
            "{$user->nama} ({$labelRole}) me-review surat baru \"{$surat->perihal}\" (No. {$surat->nomor_surat}) dari {$staff->nama}: {$pesanStatus}",
            'surat',
            $surat->id
        );

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', $pesanStatus);
    }

    /**
     * Review surat revisi dari Staff oleh Kasubag/Kabag (Diterima/Revisi lagi).
     * Jika Diterima, status kembali ke Baru dan surat diteruskan ke tahap berikutnya
     * pada alur; jika Revisi, dikembalikan lagi ke Staff pembuat.
     */
    public function reviewRevisi(
        Request $request,
        Surat $surat,
        DisposisiRuleService $rule
    ): RedirectResponse {
        $user = $request->user();

        abort_unless(
            $user->isKasubag() || $user->isKabag(),
            403,
            'Hanya Kasubag/Kabag yang boleh menandai revisi Diterima/Revisi.'
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
                && $dispoTerakhir->pengirim?->isStaff()
                && $surat->status->value === 'perlu_revisi',
            403,
            'Surat ini tidak sedang menunggu review revisi dari Anda.'
        );

        $staff = $dispoTerakhir->pengirim;
        $pembuat = $surat->pembuat;
        $labelRole = ucwords(str_replace('_', ' ', (string) $user->role?->nama_role));
        $prioritas = Prioritas::Biasa;
        $tanggalDisposisi = now();

        if ($data['keputusan'] === 'diterima') {
            $roleTujuan = $rule->roleBerikutnya((string) $user->role?->nama_role);
            abort_unless($roleTujuan, 422, 'Tidak ada tahap berikutnya pada alur untuk diteruskan.');

            $tujuan = $rule->akunDenganRole($roleTujuan);

            abort_unless(
                $tujuan,
                422,
                'Tidak ada akun '.ucwords(str_replace('_', ' ', $roleTujuan)).' yang terdaftar untuk meneruskan surat ini.'
            );

            abort_unless(
                $rule->bolehDisposisi($user, $tujuan),
                403,
                'Tujuan penerusan disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $tujuan->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            // Kirim notifikasi ke Staff pembuat bahwa revisi telah diterima
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $staff->id,
                'surat_id' => $surat->id,
                'subject' => 'Revisi Diterima: '.$surat->nomor_surat,
                'body' => "Revisi surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) yang Anda kirim telah "
                    ."diterima oleh {$user->nama} ({$labelRole}) dan otomatis diteruskan ke {$tujuan->nama}.",
            ]);

            $surat->update([
                'status' => StatusSurat::Baru,
            ]);

            $pesanStatus = "Revisi dari {$staff->nama} ditandai \"Diterima\" dan surat otomatis diteruskan ke {$tujuan->nama}.";
        } else {
            $tujuan = $pembuat ?? $staff;

            abort_unless(
                $rule->bolehDisposisi($user, $tujuan),
                403,
                'Tujuan pengembalian disposisi tidak sesuai alur yang diizinkan.'
            );

            $surat->disposisi()->create([
                'pengirim_id' => $user->id,
                'penerima_id' => $tujuan->id,
                'tanggal_disposisi' => $tanggalDisposisi,
                'prioritas' => $prioritas,
                'batas_waktu' => $rule->hitungBatasWaktu($tanggalDisposisi, $prioritas),
                'instruksi' => $data['catatan'] ?? null,
                'status' => StatusDisposisi::Terkirim,
            ]);

            $surat->update([
                'status' => StatusSurat::PerluRevisi,
            ]);

            // Kirim pesan notifikasi ke Staff pembuat bahwa revisinya masih perlu diperbaiki lagi
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $tujuan->id,
                'surat_id' => $surat->id,
                'subject' => 'Perlu Revisi Lagi: '.$surat->nomor_surat,
                'body' => "Revisi surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) yang Anda kirim masih perlu diperbaiki lagi oleh {$user->nama} ({$labelRole})"
                    .(! empty($data['catatan']) ? " dengan catatan: \"{$data['catatan']}\"." : '.'),
            ]);

            $pesanStatus = "Revisi dari {$staff->nama} ditandai \"diminta revisi kembali\".";
        }

        LogAktivitas::catat(
            'review_revisi_'.$data['keputusan'],
            "{$user->nama} ({$labelRole}) me-review revisi surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) dari {$staff->nama}: {$pesanStatus}",
            'surat',
            $surat->id
        );

        return redirect()
            ->route('surat.show', $surat)
            ->with('status', $pesanStatus);
    }

    /**
     * Endpoint AJAX untuk memantau riwayat/alur disposisi secara real time.
     * Mengembalikan HTML kartu riwayat beserta tanda tangan datanya, sehingga
     * halaman detail surat bisa menukar isinya hanya ketika ada perubahan.
     */
    public function riwayat(Request $request, Surat $surat): JsonResponse
    {
        $this->authorizeLihatRiwayat($request, $surat);

        $surat->load(['disposisi.pengirim.role', 'disposisi.penerima.role']);

        $html = view('disposisi.riwayat', compact('surat'))->render();

        return response()->json([
            'html' => $html,
            'signature' => md5(implode('|', [
                $surat->status->value,
                $surat->disposisi->count(),
                (string) $surat->disposisi->max('updated_at'),
            ])),
        ]);
    }

    /**
     * Hak akses melihat riwayat disposisi sebuah surat.
     * Mengikuti aturan akses detail surat (berbasis role, bukan per akun).
     */
    private function authorizeLihatRiwayat(Request $request, Surat $surat): void
    {
        $user = $request->user();

        $terlibat = $user->isAdmin()
            || $user->sameRoleAs($surat->pembuat)
            || $surat->disposisi()
                ->whereHas('pengirim', fn ($q) => $q->where('role_id', $user->role_id))
                ->orWhereHas('penerima', fn ($q) => $q->where('role_id', $user->role_id))
                ->exists();

        abort_unless($terlibat, 403, 'Anda tidak memiliki akses ke riwayat disposisi surat ini.');
    }

    /**
     * Export lembar disposisi ke PDF untuk dicetak/diarsip.
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

        // Hak cetak mengikuti hak akses riwayat surat (berbasis role), agar Staff Umum,
        // Kabag Umum, dan Direktur bisa mencetak lembar langkah mana pun pada alurnya.
        $this->authorizeLihatRiwayat($request, $surat);

        $disposisi->load([
            'pengirim.role',
            'penerima.role',
        ]);

        $pdf = Pdf::loadView(
            'disposisi.cetak',
            compact('surat', 'disposisi')
        )->setPaper('a4');

        // Menggunakan ID disposisi untuk nama file PDF agar terhindar dari error karakter slash pada Windows
        $namaFile = "lembar-disposisi-{$disposisi->id}.pdf";

        return $pdf->stream($namaFile);
    }

    /**
     * Cetak lembar disposisi DIGABUNG dengan seluruh lampiran surat menjadi satu
     * file PDF utuh (bukan dua file terpisah). Berbeda dari cetak() di atas
     * yang hanya berisi lembar disposisi, fitur ini SENGAJA dibatasi hanya untuk
     * Staff Umum dan Kabag Umum.
     */
    public function cetakLengkap(
        Request $request,
        Surat $surat,
        Disposisi $disposisi,
        SuratPdfService $pdfService
    ): Response {
        abort_unless(
            $disposisi->surat_id === $surat->id,
            404
        );

        abort_unless(
            $request->user()->isStaff() || $request->user()->isKabag(),
            403,
            'Hanya Staff Umum dan Kabag Umum yang boleh mencetak dokumen gabungan (lembar disposisi + lampiran) ini.'
        );

        $surat->load('lampiran');
        $disposisi->load(['pengirim.role', 'penerima.role']);

        $isiPdf = $pdfService->gabungkan($surat, $disposisi);

        // Menggunakan ID disposisi (bukan nomor surat) di nama file agar terhindar
        // dari error karakter slash pada Windows, mengikuti pola cetak() di atas.
        $namaFile = "lembar-disposisi-lengkap-{$disposisi->id}.pdf";

        return response($isiPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$namaFile}\"",
        ]);
    }
}
