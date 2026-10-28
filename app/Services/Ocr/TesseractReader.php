<?php

namespace App\Services\Ocr;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Pembaca OCR lokal memakai Tesseract.
 *
 * Tidak ada panggilan ke layanan cloud. Tesseract membaca gambar menjadi
 * teks + posisi kata (TSV); ekstraktor kemudian memakai teks tersebut untuk
 * mengisi form. PDF hanya halaman pertama yang dibaca, karena satu surat
 * umumnya satu halaman utama.
 */
class TesseractReader implements PembacaOcr
{
    public function baca(string $pathAbsolut, string $mime): HasilOcr
    {
        if (! is_file($pathAbsolut)) {
            throw new RuntimeException('Berkas scan tidak ditemukan di server.');
        }

        $berkasSementara = [];

        try {
            [$gambar, $berkasSementara] = $this->siapkanGambar($pathAbsolut, $mime);

            $bahasa = (string) config('scan.languages', 'ind+eng');
            $bahasa = $bahasa !== '' ? $bahasa : 'eng';

            $tsv = $this->jalankanTesseract($gambar, $bahasa, 'tsv');

            $hasil = $this->uraiTsv($tsv);

            // Beberapa versi Tesseract tidak mengeluarkan TSV walau berhasil.
            // Fallback ke teks biasa supaya form tetap bisa diisi.
            if ($hasil->kosong()) {
                $teks = $this->jalankanTesseract($gambar, $bahasa, null);

                return new HasilOcr(trim($teks), [], 0.0);
            }

            return $hasil;
        } finally {
            foreach (array_filter($berkasSementara) as $berkas) {
                File::delete($berkas);
            }
        }
    }

    /**
     * Siapkan berkas yang benar-benar bisa dibaca Tesseract.
     *
     * @return array{0: string, 1: array<int, string>} Path gambar dan daftar berkas sementara untuk dibersihkan.
     */
    private function siapkanGambar(string $path, string $mime): array
    {
        $mime = strtolower($mime);
        $sementara = [];

        if ($mime === 'application/pdf' || str_ends_with(strtolower($path), '.pdf')) {
            $gambar = $this->konversiPdf($path);
            $sementara[] = $gambar;

            return [$gambar, $sementara];
        }

        if (in_array($mime, ['image/heic', 'image/heif'], true)
            || in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['heic', 'heif'], true)) {
            $gambar = $this->konversiHeic($path);
            $sementara[] = $gambar;

            return [$gambar, $sementara];
        }

        if (extension_loaded('gd')) {
            $gambar = $this->optimalkanDenganGd($path);

            if ($gambar !== null) {
                return [$gambar, [$gambar]];
            }
        }

