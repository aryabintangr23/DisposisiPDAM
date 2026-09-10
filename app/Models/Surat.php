<?php

namespace App\Models;

use App\Enums\ArahSurat;
use App\Enums\StatusSurat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Surat extends Model
{
    use SoftDeletes;

    protected $table = 'surat';

    protected $fillable = [
        'created_by',
        'arah_surat',
        'jenis_surat',
        'nomor_surat',
        'nomor_agenda',
        'tanggal_surat',
        'tanggal_diterima',
        'surat_dari',
        'tujuan_surat',
        'perihal',
        'status',
    ];

    protected $casts = [
        'arah_surat' => ArahSurat::class,
        'status' => StatusSurat::class,
        'tanggal_surat' => 'date',
        'tanggal_diterima' => 'date',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope query surat berdasarkan role user yang login.
     */
    public function scopeUntukRole(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isStaff()) {
            return $query->whereHas('pembuat', fn ($q) => $q->where('role_id', $user->role_id));
        }

        $suratIds = Disposisi::whereHas('penerima', fn ($q) => $q->where('role_id', $user->role_id))
            ->orWhereHas('pengirim', fn ($q) => $q->where('role_id', $user->role_id))
            ->pluck('surat_id')
            ->unique();

        return $query->whereIn('id', $suratIds);
    }

    public function lampiran(): HasMany
    {
        return $this->hasMany(Lampiran::class);
    }

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class)->orderBy('created_at');
    }

    /**
     * Ambil data disposisi paling baru.
     */
    public function disposisiTerakhir(): ?Disposisi
    {
        return $this->disposisi()->reorder('created_at', 'desc')->first();
    }

    /**
     * Cek apakah ada disposisi yang terlambat (overdue).
     */
    public function adaDisposisiTerlambat(): bool
    {
        return $this->disposisi->contains(fn (Disposisi $d) => $d->isOverdue());
    }
}