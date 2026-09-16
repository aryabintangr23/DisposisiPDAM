<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArsipSuratKeluar extends Model
{
    use SoftDeletes;

    protected $table = 'arsip_surat_keluar';

    protected $fillable = [
        'created_by',
        'nomor_urut',
        'nomor_berkas',
        'tanggal_surat',
        'perihal',
        'tanggal_dikeluarkan',
        'tujuan',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
        'tanggal_dikeluarkan' => 'date',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope arsip berdasarkan role user (sama seperti Surat::untukRole):
     * Admin melihat semua, Staff hanya melihat arsip milik role Staff (bukan per-akun).
     */
    public function scopeUntukRole(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('pembuat', fn ($q) => $q->where('role_id', $user->role_id));
    }
}
