<?php

namespace App\Enums;

enum Prioritas: string
{
    case SangatSegera = 'sangat_segera';
    case Segera = 'segera';
    case Biasa = 'biasa';
    case TungguPetunjuk = 'tunggu_petunjuk';

    /**
     * Jumlah hari kalender (dikonfirmasi: kalender, bukan hari kerja).
     * Null berarti tidak ada batas waktu otomatis.
     */
    public function batasHari(): ?int
    {
        return match ($this) {
            self::SangatSegera => 3,
            self::Segera => 5,
            self::Biasa => 7,
            self::TungguPetunjuk => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SangatSegera => 'Sangat Segera',
            self::Segera => 'Segera',
            self::Biasa => 'Biasa',
            self::TungguPetunjuk => 'Tunggu Petunjuk',
        };
    }
}
