<?php

namespace App\Enums;

enum StatusSurat: string
{
    case Baru = 'baru';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
    case PerluRevisi = 'perlu_revisi'; // hanya berlaku untuk surat keluar

    /**
     * Sesuai konfirmasi:
     * - Surat masuk: bisa diterima / ditolak
     * - Surat keluar: bisa diterima / ditolak / perlu revisi
     */
    public static function untukArah(ArahSurat $arah): array
    {
        return match ($arah) {
            ArahSurat::Masuk => [self::Baru, self::Diterima, self::Ditolak],
            ArahSurat::Keluar => [self::Baru, self::Diterima, self::Ditolak, self::PerluRevisi],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Diterima => 'Diterima',
            self::Ditolak => 'Ditolak',
            self::PerluRevisi => 'Perlu Revisi',
        };
    }
}
