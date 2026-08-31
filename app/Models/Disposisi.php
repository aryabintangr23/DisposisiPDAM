<?php

namespace App\Models;

use App\Enums\Prioritas;
use App\Enums\StatusDisposisi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposisi extends Model
{
    protected $table = 'disposisi';

    protected $fillable = [
        'surat_id', 'pengirim_id', 'penerima_id', 'tanggal_disposisi',
        'prioritas', 'batas_waktu', 'instruksi', 'status', 'tanggal_diterima',
    ];

    protected $casts = [
        'prioritas' => Prioritas::class,
        'status' => StatusDisposisi::class,
        'tanggal_disposisi' => 'date',
        'batas_waktu' => 'date',
        'tanggal_diterima' => 'date',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }
}
