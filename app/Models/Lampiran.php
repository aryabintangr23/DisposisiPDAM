<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lampiran extends Model
{
    protected $table = 'lampiran';

    protected $fillable = ['surat_id', 'nama_file', 'path_file', 'tipe_file', 'ukuran_file'];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }
}
