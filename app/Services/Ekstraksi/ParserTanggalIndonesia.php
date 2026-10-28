<?php

namespace App\Services\Ekstraksi;

/**
 * Parser tanggal surat berbahasa Indonesia.
 *
 * Mendukung format "12 Oktober 2026", "12 Okt 2026", "12/10/2026",
 * "12-10-2026", "12.10.2026", dan "2026-10-12". Tanggal ambigu
 * (12/10 vs 10/12) diperlakukan sebagai dd/mm karena konvensi surat
 * Indonesia, lalu dicoba mm/dd kalau hasilnya tidak valid.
 */
class ParserTanggalIndonesia
{
    private const BULAN = [
        'januari' => 1, 'jan' => 1, 'january' => 1,
        'februari' => 2, 'feb' => 2, 'february' => 2,
        'maret' => 3, 'mar' => 3, 'march' => 3,
        'april' => 4, 'apr' => 4,
        'mei' => 5, 'may' => 5,
        'juni' => 6, 'jun' => 6, 'june' => 6,
        'juli' => 7, 'jul' => 7, 'july' => 7,
        'agustus' => 8, 'agu' => 8, 'agt' => 8, 'aug' => 8, 'august' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'okt' => 10, 'oct' => 10, 'october' => 10,
        'november' => 11, 'nov' => 11,
        'desember' => 12, 'des' => 12, 'dec' => 12, 'december' => 12,
    ];

    /**
     * Ambil tanggal pertama yang valid dari sebuah potongan teks.
     */
    public static function parse(string $teks): ?string
    {
        $teks = trim(preg_replace('/\s+/u', ' ', $teks) ?? '');

        if ($teks === '') {
            return null;
        }

        // 2026-10-12
        if (preg_match('/\b(20\d{2})[-\/.](\d{1,2})[-\/.](\d{1,2})\b/u', $teks, $m)) {
            return self::format((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        // 12 Oktober 2026 / 12 Okt 2026
        if (preg_match('/\b(\d{1,2})\s+([A-Za-z]+)\.?\s+(\d{4})\b/u', $teks, $m)) {
            $bulan = self::bulan($m[2]);

            if ($bulan !== null) {
                return self::format((int) $m[3], $bulan, (int) $m[1]);
            }
        }

        // 12/10/2026, 12-10-2026, 12.10.2026
        if (preg_match('/\b(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2,4})\b/u', $teks, $m)) {
            $tahun = (int) $m[3];

            if ($tahun < 100) {
                $tahun += $tahun <= 79 ? 2000 : 1900;
            }

            $pertama = self::format($tahun, (int) $m[2], (int) $m[1]);

            if ($pertama !== null) {
                return $pertama;
            }

            // Cadangan untuk format mm/dd/yyyy.
            return self::format($tahun, (int) $m[1], (int) $m[2]);
        }

        return null;
    }

    private static function bulan(string $nama): ?int
    {
        $nama = strtolower(trim($nama));
        $nama = rtrim($nama, '.');

        return self::BULAN[$nama] ?? null;
    }

    private static function format(int $tahun, int $bulan, int $hari): ?string
    {
        if ($tahun < 1900 || $tahun > 2100) {
            return null;
        }

        if (! checkdate($bulan, $hari, $tahun)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari);
    }
}
