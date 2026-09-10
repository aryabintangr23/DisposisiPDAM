<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    // Log hanya mencatat waktu dibuat (tanpa updated_at)
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
     * Helper untuk mencatat log aktivitas sistem.
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