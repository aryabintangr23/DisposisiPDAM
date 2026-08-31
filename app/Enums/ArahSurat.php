<?php

namespace App\Enums;

enum ArahSurat: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';

    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Surat Masuk',
            self::Keluar => 'Surat Keluar',
        };
    }
}
