<?php

namespace App\Enums;

enum StatusDisposisi: string
{
    case Terkirim = 'terkirim';
    case Diterima = 'diterima';
    case Dibaca = 'dibaca';
    case Ditindaklanjuti = 'ditindaklanjuti';
    case Selesai = 'selesai'; // hanya boleh diset oleh Staff (dikonfirmasi)

    public function label(): string
    {
        return match ($this) {
            self::Terkirim => 'Terkirim',
            self::Diterima => 'Diterima',
            self::Dibaca => 'Dibaca',
            self::Ditindaklanjuti => 'Ditindaklanjuti',
            self::Selesai => 'Selesai',
        };
    }
}