        return [$path, $sementara];
    }

    /**
     * Ubah halaman pertama PDF menjadi PNG. Butuh pdftoppm (poppler) atau Imagick.
     */
    private function konversiPdf(string $path): string
    {
        $tujuan = $this->pathSementara('png');

        $biner = (string) config('scan.pdftoppm_path', 'pdftoppm');
        $process = new Process([
            $biner,
            '-f', '1',
            '-l', '1',
            '-r', '220',
            '-png',
            '-singlefile',
            $path,
            substr($tujuan, 0, -4),
        ]);

        try {
            $process->setTimeout((int) config('scan.timeout', 60));
            $process->run();

            if ($process->isSuccessful() && is_file($tujuan)) {
                return $tujuan;
            }
        } catch (Throwable) {
            // Coba jalur Imagick di bawah.
        }

        File::delete($tujuan);

        if (extension_loaded('imagick')) {
            try {
                $im = new \Imagick;
                $im->setResolution(220, 220);
                $im->readImage($path.'[0]');
                $im->setImageFormat('png');
                $im->writeImage($tujuan);
                $im->clear();

                if (is_file($tujuan)) {
                    return $tujuan;
                }
            } catch (Throwable) {
                File::delete($tujuan);
            }
        }

        throw new RuntimeException(
            'Scan PDF belum bisa diproses di server ini. Pasang poppler (pdftoppm) atau Imagick, '
            .'atau unggah foto surat (JPG/PNG).'
        );
    }

    /**
     * Ubah HEIC/HEIF menjadi PNG bila server punya Imagick atau ImageMagick.
     */
    private function konversiHeic(string $path): string
    {
        $tujuan = $this->pathSementara('png');

        if (extension_loaded('imagick')) {
            try {
                $im = new \Imagick;
                $im->readImage($path);
                $im->setImageFormat('png');
                $im->writeImage($tujuan);
                $im->clear();

                if (is_file($tujuan)) {
                    return $tujuan;
                }
            } catch (Throwable) {
                File::delete($tujuan);
            }
        }

        foreach (['magick', 'convert'] as $biner) {
            $process = new Process([$biner, $path, $tujuan]);

            try {
                $process->setTimeout((int) config('scan.timeout', 60));
                $process->run();

                if ($process->isSuccessful() && is_file($tujuan)) {
                    return $tujuan;
                }
            } catch (Throwable) {
                // Coba biner berikutnya.
            }

            File::delete($tujuan);
        }

        throw new RuntimeException(
            'Format HEIC belum didukung untuk scan. Ubah foto menjadi JPG/PNG terlebih dahulu, '
            .'atau pasang Imagick di server.'
        );
    }

    /**
     * Perbesar dan sederhanakan gambar agar OCR lebih stabil. Mengembalikan
     * null jika gambar tidak bisa diproses (biarkan Tesseract memakai aslinya).
     */
    private function optimalkanDenganGd(string $path): ?string
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        [$lebar, $tinggi, $tipe] = $info;

        $sumber = match ($tipe) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };

        if (! $sumber) {
            return null;
        }

        $sumber = $this->perbaikiOrientasi($sumber, $path, $tipe);

        $lebar = imagesx($sumber);
        $tinggi = imagesy($sumber);
        $sisiTerpanjang = max($lebar, $tinggi);

        // Tesseract paling nyaman pada gambar ~2000-2800 px. Perbesar foto
        // kecil, tetapi jangan memperbesar gambar yang sudah besar.
        $skala = 1.0;

        if ($sisiTerpanjang < 1800) {
            $skala = min(2.2, 2400 / max(1, $sisiTerpanjang));
        } elseif ($sisiTerpanjang > 3200) {
            $skala = 3200 / $sisiTerpanjang;
        }

        $lebarBaru = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));

        $tujuan = imagecreatetruecolor($lebarBaru, $tinggiBaru);
        imagecopyresampled($tujuan, $sumber, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
        imagefilter($tujuan, IMG_FILTER_GRAYSCALE);

        $pathTujuan = $this->pathSementara('png');
        imagepng($tujuan, $pathTujuan, 6);

        imagedestroy($sumber);
        imagedestroy($tujuan);

        return is_file($pathTujuan) ? $pathTujuan : null;
    }

    /**
     * Putar gambar sesuai flag orientasi EXIF (khusus JPEG).
     *
     * @return \GdImage|resource
     */
    private function perbaikiOrientasi($gambar, string $path, int $tipe)
    {
        if ($tipe !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $gambar;
        }

        $exif = @exif_read_data($path);
        $orientasi = $exif['Orientation'] ?? 1;

        return match ($orientasi) {
            3 => imagerotate($gambar, 180, 0),
            6 => imagerotate($gambar, -90, 0),
            8 => imagerotate($gambar, 90, 0),
            default => $gambar,
        };
    }

    /**
     * Jalankan Tesseract. Jika data bahasa "ind" tidak tersedia, otomatis
     * mencoba "eng" agar fitur tetap berfungsi di server minimal.
     */
    private function jalankanTesseract(string $gambar, string $bahasa, ?string $format): string
    {
        $output = $this->prosesTesseract($gambar, $bahasa, $format);

        if ($output === null && $bahasa !== 'eng') {
            $output = $this->prosesTesseract($gambar, 'eng', $format);
        }

        if ($output === null) {
            throw new RuntimeException(
                'Mesin OCR Tesseract tidak tersedia atau gagal membaca berkas. '
                .'Pastikan Tesseract dan data bahasa Indonesia sudah terpasang di server.'
            );
        }

        return $output;
    }

    private function prosesTesseract(string $gambar, string $bahasa, ?string $format): ?string
    {
        $perintah = [
            (string) config('scan.tesseract_path', 'tesseract'),
            $gambar,
            'stdout',
            '-l', $bahasa,
            '--psm', (string) config('scan.psm', 3),
        ];

        $tessdata = trim((string) config('scan.tessdata_dir', ''));

        if ($tessdata !== '') {
            $perintah[] = '--tessdata-dir';
            $perintah[] = $tessdata;
        }

        if ($format !== null) {
            $perintah[] = $format;
        }

        $process = new Process($perintah);

        try {
            $process->setTimeout((int) config('scan.timeout', 60));
            $process->run();
        } catch (Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        return $process->getOutput();
    }

    /**
     * Urai output TSV Tesseract menjadi baris teks + confidence + posisi.
     */
    private function uraiTsv(string $tsv): HasilOcr
    {
        $lines = preg_split('/\R/u', trim($tsv)) ?: [];

        if (count($lines) < 2) {
            return new HasilOcr('', [], 0.0);
        }

        array_shift($lines); // header

        $kelompok = [];
        $semuaConfidence = [];

        foreach ($lines as $line) {
            $kolom = str_getcsv($line, "\t");

            if (count($kolom) < 12) {
                continue;
            }

            $teks = trim((string) $kolom[11]);
            $conf = (float) $kolom[10];

            if ($teks === '' || $conf < 0) {
                continue;
            }

            $kunci = $kolom[0].':'.$kolom[1].':'.$kolom[2].':'.$kolom[3].':'.$kolom[4];

            $kelompok[$kunci][] = [
                'teks' => $teks,
                'conf' => $conf / 100,
                'left' => (int) $kolom[6],
                'top' => (int) $kolom[7],
                'height' => (int) $kolom[9],
            ];

            $semuaConfidence[] = $conf / 100;
        }

        $baris = [];

        foreach ($kelompok as $kataKata) {
            usort($kataKata, fn ($a, $b) => $a['left'] <=> $b['left']);

            $teks = trim(implode(' ', array_column($kataKata, 'teks')));

            if ($teks === '') {
                continue;
            }

            $conf = array_sum(array_column($kataKata, 'conf')) / max(1, count($kataKata));
            $top = min(array_column($kataKata, 'top'));

            $baris[] = [
                'teks' => $teks,
                'confidence' => round($conf, 3),
                'top' => $top,
                'bottom' => $top + max(array_column($kataKata, 'height')),
            ];
        }

        usort($baris, fn ($a, $b) => $a['top'] <=> $b['top']);

        return new HasilOcr(
            implode("\n", array_column($baris, 'teks')),
            $baris,
            $semuaConfidence === [] ? 0.0 : round(array_sum($semuaConfidence) / count($semuaConfidence), 3)
        );
    }

    private function pathSementara(string $ekstensi): string
    {
        $dir = storage_path('app/'.config('scan.temp_prefix', 'scan-surat').'/tmp');
        File::ensureDirectoryExists($dir);

        return $dir.DIRECTORY_SEPARATOR.Str::uuid().'.'.$ekstensi;
    }
}
