<?php

namespace App\Enums;

enum StatusDisposisi: string
{
    case Terkirim = 'terkirim';
    case Diterima = 'diterima';
    case Dibaca = 'dibaca';
    case Ditindaklanjuti = 'ditindaklanjuti';
    case Revisi = 'revisi'; // penerima (Kasubag/Kabag) meminta revisi atas langkah ini
    case Selesai = 'selesai'; // seluruh alur final setelah keputusan Direktur

    /**
     * Status yang menandakan langkah disposisi sudah ditanggapi penerimanya
     * (diterima / revisi / selesai) sehingga tidak lagi dihitung terlambat.
     */
    public function sudahDitanggapi(): bool
    {
        return in_array($this, [self::Diterima, self::Revisi, self::Selesai], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Terkirim => 'Terkirim',
            self::Diterima => 'Diterima',
            self::Dibaca => 'Dibaca',
            self::Ditindaklanjuti => 'Ditindaklanjuti',
            self::Revisi => 'Revisi',
            self::Selesai => 'Selesai',
        };
    }
}