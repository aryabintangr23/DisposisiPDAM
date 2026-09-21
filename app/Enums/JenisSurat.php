<?php

namespace App\Enums;

enum JenisSurat: string
{
    case Biasa = 'Surat Biasa';
    case Penawaran = 'Surat Penawaran';
    case Undangan = 'Surat Undangan';
    case Bantuan = 'Surat Bantuan';
    case LainLain = 'Surat Lain-lain';

    public function label(): string
    {
        return $this->value;
    }
}