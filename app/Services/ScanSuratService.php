<?php

namespace App\Services;

use App\Models\ScanSurat;
use App\Models\User;
use App\Services\Ekstraksi\EkstraktorSurat;
use App\Services\Ocr\PembacaOcr;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Orkestrasi fitur scan surat:
 * simpan berkas sementara -> OCR lokal -> ekstraksi aturan -> hasil JSON.
 *
 * Berkas sengaja tidak langsung masuk ke lampiran. Form hanya memegang token;
 * saat surat disimpan, berkas baru dipindahkan ke disk publik. Kalau user
 * membatalkan, berkas sementara dibersihkan oleh command terjadwal.
 */
class ScanSuratService
{
    public function __construct(
        private readonly PembacaOcr $ocr,
        private readonly EkstraktorSurat $ekstraktor,
        private readonly LampiranImageService $lampiranImage,
    ) {}

    public function pindai(UploadedFile $berkas, User $user, ?string $arahTerkunci = null): ScanSurat
    {
        $token = (string) Str::uuid();
        $ekstensi = $this->ekstensi($berkas);
        $prefix = (string) config('scan.temp_prefix', 'scan-surat');
        $disk = (string) config('scan.temp_disk', 'local');

        $path = $berkas->storeAs(
            $prefix.'/'.$user->id,
            $token.'.'.$ekstensi,
            $disk
        );

        if ($path === false) {
            throw new RuntimeException('Berkas scan gagal disimpan di server.');
        }

        $scan = ScanSurat::create([
            'user_id' => $user->id,
            'token' => $token,
            'nama_file' => $berkas->getClientOriginalName() ?: 'scan.'.$ekstensi,
            'path_file' => $path,
            'tipe_file' => $berkas->getMimeType() ?: $berkas->getClientMimeType() ?: 'application/octet-stream',
            'ukuran_file' => $berkas->getSize() ?: 0,
            'status' => 'diproses',
            'expires_at' => now()->addMinutes((int) config('scan.expires_minutes', 120)),
        ]);

        try {
            $hasilOcr = $this->ocr->baca(
                Storage::disk($disk)->path($path),
                (string) $scan->tipe_file
            );

            $hasil = $this->ekstraktor->ekstrak($hasilOcr, $arahTerkunci);
            $hasil = $this->tambahkanTanggalDiterima($hasil, $arahTerkunci);

            $scan->update([
                'status' => 'selesai',
                'hasil_json' => $hasil,
                'teks_ocr' => mb_substr($hasilOcr->teks, 0, 100000),
                'pesan_error' => null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Scan surat gagal dibaca', [
                'scan_id' => $scan->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $scan->update([
                'status' => 'gagal',
                // Tanggal diterima tetap diisi walau OCR gagal: yang dicatat
                // adalah tanggal surat difoto/dipindai, bukan hasil baca teks.
                'hasil_json' => $this->tambahkanTanggalDiterima(
                    ['fields' => [], 'peringatan' => []],
                    $arahTerkunci
                ),
                'teks_ocr' => null,
                'pesan_error' => $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Surat belum bisa dibaca otomatis. Isi form secara manual atau coba foto yang lebih jelas.',
            ]);
        }

        return $scan->refresh();
    }

    /**
     * Tanggal diterima surat masuk = tanggal staff memindai/memfoto surat.
     *
     * @param  array{fields: array<string, array{value: string, confidence: float}>, peringatan: array<int, string>}  $hasil
     * @return array{fields: array<string, array{value: string, confidence: float}>, peringatan: array<int, string>}
     */
    private function tambahkanTanggalDiterima(array $hasil, ?string $arah): array
    {
        $arah = $hasil['fields']['arah_surat']['value'] ?? $arah;

        if ($arah !== 'masuk') {
            return $hasil;
        }

        $hasil['fields']['tanggal_diterima'] = [
            'value' => now()->toDateString(),
            'confidence' => 1.0,
        ];

        return $hasil;
    }

    /**
     * Cari scan milik user yang masih boleh dipakai saat menyimpan surat.
     */
    public function untukDipakai(string $token, User $user): ?ScanSurat
    {
        $scan = ScanSurat::where('token', $token)
            ->where('user_id', $user->id)
            ->first();

        if (! $scan || ! $scan->bolehDipakai()) {
            return null;
        }

        if (! Storage::disk((string) config('scan.temp_disk', 'local'))->exists($scan->path_file)) {
            return null;
        }

        return $scan;
    }

    /**
     * Salin berkas scan ke disk publik dan kembalikan data untuk record Lampiran.
     *
     * @return array{nama_file: string, path_file: string, tipe_file: string, ukuran_file: int}
     */
    public function salinKeLampiran(ScanSurat $scan): array
    {
        $diskAsal = Storage::disk((string) config('scan.temp_disk', 'local'));
        $diskTujuan = Storage::disk('public');

        $tujuan = 'lampiran/'.basename($scan->path_file);
        $stream = $diskAsal->readStream($scan->path_file);

        if ($stream === null) {
            throw new RuntimeException('Berkas hasil scan tidak ditemukan di server.');
        }

        try {
            $diskTujuan->writeStream($tujuan, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $diskTujuan->exists($tujuan)) {
            throw new RuntimeException('Lampiran hasil scan gagal disalin ke penyimpanan.');
        }

        $ekstensi = strtolower(pathinfo($tujuan, PATHINFO_EXTENSION));
        $ukuran = $this->lampiranImage->kompresJikaGambar($tujuan, $ekstensi);

        return [
            'nama_file' => $scan->nama_file,
            'path_file' => $tujuan,
            'tipe_file' => $scan->tipe_file,
            'ukuran_file' => $ukuran,
        ];
    }

    /**
     * Tandai token sudah dipakai dan hapus berkas sementara.
     */
    public function tandaiDipakai(ScanSurat $scan): void
    {
        $scan->update(['dipakai_at' => now()]);

        Storage::disk((string) config('scan.temp_disk', 'local'))->delete($scan->path_file);
    }

    /**
     * Batalkan scan yang belum dipakai (mis. user memilih foto lain).
     */
    public function batalkan(ScanSurat $scan): void
    {
        if ($scan->dipakai_at !== null) {
            return;
        }

        Storage::disk((string) config('scan.temp_disk', 'local'))->delete($scan->path_file);
        $scan->delete();
    }

    /**
     * Hapus scan kedaluwarsa beserta berkasnya.
     */
    public function bersihkanKedaluwarsa(): int
    {
        $disk = Storage::disk((string) config('scan.temp_disk', 'local'));
        $jumlah = 0;

        ScanSurat::where('expires_at', '<', now())
            ->orWhere(function ($q) {
                $q->whereNotNull('dipakai_at')
                    ->where('updated_at', '<', now()->subDay());
            })
            ->chunkById(100, function ($daftar) use ($disk, &$jumlah) {
                foreach ($daftar as $scan) {
                    $disk->delete($scan->path_file);
                    $scan->delete();
                    $jumlah++;
                }
            });

        return $jumlah;
    }

    private function ekstensi(UploadedFile $berkas): string
    {
        $ekstensi = strtolower((string) $berkas->getClientOriginalExtension());

        if (in_array($ekstensi, ['pdf', 'jpg', 'jpeg', 'png', 'heic', 'heif'], true)) {
            return $ekstensi;
        }

        $mime = strtolower((string) ($berkas->getMimeType() ?: $berkas->getClientMimeType()));

        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            default => 'jpg',
        };
    }
}
