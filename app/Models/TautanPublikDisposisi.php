<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TautanPublikDisposisi extends Model
{
    protected $table = 'tautan_publik_disposisi';

    protected $fillable = [
        'surat_id',
        'disposisi_id',
        'token',
        'dibuat_oleh',
        'kadaluarsa_at',
    ];

    protected $casts = [
        'kadaluarsa_at' => 'datetime',
        'terakhir_diakses_at' => 'datetime',
    ];

    /**
     * Dipakai untuk route model binding via token (bukan id), supaya URL publik
     * tidak menebak-nebak id record internal.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function disposisi(): BelongsTo
    {
        return $this->belongsTo(Disposisi::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function isKadaluarsa(): bool
    {
        return $this->kadaluarsa_at !== null && now()->gt($this->kadaluarsa_at);
    }

    /**
     * Buat kode token pendek (8 karakter) yang dipastikan unik untuk tautan publik baru.
     * Memakai alfabet tanpa karakter yang gampang tertukar (0/O, 1/l/I) supaya nyaman
     * dibaca/diketik ulang, dan hasilnya dipakai sebagai short link, mis. /d/7kX9mQa2.
     */
    public static function buatTokenUnik(): string
    {
        $alfabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

        do {
            $token = '';

            for ($i = 0; $i < 8; $i++) {
                $token .= $alfabet[random_int(0, strlen($alfabet) - 1)];
            }
        } while (static::where('token', $token)->exists());

        return $token;
    }
}
