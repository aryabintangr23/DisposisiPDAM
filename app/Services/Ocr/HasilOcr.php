<?php

namespace App\Services\Ocr;

/**
 * Hasil pembacaan OCR: teks utuh, baris-baris beserta confidence, dan
 * rata-rata confidence seluruh kata.
 *
 * Baris menyimpan posisi vertikal (top/bottom) agar ekstraktor bisa memakai
 * heuristik tata letak, misalnya kop surat di bagian atas atau blok
 * "Kepada Yth." di bagian tengah.
 */
class HasilOcr
{
    /**
     * @param  array<int, array{teks: string, confidence: float, top: int, bottom: int}>  $baris
     */
    public function __construct(
        public readonly string $teks,
        public readonly array $baris = [],
        public readonly float $kepercayaan = 0.0,
    ) {}

    public function kosong(): bool
    {
        return trim($this->teks) === '';
    }
}
