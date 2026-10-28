<?php

namespace App\Services\Ocr;

interface PembacaOcr
{
    /**
     * Baca teks dari berkas gambar/PDF yang sudah tersimpan di server.
     *
     * @param  string  $pathAbsolut  Path absolut berkas.
     * @param  string  $mime  MIME type berkas, mis. "application/pdf".
     *
     * @throws \RuntimeException jika mesin OCR tidak tersedia atau gagal membaca.
     */
    public function baca(string $pathAbsolut, string $mime): HasilOcr;
}
