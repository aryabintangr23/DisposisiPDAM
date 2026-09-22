<?php

namespace App\Http\Controllers;

use App\Models\Lampiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LampiranController extends Controller
{
    /**
     * Tampilkan/streaming berkas lampiran langsung dari disk penyimpanan.
     *
     * Sengaja TIDAK memakai Storage::url()/asset('storage/...') yang bergantung
     * pada symlink "public/storage" -> "storage/app/public". Di sebagian
     * lingkungan hosting, symlink ini bisa putus, tertimpa folder biasa saat
     * deploy, atau memang tidak didukung sama sekali — akibatnya berkas yang
     * baru diunggah (misalnya JPG hasil foto/scan) tersimpan dengan benar di
     * disk tapi tidak pernah muncul lewat URL publik. Endpoint ini membaca
     * berkas langsung dari disk 'public' lewat Storage, sehingga selalu
     * bekerja terlepas dari kondisi symlink tersebut.
     */
    public function tampil(Request $request, Lampiran $lampiran): StreamedResponse
    {
        $surat = $lampiran->surat;

        abort_unless(
            $surat && $surat->bisaDiaksesOleh($request->user()),
            403,
            'Anda tidak memiliki akses ke lampiran ini.'
        );

        abort_unless(
            Storage::disk('public')->exists($lampiran->path_file),
            404,
            'Berkas lampiran tidak ditemukan di server.'
        );

        return Storage::disk('public')->response(
            $lampiran->path_file,
            $lampiran->nama_file,
            [
                'Content-Type' => $lampiran->tipe_file
                    ?: (Storage::disk('public')->mimeType($lampiran->path_file) ?: 'application/octet-stream'),
            ]
        );
    }
}
