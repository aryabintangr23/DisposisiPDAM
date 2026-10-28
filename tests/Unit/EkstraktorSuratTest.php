<?php

namespace Tests\Unit;

use App\Services\Ekstraksi\EkstraktorSurat;
use App\Services\Ocr\HasilOcr;
use PHPUnit\Framework\TestCase;

class EkstraktorSuratTest extends TestCase
{
    private EkstraktorSurat $ekstraktor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ekstraktor = new EkstraktorSurat;
    }

    public function test_mengekstrak_surat_masuk_dari_instansi_lain(): void
    {
        $ocr = $this->buatOcr([
            'PEMERINTAH KABUPATEN MAGELANG',
            'DINAS PENDIDIKAN',
            'Jl. Soekarno Hatta No. 12, Magelang',
            'Nomor: 421/123/DISDIK/2026',
            'Tanggal: 12 Oktober 2026',
            'Perihal: Undangan Rapat Koordinasi',
            'Kepada Yth. Direktur Perumda Tirta Gemilang',
            'di tempat',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr);

        $this->assertSame('421/123/DISDIK/2026', $hasil['fields']['nomor_surat']['value']);
        $this->assertSame('2026-10-12', $hasil['fields']['tanggal_surat']['value']);
        $this->assertSame('Undangan Rapat Koordinasi', $hasil['fields']['perihal']['value']);
        $this->assertStringContainsString('PEMERINTAH KABUPATEN MAGELANG', $hasil['fields']['surat_dari']['value']);
        $this->assertSame('masuk', $hasil['fields']['arah_surat']['value']);
        $this->assertSame('Surat Undangan', $hasil['fields']['jenis_surat']['value']);
    }

    public function test_mengekstrak_surat_keluar_tanpa_mengisi_surat_dari(): void
    {
        $ocr = $this->buatOcr([
            'PERUMDA AIR MINUM TIRTA GEMILANG',
            'KABUPATEN MAGELANG',
            'Nomor: 123/PDAM/X/2026',
            'Tanggal: 5 November 2026',
            'Perihal: Penawaran Harga',
            'Kepada Yth. PT Maju Jaya',
            'di tempat',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr);

        $this->assertSame('123/PDAM/X/2026', $hasil['fields']['nomor_surat']['value']);
        $this->assertSame('2026-11-05', $hasil['fields']['tanggal_surat']['value']);
        $this->assertSame('Penawaran Harga', $hasil['fields']['perihal']['value']);
        $this->assertSame('PT Maju Jaya', $hasil['fields']['tujuan_surat']['value']);
        $this->assertSame('keluar', $hasil['fields']['arah_surat']['value']);
        $this->assertSame('Surat Penawaran', $hasil['fields']['jenis_surat']['value']);
        $this->assertArrayNotHasKey('surat_dari', $hasil['fields']);
    }

    public function test_mengekstrak_tujuan_surat_dari_tata_letak_dua_kolom(): void
    {
        $ocr = $this->buatOcr([
            'Nomor : 690.101/64/1X/2026 Kepada',
            'Lampiran :- Yth. Wakil Dekan Bidang Akademik &',
            'Perihal : Balasan Ijin Kerja Praktik Kemahasiswaan',
            'Universitas Muhammadiyah Magelang',
            'di-',
            'MAGELANG',
            'Sehubungan dengan surat yang diajukan kepada kami, kami sampaikan balasan.',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr, 'keluar');

        $this->assertSame(
            'Wakil Dekan Bidang Akademik & Universitas Muhammadiyah Magelang',
            $hasil['fields']['tujuan_surat']['value']
        );
        $this->assertNotContains('Tujuan surat tidak terbaca otomatis.', $hasil['peringatan']);
    }

    public function test_kepada_di_badan_surat_tidak_dianggap_tujuan(): void
    {
        $ocr = $this->buatOcr([
            'PERUMDA AIR MINUM TIRTA GEMILANG',
            'Nomor: 55/PDAM/2026',
            'Perihal: Pemberitahuan Pemeliharaan',
            'Sehubungan dengan hal tersebut, surat yang diajukan kepada kami sudah dicatat.',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr);

        $this->assertArrayNotHasKey('tujuan_surat', $hasil['fields']);
        $this->assertContains('Tujuan surat tidak terbaca otomatis.', $hasil['peringatan']);
    }

    public function test_mengekstrak_nomor_agenda_dan_memprioritaskan_nomor_surat(): void
    {
        $ocr = $this->buatOcr([
            'PERUMDA AIR MINUM TIRTA GEMILANG',
            'SURAT MASUK',
            'Nomor : 690.101/64/IX/2026',
            'Nomor Agenda : 1247/SM/IX/2026',
            'Tanggal : 11 September 2026',
            'Dari : Universitas Muhammadiyah Magelang',
            'Nomor Surat : 507/FT/II.3.AU/F/2026',
            'Perihal : Permohonan Ijin Pelaksanaan Kerja Praktik',
            'Kepada Yth. Direktur PERUMDA Air Minum Tirta Gemilang',
            'di -',
            'MAGELANG',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr, 'masuk');

        $this->assertSame('1247/SM/IX/2026', $hasil['fields']['nomor_agenda']['value']);
        $this->assertSame('507/FT/II.3.AU/F/2026', $hasil['fields']['nomor_surat']['value']);
        $this->assertSame('Universitas Muhammadiyah Magelang', $hasil['fields']['surat_dari']['value']);
    }

    public function test_nomor_agenda_tidak_diisi_untuk_surat_keluar(): void
    {
        $ocr = $this->buatOcr([
            'PERUMDA AIR MINUM TIRTA GEMILANG',
            'Nomor Agenda : 1247/SM/IX/2026',
            'Nomor : 55/PDAM/2026',
            'Perihal : Pemberitahuan Pemeliharaan',
            'Kepada Yth. PT Maju Jaya',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr, 'keluar');

        $this->assertArrayNotHasKey('nomor_agenda', $hasil['fields']);
    }

    public function test_arah_terkunci_menimpa_deteksi_otomatis(): void
    {
        $ocr = $this->buatOcr([
            'PERUMDA AIR MINUM TIRTA GEMILANG',
            'Nomor: 55/PDAM/2026',
            'Perihal: Pemberitahuan Pemeliharaan',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr, 'masuk');

        $this->assertSame('masuk', $hasil['fields']['arah_surat']['value']);
        $this->assertSame(1.0, $hasil['fields']['arah_surat']['confidence']);
    }

    public function test_nomor_telepon_dan_tanggal_palsu_tidak_diambil_sebagai_nomor_surat(): void
    {
        $ocr = $this->buatOcr([
            'Nomor Telepon: 0812 3456 7890',
            'Perihal: Laporan Bulanan',
        ]);

        $hasil = $this->ekstraktor->ekstrak($ocr);

        $this->assertArrayNotHasKey('nomor_surat', $hasil['fields']);
        $this->assertContains('Nomor surat tidak terbaca otomatis.', $hasil['peringatan']);
    }

    public function test_kualitas_ocr_rendah_memberi_peringatan(): void
    {
        $ocr = new HasilOcr(
            'Nomor: 1/A/2026',
            [['teks' => 'Nomor: 1/A/2026', 'confidence' => 0.2, 'top' => 0, 'bottom' => 10]],
            0.2
        );

        $hasil = $this->ekstraktor->ekstrak($ocr);

        $this->assertContains('Kualitas foto/scan rendah. Periksa kembali semua isian hasil scan.', $hasil['peringatan']);
    }

    /**
     * @param  array<int, string>  $baris
     */
    private function buatOcr(array $baris): HasilOcr
    {
        $data = [];

        foreach ($baris as $i => $teks) {
            $data[] = [
                'teks' => $teks,
                'confidence' => 0.95,
                'top' => $i * 20,
                'bottom' => ($i * 20) + 15,
            ];
        }

        return new HasilOcr(implode("\n", $baris), $data, 0.95);
    }
}
