<?php

namespace App\Models;

// Catatan: file ini menggantikan app/Models/User.php bawaan Laravel.
// Perubahan utama dari default: kolom "name" -> "nama", tambah relasi
// role(), suratDibuat(), disposisiTerkirim(), disposisiDiterima().

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

    // Helper agar controller/policy tidak hardcode string role berulang kali.
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
}
