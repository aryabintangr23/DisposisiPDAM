<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanSuratRequest;
use App\Models\LogAktivitas;
use App\Models\ScanSurat;
use App\Services\ScanSuratService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ScanSuratController extends Controller
{
    /**
     * Pindai foto/PDF surat dan kembalikan field yang berhasil dibaca.
     *
     * Berkas tetap disimpan sementara; form hanya menerima token. Token itulah
     * yang nanti dipakai SuratController::store untuk memasang lampiran.
     */
    public function store(
        ScanSuratRequest $request,
        ScanSuratService $service
    ): JsonResponse {
        abort_unless(config('scan.enabled'), 503, 'Fitur scan surat sedang dinonaktifkan.');
        abort_unless($request->user()->isStaff(), 403, 'Hanya Staff Umum yang boleh memindai surat.');

        if (function_exists('set_time_limit')) {
            @set_time_limit((int) config('scan.timeout', 60) + 15);
        }

        $arah = $request->input('arah_surat');

        try {
            $scan = $service->pindai(
                $request->file('berkas'),
                $request->user(),
                in_array($arah, ['masuk', 'keluar'], true) ? $arah : null
            );
        } catch (Throwable $e) {
            Log::error('Scan surat gagal disimpan', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Berkas scan gagal diproses. Coba lagi atau isi form secara manual.',
            ], 500);
        }

        $hasil = $scan->hasilEkstraksi();
        $berhasil = $scan->status === 'selesai';

        LogAktivitas::catat(
            'surat_discan',
            $berhasil
                ? "{$request->user()->nama} memindai surat dan menemukan ".count($hasil['fields']).' field otomatis.'
                : "{$request->user()->nama} mencoba memindai surat, tetapi OCR gagal membaca berkas.",
            'scan_surat',
            $scan->id
        );

        return response()->json([
            'id' => $scan->id,
            'token' => $scan->token,
            'status' => $scan->status,
            'berhasil' => $berhasil,
            'nama_file' => $scan->nama_file,
            'ukuran_file' => $scan->ukuran_file,
            'preview_url' => route('surat.scan.preview', $scan),
            'fields' => $hasil['fields'],
            'peringatan' => $hasil['peringatan'],
            'teks_ocr' => $scan->teks_ocr,
            'pesan' => $scan->pesan_error,
        ]);
    }

    /**
     * Streaming preview berkas scan sementara. Hanya pemilik scan yang boleh.
     */
    public function preview(Request $request, ScanSurat $scan): StreamedResponse
    {
        abort_unless(
            $scan->user_id === $request->user()->id || $request->user()->isAdmin(),
            403,
            'Anda tidak memiliki akses ke berkas scan ini.'
        );

        $disk = Storage::disk((string) config('scan.temp_disk', 'local'));

        abort_unless($disk->exists($scan->path_file), 404, 'Berkas scan sudah tidak tersedia.');

        return $disk->response(
            $scan->path_file,
            $scan->nama_file,
            ['Content-Type' => $scan->tipe_file ?: 'application/octet-stream']
        );
    }

    /**
     * Batalkan scan yang belum dipakai (mis. user memilih foto lain).
     */
    public function destroy(Request $request, ScanSurat $scan, ScanSuratService $service): JsonResponse
    {
        abort_unless(
            $scan->user_id === $request->user()->id,
            403,
            'Anda tidak memiliki akses ke berkas scan ini.'
        );

        if ($scan->dipakai_at !== null) {
            return response()->json([
                'message' => 'Scan ini sudah dipakai sebagai lampiran surat.',
            ], 422);
        }

        $service->batalkan($scan);

        return response()->json(['ok' => true]);
    }
}
