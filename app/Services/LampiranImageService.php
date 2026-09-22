<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Mengecilkan otomatis lampiran berupa gambar (JPG/PNG) yang berukuran besar
 * atau berdimensi sangat tinggi — umumnya hasil foto kamera HP atau scan —
 * SETELAH berkas tersimpan di disk. Validasi upload tetap mengizinkan berkas
 * sampai 10MB (lihat StoreSuratRequest/UpdateSuratRequest) sebagai jaga-jaga,
 * tapi begitu tersimpan, gambar yang terlalu besar diperkecil supaya hemat
 * ruang penyimpanan dan lebih cepat dimuat saat ditampilkan/dicetak.
 *
 * PDF tidak disentuh oleh service ini.
 */
class LampiranImageService
{
    /** Sisi terpanjang maksimum (px) setelah dikecilkan. */
    private const MAKS_DIMENSI = 2000;

    /** Ambang ukuran berkas (byte) — di bawah ini gambar tidak diutak-atik lagi. */
    private const AMBANG_UKURAN = 2 * 1024 * 1024; // 2MB

    private const KUALITAS_JPEG = 82;

    /**
     * @param  string  $pathRelatif  Path relatif pada disk 'public', mis. "lampiran/xxx.jpg".
     * @param  string  $ekstensi  Ekstensi berkas (huruf kecil), mis. "jpg", "jpeg", "png".
     * @return int  Ukuran berkas (byte) setelah diproses (sama dengan sebelumnya jika tidak diproses).
     */
    public function kompresJikaGambar(string $pathRelatif, string $ekstensi): int
    {
        $disk = Storage::disk('public');

        if (! in_array($ekstensi, ['jpg', 'jpeg', 'png'], true)) {
            return $disk->size($pathRelatif);
        }

        // GD kadang tidak aktif di server tertentu — kalau begitu lewati saja
        // kompresi dan biarkan berkas asli tersimpan apa adanya (tetap berfungsi).
        if (! extension_loaded('gd')) {
            return $disk->size($pathRelatif);
        }

        $pathAsli = $disk->path($pathRelatif);
        $ukuran = @getimagesize($pathAsli);

        if ($ukuran === false) {
            return $disk->size($pathRelatif);
        }

        [$lebar, $tinggi, $tipe] = $ukuran;
        $sisiTerpanjang = max($lebar, $tinggi);
        $ukuranBerkas = filesize($pathAsli) ?: 0;

        // Gambar sudah cukup kecil, tidak perlu diproses ulang.
        if ($sisiTerpanjang <= self::MAKS_DIMENSI && $ukuranBerkas <= self::AMBANG_UKURAN) {
            return $ukuranBerkas;
        }

        $sumber = match ($tipe) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($pathAsli),
            IMAGETYPE_PNG => @imagecreatefrompng($pathAsli),
            default => null,
        };

        if (! $sumber) {
            return $ukuranBerkas;
        }

        // Perbaiki orientasi berdasarkan data EXIF — foto dari kamera HP sering
        // tersimpan dalam orientasi "mentah" dan baru diputar oleh flag EXIF.
        $sumber = $this->perbaikiOrientasi($sumber, $pathAsli, $tipe);
        $lebar = imagesx($sumber);
        $tinggi = imagesy($sumber);
        $sisiTerpanjang = max($lebar, $tinggi);

        $lebarBaru = $lebar;
        $tinggiBaru = $tinggi;

        if ($sisiTerpanjang > self::MAKS_DIMENSI) {
            $skala = self::MAKS_DIMENSI / $sisiTerpanjang;
            $lebarBaru = max(1, (int) round($lebar * $skala));
            $tinggiBaru = max(1, (int) round($tinggi * $skala));
        }

        $tujuan = imagecreatetruecolor($lebarBaru, $tinggiBaru);

        if ($tipe === IMAGETYPE_PNG) {
            imagealphablending($tujuan, false);
            imagesavealpha($tujuan, true);
            $transparan = imagecolorallocatealpha($tujuan, 0, 0, 0, 127);
            imagefill($tujuan, 0, 0, $transparan);
        }

        imagecopyresampled($tujuan, $sumber, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);

        if ($tipe === IMAGETYPE_PNG) {
            imagepng($tujuan, $pathAsli, 6);
        } else {
            imagejpeg($tujuan, $pathAsli, self::KUALITAS_JPEG);
        }

        imagedestroy($sumber);
        imagedestroy($tujuan);

        clearstatcache(true, $pathAsli);

        return $disk->size($pathRelatif);
    }

    /**
     * Putar gambar sesuai flag orientasi EXIF (hanya relevan untuk JPEG).
     *
     * @return \GdImage|resource
     */
    private function perbaikiOrientasi($gambar, string $pathAsli, int $tipe)
    {
        if ($tipe !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $gambar;
        }

        $exif = @exif_read_data($pathAsli);
        $orientasi = $exif['Orientation'] ?? 1;

        return match ($orientasi) {
            3 => imagerotate($gambar, 180, 0),
            6 => imagerotate($gambar, -90, 0),
            8 => imagerotate($gambar, 90, 0),
            default => $gambar,
        };
    }
}
