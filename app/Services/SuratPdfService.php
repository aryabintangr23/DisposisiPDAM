<?php

namespace App\Services;

use App\Models\Disposisi;
use App\Models\Surat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

/**
 * Menggabungkan lembar disposisi (dibuat dari view Blade lewat dompdf) dan
 * seluruh lampiran surat (PDF/JPG/PNG) menjadi SATU file PDF utuh, memakai
 * FPDI untuk menyalin halaman-halaman PDF sumber ke dokumen keluaran.
 *
 * Format lampiran yang tidak bisa digabung langsung (mis. HEIC, DOCX) tidak
 * dilewati diam-diam — namanya dicatat dan ditampilkan di halaman catatan
 * terakhir pada PDF gabungan, supaya pengguna tahu harus mengunduhnya terpisah.
 */
class SuratPdfService
{
    /**
     * @return string Isi biner PDF gabungan.
     */
    public function gabungkan(Surat $surat, ?Disposisi $disposisi): string
    {
        $fpdi = new Fpdi();
        $fpdi->SetAutoPageBreak(false);
        $fpdi->SetMargins(0, 0, 0);

        $lampiranGagal = [];

        if ($disposisi) {
            $isiDisposisi = Pdf::loadView('disposisi.cetak', [
                'surat' => $surat,
                'disposisi' => $disposisi,
            ])->setPaper('a4')->output();

            $this->tambahkanPdfDariBinary($fpdi, $isiDisposisi);
        }

        foreach ($surat->lampiran as $file) {
            $path = Storage::disk('public')->path($file->path_file);

            if (! is_file($path)) {
                $lampiranGagal[] = $file->nama_file.' (berkas tidak ditemukan)';

                continue;
            }

            $ekstensi = strtolower(pathinfo($file->nama_file, PATHINFO_EXTENSION));

            try {
                if ($ekstensi === 'pdf') {
                    $this->tambahkanPdfDariFile($fpdi, $path);
                } elseif (in_array($ekstensi, ['jpg', 'jpeg', 'png'], true)) {
                    $this->tambahkanGambar($fpdi, $path, $ekstensi);
                } else {
                    // HEIC, DOCX, dan format lain belum bisa digabung otomatis.
                    $lampiranGagal[] = $file->nama_file;
                }
            } catch (\Throwable $e) {
                $lampiranGagal[] = $file->nama_file.' (gagal diproses)';
            }
        }

        if ($fpdi->PageNo() === 0) {
            $fpdi->SetMargins(15, 15, 15);
            $fpdi->AddPage();
            $fpdi->SetFont('Helvetica', '', 11);
            $fpdi->Write(6, 'Tidak ada lembar disposisi atau lampiran yang bisa ditampilkan.');
        }

        if (! empty($lampiranGagal)) {
            $fpdi->SetMargins(15, 15, 15);
            $fpdi->AddPage();
            $fpdi->SetFont('Helvetica', 'B', 12);
            $fpdi->Write(7, 'Catatan Lampiran');
            $fpdi->Ln(10);
            $fpdi->SetFont('Helvetica', '', 10);
            $fpdi->MultiCell(0, 6, "Berkas berikut tidak bisa digabungkan otomatis ke PDF ini karena formatnya tidak didukung. Silakan unduh berkas ini secara terpisah dari halaman detail surat pada aplikasi:\n\n- ".implode("\n- ", $lampiranGagal));
        }

        return $fpdi->Output('S');
    }

    private function tambahkanPdfDariBinary(Fpdi $fpdi, string $isiPdf): void
    {
        $sementara = tempnam(sys_get_temp_dir(), 'dispo_pdf_');
        file_put_contents($sementara, $isiPdf);

        try {
            $this->tambahkanPdfDariFile($fpdi, $sementara);
        } finally {
            @unlink($sementara);
        }
    }

    private function tambahkanPdfDariFile(Fpdi $fpdi, string $path): void
    {
        $jumlahHalaman = $fpdi->setSourceFile($path);

        for ($i = 1; $i <= $jumlahHalaman; $i++) {
            $templateId = $fpdi->importPage($i);
            $ukuran = $fpdi->getTemplateSize($templateId);

            $fpdi->AddPage(
                $ukuran['orientation'],
                [$ukuran['width'], $ukuran['height']]
            );
            $fpdi->useTemplate($templateId, 0, 0, $ukuran['width'], $ukuran['height']);
        }
    }

    private function tambahkanGambar(Fpdi $fpdi, string $path, string $ekstensi): void
    {
        $ukuran = getimagesize($path);

        if ($ukuran === false) {
            throw new \RuntimeException("Berkas gambar tidak valid: {$path}");
        }

        [$lebarPx, $tinggiPx] = $ukuran;

        // Asumsi 96 DPI untuk konversi piksel ke milimeter (cukup akurat untuk kebutuhan cetak/arsip).
        $lebarMm = $lebarPx * 25.4 / 96;
        $tinggiMm = $tinggiPx * 25.4 / 96;

        // Batasi ke ukuran maksimum kertas A4 (210 x 297 mm), skala proporsional bila lebih besar.
        $maxLebar = 210.0;
        $maxTinggi = 297.0;

        if ($lebarMm > $maxLebar || $tinggiMm > $maxTinggi) {
            $skala = min($maxLebar / $lebarMm, $maxTinggi / $tinggiMm);
            $lebarMm *= $skala;
            $tinggiMm *= $skala;
        }

        $orientasi = $lebarMm > $tinggiMm ? 'L' : 'P';

        $fpdi->AddPage($orientasi, [$lebarMm, $tinggiMm]);
        $fpdi->Image($path, 0, 0, $lebarMm, $tinggiMm, $ekstensi === 'png' ? 'PNG' : 'JPG');
    }
}
