<?php

namespace App\Services;

use App\Enums\Prioritas;
use App\Enums\StatusSurat;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Models\User;
use Carbon\Carbon;

class DisposisiRuleService
{
    /**
     * Matriks alur disposisi yang diizinkan (role pengirim => role penerima).
     * Alur surat masuk: Staff -> Kasubag -> Kabag -> Direktur; Direktur memutuskan
     * dan mengarahkan disposisi ke jabatan (label, bukan akun).
     */
    private const ALUR_SAH = [
        'staff_umum' => ['kasubag_umum'],
        'kasubag_umum' => ['staff_umum', 'kabag_umum'],
        'kabag_umum' => ['staff_umum', 'kasubag_umum', 'direktur'],
        'direktur' => [],
    ];

    /**
     * Daftar tetap jabatan tujuan disposisi keputusan Direktur (ditampilkan sebagai
     * dropdown; disimpan sebagai label, tidak memerlukan akun pengguna). Jabatan
     * "Kepala Unit" dan "Kasubag" bersifat umum/universal sehingga wajib ditambah
     * keterangan "bagian" (lihat JABATAN_PERLU_BAGIAN).
     */
    public const TUJUAN_JABATAN = [
        'Kabag Keuangan',
        'Kabag Umum & Administrasi',
        'Kabag Teknik',
        'Kepala SPI',
        'Staf Ahli',
        'Kepala Unit',
        'Kasubag',
    ];

    /**
     * Jabatan yang wajib didampingi keterangan "bagian".
     */
    public const JABATAN_PERLU_BAGIAN = [
        'Kepala Unit',
        'Kasubag',
    ];

    /**
     * Nilai sentinel pilihan "Lainnya" pada dropdown jabatan tujuan keputusan
     * Direktur. Saat dipilih, Direktur mengetik jabatan manual di
     * `tujuan_jabatan_lain`.
     */
    public const JABATAN_LAINNYA = '__lainnya__';

    /**
     * Cek apakah sebuah jabatan tujuan wajib diisi keterangan "bagian".
     * Perbandingan tidak peka huruf besar dan mengabaikan spasi di tepinya.
     */
    public static function jabatanPerluBagian(string $jabatan): bool
    {
        $normal = mb_strtolower(trim($jabatan));

        return in_array($normal, array_map('mb_strtolower', self::JABATAN_PERLU_BAGIAN), true);
    }

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
     * Role penerus berikutnya pada alur surat masuk.
     */
    public function roleBerikutnya(string $role): ?string
    {
        return match ($role) {
            'kasubag_umum' => 'kabag_umum',
            'kabag_umum' => 'direktur',
            default => null,
        };
    }

    /**
     * Ambil akun (user) pertama dari sebuah role.
     */
    public function akunDenganRole(string $namaRole): ?User
    {
        return User::whereHas('role', fn ($q) => $q->where('nama_role', $namaRole))->first();
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
     * Cek hak akses penetapan keputusan surat
     * (Direktur: Terima/Tolak, Kasubag & Kabag: Perlu Revisi).
     */
    public function bolehSetKeputusan(User $user, string $keputusan): bool
    {
        if ($user->isDirektur()) {
            return in_array($keputusan, ['diterima', 'ditolak'], true);
        }

        if ($user->isKabag() || $user->isKasubag()) {
            return $keputusan === 'perlu_revisi';
        }

        return false;
    }

    /**
     * Tandai surat sebagai "Ditolak" secara otomatis jika disposisi terakhirnya
     * sudah melewati batas waktu (berdasarkan prioritas) dan surat belum berstatus final
     * (diterima/ditolak). Dipanggil saat daftar/detail surat diakses, dan juga lewat
     * jadwal harian (lihat App\Console\Commands\TandaiSuratTerlambat).
     */
    public function tandaiOtomatisJikaTerlambat(Surat $surat): bool
    {
        if (in_array($surat->status->value, [StatusSurat::Diterima->value, StatusSurat::Ditolak->value], true)) {
            return false;
        }

        $dispoTerakhir = $surat->relationLoaded('disposisi')
            ? $surat->disposisi->last()
            : $surat->disposisiTerakhir();

        if (! $dispoTerakhir || ! $dispoTerakhir->isOverdue()) {
            return false;
        }

        $surat->update(['status' => StatusSurat::Ditolak]);

        LogAktivitas::catat(
            'surat_ditolak_otomatis',
            "Sistem menandai surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) sebagai \"Ditolak\" secara otomatis karena melewati batas waktu prioritas disposisi.",
            'surat',
            $surat->id
        );

        return true;
    }
}
