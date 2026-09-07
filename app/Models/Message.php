<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'surat_id',
        'subject',
        'body',
        'is_read',
        'read_at',
        'deleted_by_sender_at',
        'deleted_by_receiver_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'deleted_by_sender_at' => 'datetime',
        'deleted_by_receiver_at' => 'datetime',
    ];

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function dikirimOleh(User $user): bool
    {
        return $this->sender_id === $user->id;
    }

    public function diterimaOleh(User $user): bool
    {
        return $this->receiver_id === $user->id;
    }

    public function tandaiSudahDibaca(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    /**
     * Tandai pesan dihapus per-pengguna
     */
    public function hapusUntuk(User $user): bool
    {
        if ($this->dikirimOleh($user)) {
            $this->deleted_by_sender_at ??= now();
        } elseif ($this->diterimaOleh($user)) {
            $this->deleted_by_receiver_at ??= now();
        } else {
            return false;
        }

        $this->save();

        // Jika kedua belah pihak sudah menghapus, hapus permanen dari DB
        if ($this->deleted_by_sender_at && $this->deleted_by_receiver_at) {
            $this->delete();

            return true;
        }

        return false;
    }

    /**
     * Pulihkan pesan dari tempat sampah
     */
    public function pulihkanUntuk(User $user): void
    {
        if ($this->dikirimOleh($user)) {
            $this->update(['deleted_by_sender_at' => null]);
        }

        if ($this->diterimaOleh($user)) {
            $this->update(['deleted_by_receiver_at' => null]);
        }
    }

    /**
     * Scope: Pesan di Kotak Masuk
     */
    public function scopeKotakMasukUntuk(Builder $query, User $user): Builder
    {
        return $query->where('receiver_id', $user->id)
            ->whereNull('deleted_by_receiver_at');
    }

    /**
     * Scope: Pesan Terkirim
     */
    public function scopeTerkirimUntuk(Builder $query, User $user): Builder
    {
        return $query->where('sender_id', $user->id)
            ->whereNull('deleted_by_sender_at');
    }

    /**
     * Scope: Pesan di Tempat Sampah milik User
     */
    public function scopeSampahUntuk(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where(fn ($sub) => $sub->where('sender_id', $user->id)->whereNotNull('deleted_by_sender_at'))
                ->orWhere(fn ($sub) => $sub->where('receiver_id', $user->id)->whereNotNull('deleted_by_receiver_at'));
        });
    }
}
