<?php

namespace App\Http\Controllers;

use App\Models\Disposisi;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Models\TautanPublikDisposisi;
use App\Services\SuratPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TautanPublikController extends Controller
{
    /**
     * Buat tautan publik untuk lembar disposisi + lampiran sebuah surat.
     * Hanya Staff Umum yang boleh membuat, menyalin, atau mengirim tautan ini —
     * begitu tautan dibuat, siapa pun yang memegangnya bisa membukanya tanpa login.
     */
    public function store(Request $request, Surat $surat, Disposisi $disposisi): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $disposisi->surat_id === $surat->id,
            404
        );

        abort_unless(
            $user->isStaff(),
            403,
            'Hanya Staff Umum yang boleh membuat tautan publik.'
        );

        abort_unless(
            $surat->status->value === 'diterima',
            403,
            'Tautan publik hanya bisa dibagikan setelah surat disetujui Direktur.'
        );

        // Pakai ulang tautan yang masih berlaku untuk disposisi yang sama, supaya
        // tidak menumpuk tautan baru setiap kali tombol "Bagikan" ditekan berulang.
        $tautan = TautanPublikDisposisi::bagikanUntuk($surat, $disposisi, $user->id);

        if ($tautan->wasRecentlyCreated) {
            LogAktivitas::catat(
                'tautan_publik_dibuat',
                "{$user->nama} membuat tautan publik untuk surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}).",
                'surat',
                $surat->id
            );
        }

        return back()->with('tautanPublikUrl', route('tautanPublik.publik', $tautan->token));
    }

    /**
     * Cabut/nonaktifkan tautan publik. Hanya Staff Umum.
     */
    public function destroy(Request $request, TautanPublikDisposisi $tautanPublik): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->isStaff(),
            403,
            'Hanya Staff Umum yang boleh mencabut tautan publik.'
        );

        $surat = $tautanPublik->surat;
        $tautanPublik->delete();

        LogAktivitas::catat(
            'tautan_publik_dicabut',
            "{$user->nama} mencabut tautan publik surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}).",
            'surat',
            $surat->id
        );

        return back()->with('status', 'Tautan publik berhasil dicabut.');
    }

    /**
     * Tampilkan PDF gabungan (lembar disposisi + lampiran) lewat tautan publik.
     *
     * PENTING: endpoint ini SENGAJA tidak memakai middleware auth — inilah yang
     * membuatnya "publik". Siapa pun yang memegang link token-nya bisa membuka
     * tanpa perlu login ke aplikasi. Yang dibatasi hanya SIAPA yang boleh MEMBUAT
     * tautan ini (lihat method store() di atas), bukan siapa yang boleh membukanya.
     */
    public function tampilkan(TautanPublikDisposisi $tautanPublik, SuratPdfService $pdfService): Response
    {
        abort_if($tautanPublik->isKadaluarsa(), 410, 'Tautan ini sudah kedaluwarsa.');

        $surat = $tautanPublik->surat()->with('lampiran')->firstOrFail();
        $disposisi = $tautanPublik->disposisi()->with(['pengirim.role', 'penerima.role'])->first();

        $tautanPublik->increment('jumlah_akses');
        $tautanPublik->update(['terakhir_diakses_at' => now()]);

        $isiPdf = $pdfService->gabungkan($surat, $disposisi);

        $namaFile = 'lembar-disposisi-'.str_replace(['/', '\\'], '-', $surat->nomor_surat).'.pdf';

        return response($isiPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$namaFile}\"",
        ]);
    }
}