<?php

namespace App\Services;

use App\Enums\Prioritas;
use App\Models\User;
use Carbon\Carbon;

class DisposisiRuleService
{
    /**
     * Matriks alur disposisi yang diizinkan (role pengirim => role penerima)
     */
    private const ALUR_SAH = [
        'staff_umum' => ['kabag_umum'],
        'kabag_umum' => ['staff_umum', 'direktur'],
        'direktur'   => ['kabag_umum'],
    ];

    /**
     * Cek apakah pengirim boleh mengirim disposisi ke penerima.
     */
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
     * Hitung batas waktu disposisi berdasarkan hari kalender.
     */
    public function hitungBatasWaktu(Carbon $tanggalDisposisi, Prioritas $prioritas): ?Carbon
    {
        $hari = $prioritas->batasHari();

        return $hari === null ? null : $tanggalDisposisi->copy()->addDays($hari);
    }

    /**
     * Cek apakah user berhak menyelesaikan disposisi (khusus Staff).
     */
    public function bolehMenyelesaikan(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Cek hak akses penetapan keputusan surat (Direktur: Terima/Tolak, Kabag: Perlu Revisi).
     */
    public function bolehSetKeputusan(User $user, User $penerima, string $keputusan): bool
    {
        if ($user->isDirektur()) {
            return in_array($keputusan, ['diterima', 'ditolak'], true);
        }

        if ($user->isKabag()) {
            return $keputusan === 'perlu_revisi';
        }

        return false;
    }
}
