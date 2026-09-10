<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'nama',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function suratDibuat(): HasMany
    {
        return $this->hasMany(Surat::class, 'created_by');
    }

    public function disposisiTerkirim(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'pengirim_id');
    }

    public function disposisiDiterima(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'penerima_id');
    }

    public function pesanMasuk(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function pesanKeluar(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Hitung jumlah pesan masuk yang belum dibaca dan tidak di tempat sampah.
     */
    public function jumlahPesanBelumDibaca(): int
    {
        return $this->pesanMasuk()
            ->where('is_read', false)
            ->whereNull('deleted_by_receiver_at')
            ->count();
    }

    public function isStaff(): bool
    {
        return $this->role?->nama_role === 'staff_umum';
    }

    public function isKabag(): bool
    {
        return $this->role?->nama_role === 'kabag_umum';
    }

    public function isDirektur(): bool
    {
        return $this->role?->nama_role === 'direktur';
    }

    public function isAdmin(): bool
    {
        return $this->role?->nama_role === 'admin';
    }

    /**
     * Cek apakah user memiliki role yang sama dengan user lain.
     */
    public function sameRoleAs(?User $lain): bool
    {
        return $lain !== null && $this->role_id === $lain->role_id;
    }
}