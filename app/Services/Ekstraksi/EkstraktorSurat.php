<?php

namespace App\Services\Ekstraksi;

use App\Services\Ocr\HasilOcr;

/**
 * Ekstraktor field surat berbasis aturan NLP.
 *
 * Pendekatan ini sengaja deterministik: pola label ("Nomor", "Tanggal",
 * "Perihal", "Kepada"), kamus institusi/bulan, dan heuristik tata letak.
 * Hasilnya bisa diuji dan diperbaiki tanpa data latih. Nanti, koreksi user
 * bisa dipakai untuk melatih classifier/CRF tanpa mengubah kontrak output.
 */
class EkstraktorSurat
{
    private const POLA_LABEL = '/^(?:nomor|no\.?|tanggal|tgl|perihal|hal|lampiran|kepada|yth|y\.?th\.?|dengan hormat|hormat kami|tanda tangan|ttd)\b/i';

    private const KATA_INSTANSI = '/\b(pdam|perumda|perusahaan daerah|dinas|badan|kantor|pemerintah|pemkot|pemkab|kementerian|universitas|sekolah|pt|cv|yayasan|rumah sakit|bank|koperasi|lembaga|instansi)\b/i';

    private const KATA_ALAMAT = '/\b(jl\.?|jalan|kel\.?|kelurahan|kec\.?|kecamatan|kode pos|telp\.?|telepon|fax|email|website|www\.|npwp|rekening)\b/i';

    /**
     * @return array{fields: array<string, array{value: string, confidence: float}>, peringatan: array<int, string>}
     */
    public function ekstrak(HasilOcr $ocr, ?string $arahTerkunci = null): array
    {
        $baris = $this->rapikanBaris($ocr);

        $fields = [];
        $peringatan = [];

        $arah = $this->deteksiArah($baris, $ocr->teks, $arahTerkunci);
        $arahNilai = $arah['value'] ?? null;

        $nomor = $this->ekstrakNomor($baris);
        if ($nomor !== null) {
            $fields['nomor_surat'] = $nomor;
        } else {
            $peringatan[] = 'Nomor surat tidak terbaca otomatis.';
        }

        // Nomor agenda adalah nomor internal surat masuk. Untuk surat keluar
        // field ini tidak relevan, jadi jangan diusulkan.
        $nomorAgenda = $this->ekstrakNomorAgenda($baris);
        if ($nomorAgenda !== null && $arahNilai !== 'keluar') {
            $fields['nomor_agenda'] = $nomorAgenda;
        }

        $tanggal = $this->ekstrakTanggal($baris);
        if ($tanggal !== null) {
            $fields['tanggal_surat'] = $tanggal;
        } else {
            $peringatan[] = 'Tanggal surat tidak terbaca otomatis.';
        }

        $perihal = $this->ekstrakPerihal($baris);
        if ($perihal !== null) {
            $fields['perihal'] = $perihal;
        } else {
            $peringatan[] = 'Perihal tidak terbaca otomatis.';
        }

        $suratDari = $this->ekstrakSuratDari($baris);

        if ($suratDari !== null && $arahNilai !== 'keluar') {
            $fields['surat_dari'] = $suratDari;
        } elseif ($arahNilai !== 'keluar') {
            $peringatan[] = 'Asal surat tidak terbaca otomatis.';
        }

        $tujuan = $this->ekstrakTujuan($baris);
        if ($tujuan !== null && $arahNilai !== 'masuk') {
            $fields['tujuan_surat'] = $tujuan;
        } elseif ($arahNilai !== 'masuk') {
            $peringatan[] = 'Tujuan surat tidak terbaca otomatis.';
        }

        $jenis = $this->ekstrakJenis($baris, $ocr->teks, $perihal['value'] ?? null);
        if ($jenis !== null) {
            $fields['jenis_surat'] = $jenis;
        }

        if ($arahNilai !== null) {
            $fields['arah_surat'] = $arah;
        } else {
            $peringatan[] = 'Arah surat tidak bisa dipastikan; silakan pilih manual.';
        }

        if ($ocr->kepercayaan > 0 && $ocr->kepercayaan < 0.5) {
            $peringatan[] = 'Kualitas foto/scan rendah. Periksa kembali semua isian hasil scan.';
        }

        if ($fields === []) {
            $peringatan[] = 'Tidak ada field yang berhasil dibaca. Isi form secara manual.';
        }

        return [
            'fields' => $fields,
            'peringatan' => array_values(array_unique($peringatan)),
        ];
    }

