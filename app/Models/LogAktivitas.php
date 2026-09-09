<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    // Baris log tidak pernah diubah setelah dibuat — tidak perlu updated_at.
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'aksi',
        'deskripsi',
        'entitas',
        'entitas_id',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cara utama untuk mencatat aktivitas dari controller mana pun:
     *
     *   LogAktivitas::catat(
     *       'surat_dibuat',
     *       "Surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) dibuat.",
     *       'surat',
     *       $surat->id
     *   );
     *
     * user_id & ip_address otomatis diambil dari request yang sedang
     * berjalan (auth()->id() bisa null, mis. untuk percobaan login gagal).
     */
    public static function catat(
        string $aksi,
        string $deskripsi,
        ?string $entitas = null,
        ?int $entitasId = null,
        ?int $userId = null
    ): void {
        static::create([
            'user_id' => $userId ?? auth()->id(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'ip_address' => request()?->ip(),
        ]);
    }
}
