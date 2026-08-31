<?php

namespace App\Services;

use App\Enums\Prioritas;
use App\Models\User;
use Carbon\Carbon;

/**
 * Pusat business rule untuk disposisi, supaya aturan arah yang sah dan
 * perhitungan batas waktu tidak tercecer/duplikat di banyak controller.
 */
class DisposisiRuleService
{
    /**
     * Matriks arah disposisi yang sah (role pengirim => daftar role penerima yang boleh dituju).
     * Sesuai requirement, hanya 4 arah ini yang diizinkan:
     * Staff -> Kabag, Kabag -> Staff, Kabag -> Direktur, Direktur -> Kabag.
     */
    private const ALUR_SAH = [
        'staff_umum' => ['kabag_umum'],
        'kabag_umum' => ['staff_umum', 'direktur'],
        'direktur'   => ['kabag_umum'],
    ];

    public function bolehDisposisi(User $pengirim, User $penerima): bool
    {
        $roleKirim = $pengirim->role?->nama_role;
        $roleTerima = $penerima->role?->nama_role;

        if (! $roleKirim || ! $roleTerima) {
            return false;
        }

        return in_array($roleTerima, self::ALUR_SAH[$roleKirim] ?? [], true);
    }

    /**
     * Hitung batas waktu dari tanggal disposisi + prioritas.
     * Dikonfirmasi: menggunakan hari kalender (bukan hari kerja).
     * Mengembalikan null untuk prioritas "tunggu_petunjuk".
     */
    public function hitungBatasWaktu(Carbon $tanggalDisposisi, Prioritas $prioritas): ?Carbon
    {
        $hari = $prioritas->batasHari();

        return $hari === null ? null : $tanggalDisposisi->copy()->addDays($hari);
    }

    /**
     * Hanya Staff yang boleh menandai disposisi sebagai "selesai" (dikonfirmasi).
     */
    public function bolehMenyelesaikan(User $user): bool
    {
        return $user->isStaff();
    }
}