    /**
     * @return array<int, array{teks: string, confidence: float, top: int, bottom: int}>
     */
    private function rapikanBaris(HasilOcr $ocr): array
    {
        $baris = $ocr->baris;

        if ($baris === []) {
            foreach (preg_split('/\R/u', $ocr->teks) ?: [] as $teks) {
                $baris[] = ['teks' => $teks, 'confidence' => $ocr->kepercayaan, 'top' => 0, 'bottom' => 0];
            }
        }

        $hasil = [];

        foreach ($baris as $item) {
            $teks = $this->rapikanTeks((string) ($item['teks'] ?? ''));

            if ($teks === '') {
                continue;
            }

            $hasil[] = [
                'teks' => $teks,
                'confidence' => max(0.0, min(1.0, (float) ($item['confidence'] ?? 0.5))),
                'top' => (int) ($item['top'] ?? 0),
                'bottom' => (int) ($item['bottom'] ?? 0),
            ];
        }

        return $hasil;
    }

    private function rapikanTeks(string $teks): string
    {
        $teks = str_replace(["\u{00A0}", "\u{200B}", "\t"], ' ', $teks);
        $teks = str_replace(['–', '—', '−'], '-', $teks);
        $teks = preg_replace('/\s+/u', ' ', $teks) ?? $teks;

        return trim($teks);
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function ekstrakNomor(array $baris): ?array
    {
        $kandidat = [];

        foreach ($baris as $i => $item) {
            $teks = $item['teks'];

            if (! preg_match('/\b(?:nomor|no\.?)\b/i', $teks)) {
                continue;
            }

            if (preg_match('/\b(?:agenda|urut|telepon|telp|hp|rekening|nik|npwp)\b/i', $teks)) {
                continue;
            }

            // "Jl. ... No. 12" adalah alamat, bukan nomor surat.
            if (preg_match(self::KATA_ALAMAT, $teks)) {
                continue;
            }

            // Label "Nomor Surat" di bagian atas (lembar pengantar) lebih
            // dipercaya daripada referensi nomor surat di badan surat.
            $eksplisitSurat = $i < 20
                && (bool) preg_match('/\b(?:nomor\s+surat|no\.?\s*surat)\b/i', $teks);
            $labelKuat = $eksplisitSurat
                || (bool) preg_match('/^(?:[\d\.\)\-\s]*)(?:nomor|no\.?)\s*[:\-]/i', $teks);

            $nilai = $this->nilaiSetelahLabel($teks, '/\b(?:nomor|no\.?)\b\s*(?:surat)?\s*[:\-]?\s*/i');

            if ($nilai === '') {
                $nilai = $this->barisBerikutnya($baris, $i, 1);
            }

            $nilai = $this->bersihkanNomor($nilai);

            if (! $this->nomorValid($nilai)) {
                continue;
            }

            // Pada lembar pengantar surat masuk biasanya ada "Nomor" internal
            // dan "Nomor Surat" asli. Prioritaskan label yang eksplisit.
            $kandidat[] = [
                'value' => $nilai,
                'confidence' => $this->gabungConfidence($item['confidence'], $labelKuat ? 1.0 : 0.82),
                'skor' => ($labelKuat ? 3 : 1) + ($eksplisitSurat ? 4 : 0) + (preg_match('/[\/A-Za-z]/', $nilai) ? 2 : 0),
            ];
        }

        if ($kandidat === []) {
            return null;
        }

        usort($kandidat, fn ($a, $b) => $b['skor'] <=> $a['skor']);

        return [
            'value' => $kandidat[0]['value'],
            'confidence' => round($kandidat[0]['confidence'], 2),
        ];
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function ekstrakNomorAgenda(array $baris): ?array
    {
        $kandidat = [];

        foreach ($baris as $i => $item) {
            $teks = $item['teks'];

            if (! preg_match('/\b(?:nomor|no\.?)\s+agenda\b/i', $teks)) {
                continue;
            }

            $labelKuat = (bool) preg_match('/^(?:[\d\.\)\-\s]*)(?:nomor|no\.?)\s+agenda\b\s*[:\-]/i', $teks);

            $nilai = $this->nilaiSetelahLabel($teks, '/\b(?:nomor|no\.?)\s+agenda\b\s*[:\-]?\s*/i');

            if ($nilai === '') {
                $nilai = $this->barisBerikutnya($baris, $i, 1);
            }

            $nilai = $this->bersihkanNomor($nilai);

            if (! $this->nomorValid($nilai)) {
                continue;
            }

            $kandidat[] = [
                'value' => $nilai,
                'confidence' => $this->gabungConfidence($item['confidence'], $labelKuat ? 1.0 : 0.85),
                'skor' => ($labelKuat ? 3 : 1) + (preg_match('/[\/A-Za-z]/', $nilai) ? 2 : 0),
            ];
        }

        if ($kandidat === []) {
            return null;
        }

        usort($kandidat, fn ($a, $b) => $b['skor'] <=> $a['skor']);

        return [
            'value' => $kandidat[0]['value'],
            'confidence' => round($kandidat[0]['confidence'], 2),
        ];
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function ekstrakTanggal(array $baris): ?array
    {
        $kandidat = [];

        foreach ($baris as $i => $item) {
            $teks = $item['teks'];

            if (! preg_match('/\b(?:tanggal|tgl)\b/i', $teks)) {
                continue;
            }

            if (preg_match('/\b(?:diterima|terima|ttd|tanda tangan|lahir)\b/i', $teks)) {
                continue;
            }

            $labelKuat = (bool) preg_match('/\b(?:tanggal\s+surat|tgl\.?\s+surat)\b/i', $teks)
                || (bool) preg_match('/^(?:[\d\.\)\-\s]*)(?:tanggal|tgl\.?)\s*[:\-]/i', $teks);

            $nilai = $this->nilaiSetelahLabel($teks, '/\b(?:tanggal|tgl\.?)\b\s*(?:surat)?\s*[:\-]?\s*/i');

            if ($nilai === '') {
                $nilai = $this->barisBerikutnya($baris, $i, 1);
            }

            $tanggal = ParserTanggalIndonesia::parse($nilai);

            if ($tanggal === null) {
                continue;
            }

            $kandidat[] = [
                'value' => $tanggal,
                'confidence' => $this->gabungConfidence($item['confidence'], $labelKuat ? 1.0 : 0.9),
                'skor' => $labelKuat ? 2 : 1,
            ];
        }

        // Kalau tidak ada label "Tanggal", cari tanggal di bagian atas surat.
        if ($kandidat === []) {
            foreach (array_slice($baris, 0, 12) as $item) {
                $tanggal = ParserTanggalIndonesia::parse($item['teks']);

                if ($tanggal !== null) {
                    return [
                        'value' => $tanggal,
                        'confidence' => round($this->gabungConfidence($item['confidence'], 0.6), 2),
                    ];
                }
            }

            return null;
        }

        usort($kandidat, fn ($a, $b) => $b['skor'] <=> $a['skor']);

        return [
            'value' => $kandidat[0]['value'],
            'confidence' => round($kandidat[0]['confidence'], 2),
        ];
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function ekstrakPerihal(array $baris): ?array
    {
        foreach ($baris as $i => $item) {
            $teks = $item['teks'];

            if (! preg_match('/^(?:[\d\.\)\-\s]*)(?:perihal|hal)\b\s*[:\-]?\s*(.*)$/i', $teks, $m)) {
                continue;
            }

            $nilai = $this->bersihkanNilai((string) $m[1]);
            $conf = $item['confidence'];

            if ($nilai === '') {
                $nilai = $this->kumpulkanBaris($baris, $i, 3, true);
                $conf = $this->gabungConfidence($conf, 0.9);
            } else {
                $conf = $this->gabungConfidence($conf, 1.0);
            }

            $nilai = $this->potongDiLabel($nilai);

            if (mb_strlen($nilai) < 3) {
                continue;
            }

            return [
                'value' => $nilai,
                'confidence' => round($conf, 2),
            ];
        }

        return null;
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function ekstrakSuratDari(array $baris): ?array
    {
        // Lembar pengantar surat masuk biasanya punya label "Dari :" yang
        // lebih akurat daripada kop surat di bagian paling atas.
        foreach (array_slice($baris, 0, 20, true) as $i => $item) {
            if (! preg_match('/^(?:[\d\.\)\-\s]*)(?:dari|asal)\s*[:\-]\s*(.*)$/i', $item['teks'], $m)) {
                continue;
            }

            $nilai = $this->bersihkanNilai((string) $m[1]);

            if ($nilai === '') {
                $nilai = $this->barisBerikutnya($baris, $i, 2);
            }

            $nilai = $this->bersihkanNilai($nilai);

            if (mb_strlen($nilai) < 3) {
                continue;
            }

            return [
                'value' => $nilai,
                'confidence' => round($this->gabungConfidence($item['confidence'], 0.95), 2),
            ];
        }

        $awal = null;

        foreach (array_slice($baris, 0, 8, true) as $i => $item) {
            if ($this->skorInstansi($item['teks']) >= 3) {
                $awal = $i;
                break;
            }
        }

        if ($awal === null) {
            // Cadangan: baris dengan skor lebih rendah tetapi masih masuk akal.
            $skorTerbaik = -1;

            foreach (array_slice($baris, 0, 8, true) as $i => $item) {
                $skor = $this->skorInstansi($item['teks']);

                if ($skor > $skorTerbaik) {
                    $skorTerbaik = $skor;
                    $awal = $i;
                }
            }

            if ($awal === null || $skorTerbaik < 2) {
                return null;
            }
        }

        $bagian = [];
        $confidence = [];

        for ($i = $awal; $i < count($baris) && count($bagian) < 3; $i++) {
            $teks = $baris[$i]['teks'];

            if (preg_match(self::KATA_ALAMAT, $teks)) {
                break;
            }

            if ($i > $awal && preg_match(self::POLA_LABEL, $teks)) {
                break;
            }

            $skor = $this->skorInstansi($teks);

            // Baris lanjutan nama instansi biasanya huruf kapital dan tanpa
            // kata kunci lengkap, mis. "TIRTA GEMILANG" setelah "PERUMDA ...".
            if ($i > $awal && $skor < 1 && ! $this->mayoritasKapital($teks)) {
                break;
            }

            $bagian[] = $teks;
            $confidence[] = $baris[$i]['confidence'];
        }

        $nilai = $this->bersihkanNilai(implode(' ', $bagian));

        if (mb_strlen($nilai) < 3) {
            return null;
        }

        return [
            'value' => $nilai,
            'confidence' => round($this->gabungConfidence(
                $confidence === [] ? 0.5 : array_sum($confidence) / count($confidence),
                $this->skorInstansi($nilai) >= 3 ? 0.95 : 0.65
            ), 2),
        ];
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function ekstrakTujuan(array $baris): ?array
    {
        if ($baris === []) {
            return null;
        }

        $maksPindai = min(count($baris), 40);
        $batasBody = null;

        // Target surat berada sebelum paragraf pembuka. Batas ini mencegah
        // kata "kepada" di badan surat ("... diajukan kepada kami ...")
        // terbaca sebagai tujuan.
        for ($i = 0; $i < $maksPindai; $i++) {
            if (preg_match('/^(?:dengan hormat|sehubungan|bersama ini|menindaklanjuti|dalam rangka|berdasarkan|bahwa)\b/i', $baris[$i]['teks'])) {
                $batasBody = $i;
                break;
            }
        }

        $kandidat = null;

        // "Yth." bisa berada di tengah baris karena tata letak dua kolom,
        // mis. "Lampiran :- Yth. Wakil Dekan ...".
        for ($i = 0; $i < $maksPindai; $i++) {
            $teks = $baris[$i]['teks'];

            if (! preg_match('/\byth\b\.?\s*[:\-,]?\s*(.*)$/iu', $teks, $m)) {
                continue;
            }

            $kandidat = [
                'index' => $i,
                'awal' => $this->bersihkanNilaiTujuan((string) $m[1]),
                'confidence' => $this->gabungConfidence($baris[$i]['confidence'], 0.95),
            ];
            break;
        }

        // Cadangan untuk surat yang hanya menulis "Kepada" tanpa "Yth.".
        if ($kandidat === null) {
            $batasKepada = $batasBody ?? $maksPindai;

            for ($i = 0; $i < $batasKepada; $i++) {
                $teks = $this->bersihkanNilai($baris[$i]['teks']);

                if (! preg_match('/\bkepada\b\s*[:\-,]?\s*(.*)$/iu', $teks, $m)) {
                    continue;
                }

                $labelKuat = (bool) preg_match('/^(?:[\d\.\)\-\s]*)(?:kepada)\b/i', $teks)
                    || (bool) preg_match('/\b(?:nomor|no\.?|lampiran|perihal|hal|tanggal|tgl)\b.*\bkepada\b/i', $teks)
                    || (bool) preg_match('/\bkepada\s*[:\-]?\s*$/i', $teks);

                if (! $labelKuat) {
                    continue;
                }

                $awal = $this->bersihkanNilaiTujuan((string) $m[1]);

                if (preg_match('/^(?:kami|saya|yang|mahasiswa|pihak)\b/i', $awal)) {
                    continue;
                }

                $kandidat = [
                    'index' => $i,
                    'awal' => $awal,
                    'confidence' => $this->gabungConfidence($baris[$i]['confidence'], 0.9),
                ];
                break;
            }
        }

        if ($kandidat === null) {
            return null;
        }

        $batas = $batasBody !== null && $batasBody > $kandidat['index']
            ? $batasBody
            : min(count($baris), $kandidat['index'] + 8);

        $nilai = $this->kumpulkanTujuan($baris, $kandidat['index'], $kandidat['awal'], $batas);

        if (mb_strlen($nilai) < 3) {
            return null;
        }

        return [
            'value' => $nilai,
            'confidence' => round($kandidat['confidence'], 2),
        ];
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     */
    private function kumpulkanTujuan(array $baris, int $index, string $awal, int $batas): string
    {
        $bagian = $awal === '' ? [] : [$awal];
        $labelDilewati = 0;

        for ($i = $index + 1; $i < $batas && $i < count($baris) && count($bagian) < 5; $i++) {
            $teks = $this->bersihkanNilai($baris[$i]['teks']);

            if ($teks === '') {
                continue;
            }

            if ($this->barisAlamatTujuan($teks)) {
                break;
            }

            if (preg_match('/^(?:dengan hormat|sehubungan|bersama ini|menindaklanjuti|dalam rangka|berdasarkan|bahwa)\b/i', $teks)) {
                break;
            }

            if (preg_match(self::POLA_LABEL, $teks)) {
                // Kolom kiri (Nomor/Lampiran/Perihal) sering terbaca di antara
                // baris tujuan pada surat dua kolom. Lewati, lalu lanjutkan.
                if (preg_match('/^(?:[\d\.\)\-\s]*)(?:nomor|no\.?|lampiran|perihal|hal|tanggal|tgl)\b/i', $teks) && $labelDilewati < 2) {
                    $labelDilewati++;

                    continue;
                }

                break;
            }

            $bagian[] = $teks;
        }

        return $this->bersihkanNilaiTujuan(implode(' ', $bagian));
    }

    private function barisAlamatTujuan(string $teks): bool
    {
        if (preg_match('/^(?:di\b|di-|jl\.?|jalan|kel\.?|kelurahan|kec\.?|kecamatan|kota|kabupaten|provinsi|kode pos|telp\.?|telepon|fax|email|website|www\.)\b/i', $teks)) {
            return true;
        }

        if (preg_match(self::KATA_ALAMAT, $teks)) {
            return true;
        }

        return (bool) preg_match('/^(?:hormat kami|ttd|tanda tangan|salam|terima kasih)\b/i', $teks);
    }

    private function bersihkanNilaiTujuan(string $nilai): string
    {
        $nilai = $this->bersihkanNilai($nilai);
        $nilai = preg_replace('/^(?:kepada|yth\.?|y\.?th\.?)\b[\s,.\-:]*/iu', '', $nilai) ?? $nilai;
        $nilai = preg_replace('/^[\s:;,\-–—.•·*]+/u', '', $nilai) ?? $nilai;
        $nilai = preg_replace('/\s+di\s+(?:tempat|-)\.?$/iu', '', $nilai) ?? $nilai;

        return $this->bersihkanNilai($nilai);
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function ekstrakJenis(array $baris, string $teksPenuh, ?string $perihal): ?array
    {
        $perihalLower = mb_strtolower($perihal ?? '');
        $awalLower = mb_strtolower(implode(' ', array_slice(array_column($baris, 'teks'), 0, 15)));
        $penuhLower = mb_strtolower($teksPenuh);

        $kandidat = [
            'Surat Undangan' => ['undangan', 'mengharap kehadiran', 'kehadiran', 'acara'],
            'Surat Penawaran' => ['penawaran', 'penawaran harga', 'quotation', 'harga penawaran'],
            'Surat Bantuan' => ['bantuan', 'hibah', 'sumbangan', 'donasi'],
            'Surat Pemberitahuan' => ['pemberitahuan', 'diberitahukan', 'menginformasikan'],
            'Surat Permohonan' => ['permohonan', 'memohon', 'mohon'],
            'Surat Laporan' => ['laporan'],
            'Surat Edaran' => ['edaran'],
            'Surat Tugas' => ['surat tugas', 'menugaskan'],
            'Surat Keterangan' => ['keterangan'],
        ];

        $terbaik = null;
        $skorTerbaik = 0;
        $dariPerihal = false;

        foreach ($kandidat as $jenis => $kataKunci) {
            $skor = 0;
            $kenaPerihal = false;

            foreach ($kataKunci as $kunci) {
                if ($perihalLower !== '' && str_contains($perihalLower, $kunci)) {
                    $skor += 3;
                    $kenaPerihal = true;
                }

                if (str_contains($awalLower, $kunci)) {
                    $skor += 2;
                }

                if (str_contains($penuhLower, $kunci)) {
                    $skor += 1;
                }
            }

            if ($skor > $skorTerbaik) {
                $skorTerbaik = $skor;
                $terbaik = $jenis;
                $dariPerihal = $kenaPerihal;
            }
        }

        if ($terbaik === null) {
            return [
                'value' => 'Surat Biasa',
                'confidence' => 0.4,
            ];
        }

        return [
            'value' => $terbaik,
            'confidence' => $dariPerihal ? 0.85 : 0.65,
        ];
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     * @return array{value: string, confidence: float}|null
     */
    private function deteksiArah(array $baris, string $teksPenuh, ?string $arahTerkunci): ?array
    {
        if (in_array($arahTerkunci, ['masuk', 'keluar'], true)) {
            return ['value' => $arahTerkunci, 'confidence' => 1.0];
        }

        $atasBaris = [];

        foreach ($baris as $item) {
            // Kop surat berhenti sebelum blok "Kepada/Yth." — kalau tidak,
            // nama PDAM di baris tujuan ikut terbaca sebagai kop.
            if (preg_match('/^(?:[\d\.\)\-\s]*)(?:kepada|yth\.?|y\.?th\.?)\b/i', $item['teks'])) {
                break;
            }

            $atasBaris[] = $item['teks'];

            if (count($atasBaris) >= 8) {
                break;
            }
        }

        $atas = implode(' ', $atasBaris);
        $atasLower = mb_strtolower($atas);
        $penuhLower = mb_strtolower($teksPenuh);

        $pdamDiAtas = (bool) preg_match('/\b(pdam|perumda|perusahaan daerah air minum|tirta)\b/i', $atas);
        $pdamDiTujuan = (bool) preg_match('/(?:kepada|yth)[^\n]{0,140}\b(pdam|perumda|tirta)\b/i', $teksPenuh);

        if ($pdamDiTujuan && ! $pdamDiAtas) {
            return ['value' => 'masuk', 'confidence' => 0.8];
        }

        if ($pdamDiAtas && ! $pdamDiTujuan) {
            return ['value' => 'keluar', 'confidence' => 0.8];
        }

        if ($pdamDiAtas && $pdamDiTujuan) {
            $posKepada = mb_stripos($penuhLower, 'kepada');
            $posYth = mb_stripos($penuhLower, 'yth');
            $posTujuan = min(
                $posKepada === false ? PHP_INT_MAX : $posKepada,
                $posYth === false ? PHP_INT_MAX : $posYth
            );

            $posPdam = PHP_INT_MAX;

            foreach (['pdam', 'perumda', 'tirta'] as $kata) {
                $pos = mb_stripos($penuhLower, $kata);

                if ($pos !== false) {
                    $posPdam = min($posPdam, $pos);
                }
            }

            if ($posPdam !== PHP_INT_MAX && $posTujuan !== PHP_INT_MAX && $posTujuan < $posPdam) {
                return ['value' => 'masuk', 'confidence' => 0.65];
            }

            return ['value' => 'keluar', 'confidence' => 0.65];
        }

        // Kop bukan PDAM, tetapi ada instansi lain di bagian atas: biasanya
        // surat dari instansi eksternal yang masuk ke PDAM.
        if (preg_match(self::KATA_INSTANSI, $atasLower)) {
            return ['value' => 'masuk', 'confidence' => 0.55];
        }

        return null;
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     */
    private function barisBerikutnya(array $baris, int $index, int $maks): string
    {
        $hasil = [];

        for ($i = $index + 1; $i < count($baris) && count($hasil) < $maks; $i++) {
            $teks = $baris[$i]['teks'];

            if (preg_match(self::POLA_LABEL, $teks)) {
                break;
            }

            $hasil[] = $teks;
        }

        return implode(' ', $hasil);
    }

    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     */
    private function kumpulkanBaris(array $baris, int $index, int $maks, bool $hentikanDiLabel = true): string
    {
        $hasil = [];

        for ($i = $index + 1; $i < count($baris) && count($hasil) < $maks; $i++) {
            $teks = $baris[$i]['teks'];

            if ($hentikanDiLabel && preg_match(self::POLA_LABEL, $teks)) {
                break;
            }

            if (preg_match(self::KATA_ALAMAT, $teks)) {
                break;
            }

            $hasil[] = $teks;
        }

        return implode(' ', $hasil);
    }

    private function nilaiSetelahLabel(string $teks, string $pola): string
    {
        $hasil = preg_replace($pola, '', $teks, 1);

        return $this->bersihkanNilai((string) $hasil);
    }

    private function bersihkanNilai(string $nilai): string
    {
        $nilai = preg_replace('/\s+/u', ' ', $nilai) ?? $nilai;
        $nilai = preg_replace('/^[\s:;,\-–—]+/u', '', $nilai) ?? $nilai;
        $nilai = preg_replace('/[\s:;,\-–—]+$/u', '', $nilai) ?? $nilai;

        return trim($nilai);
    }

    private function bersihkanNomor(string $nilai): string
    {
        $nilai = $this->bersihkanNilai($nilai);
        $nilai = preg_replace('/\s+(?:tanggal|tgl\.?|perihal|hal|lampiran|kepada|yth)\b.*$/iu', '', $nilai) ?? $nilai;
        $nilai = preg_replace('/^surat\s+/iu', '', $nilai) ?? $nilai;
        $nilai = $this->bersihkanNilai($nilai);

        if (mb_strlen($nilai) > 120) {
            $nilai = mb_substr($nilai, 0, 120);
        }

        return $nilai;
    }

    private function potongDiLabel(string $nilai): string
    {
        $nilai = preg_replace('/\s+(?:kepada|yth\.?|y\.?th\.?|lampiran|dengan hormat|hormat kami)\b.*$/iu', '', $nilai) ?? $nilai;

        return $this->bersihkanNilai($nilai);
    }

    private function nomorValid(string $nilai): bool
    {
        if (mb_strlen($nilai) < 3 || mb_strlen($nilai) > 120) {
            return false;
        }

        if (! preg_match('/\d/', $nilai)) {
            return false;
        }

        // Jangan mengambil tanggal atau nomor telepon sebagai nomor surat.
        if (preg_match('/^\d{1,2}[-\/.]\d{1,2}[-\/.]\d{2,4}$/', $nilai)) {
            return false;
        }

        if (preg_match('/^\+?\d[\d\s\-()]{8,}$/', $nilai) && ! preg_match('/[A-Za-z\/]/', $nilai)) {
            return false;
        }

        return true;
    }

    private function skorInstansi(string $teks): int
    {
        $skor = 0;

        if (preg_match(self::KATA_INSTANSI, $teks)) {
            $skor += 3;
        }

        if ($this->mayoritasKapital($teks)) {
            $skor += 1;
        }

        if (mb_strlen($teks) >= 5 && mb_strlen($teks) <= 120) {
            $skor += 1;
        }

        if (preg_match(self::KATA_ALAMAT, $teks)) {
            $skor -= 4;
        }

        if (preg_match('/\b(?:nomor|tanggal|perihal|hal|kepada|yth|lampiran)\b/i', $teks)) {
            $skor -= 3;
        }

        return $skor;
    }

    private function mayoritasKapital(string $teks): bool
    {
        $huruf = preg_replace('/[^A-Za-z]/u', '', $teks) ?? '';

        if (mb_strlen($huruf) < 4) {
            return false;
        }

        $kapital = preg_replace('/[^A-Z]/u', '', $huruf) ?? '';

        return mb_strlen($kapital) / mb_strlen($huruf) >= 0.7;
    }

    private function gabungConfidence(float $ocr, float $pola): float
    {
        $ocr = $ocr > 0 ? $ocr : 0.5;

        return max(0.0, min(1.0, ($ocr * 0.55) + ($pola * 0.45)));
    }
}
