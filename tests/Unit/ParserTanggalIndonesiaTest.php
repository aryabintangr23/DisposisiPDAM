<?php

namespace Tests\Unit;

use App\Services\Ekstraksi\ParserTanggalIndonesia;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParserTanggalIndonesiaTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function tanggalValid(): array
    {
        return [
            'bulan lengkap' => ['12 Oktober 2026', '2026-10-12'],
            'bulan singkat' => ['5 Nov 2026', '2026-11-05'],
            'dengan titik' => ['31 Des. 2025', '2025-12-31'],
            'dengan label' => ['Tanggal: 12 Oktober 2026', '2026-10-12'],
            'garis miring' => ['12/10/2026', '2026-10-12'],
            'tanda hubung' => ['12-10-2026', '2026-10-12'],
            'tahun dua digit' => ['12.10.26', '2026-10-12'],
            'iso' => ['2026-10-12', '2026-10-12'],
            'di tengah kalimat' => ['Ditetapkan di Magelang pada 1 Januari 2027', '2027-01-01'],
        ];
    }

    #[DataProvider('tanggalValid')]
    public function test_membaca_tanggal_indonesia(string $teks, string $harapan): void
    {
        $this->assertSame($harapan, ParserTanggalIndonesia::parse($teks));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function tanggalTidakValid(): array
    {
        return [
            'kosong' => [''],
            'tanpa angka' => ['tanggal belum ditentukan'],
            'tanggal mustahil' => ['31 Februari 2026'],
            'tahun di luar rentang' => ['1 Januari 1800'],
        ];
    }

    #[DataProvider('tanggalTidakValid')]
    public function test_menolak_tanggal_tidak_valid(string $teks): void
    {
        $this->assertNull(ParserTanggalIndonesia::parse($teks));
    }
}
