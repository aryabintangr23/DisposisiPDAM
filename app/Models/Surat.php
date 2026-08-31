<?php

namespace App\Models;

use App\Enums\ArahSurat;
use App\Enums\StatusSurat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surat extends Model
{
    protected $table = 'surat';

    protected $fillable = [
        'created_by', 'arah_surat', 'jenis_surat', 'nomor_surat',
        'nomor_agenda', 'tanggal_surat', 'tanggal_diterima',
        'surat_dari', 'tujuan_surat', 'perihal', 'status',
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

    public function lampiran(): HasMany
    {
        return $this->hasMany(Lampiran::class);
    }

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class)->orderBy('created_at');
    }

    public function disposisiTerakhir(): ?Disposisi
    {
        return $this->disposisi()->latest('created_at')->first();
    }
}
