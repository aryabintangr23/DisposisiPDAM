<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSurat extends Model
{
    protected $table = 'scan_surat';

    protected $fillable = [
        'user_id',
        'token',
        'nama_file',
        'path_file',
        'tipe_file',
        'ukuran_file',
        'hasil_json',
        'teks_ocr',
        'status',
        'pesan_error',
        'dipakai_at',
        'expires_at',
    ];

    protected $casts = [
        'hasil_json' => 'array',
        'ukuran_file' => 'integer',
        'dipakai_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Berkas scan masih boleh dipakai sebagai lampiran surat.
     */
    public function bolehDipakai(): bool
    {
        return $this->dipakai_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    /**
     * Hasil ekstraksi yang sudah tersimpan, dengan bentuk yang aman untuk response.
     *
     * @return array{fields: array<string, array{value: string, confidence: float}>, peringatan: array<int, string>}
     */
    public function hasilEkstraksi(): array
    {
        $hasil = is_array($this->hasil_json) ? $this->hasil_json : [];

        return [
            'fields' => is_array($hasil['fields'] ?? null) ? $hasil['fields'] : [],
            'peringatan' => is_array($hasil['peringatan'] ?? null) ? $hasil['peringatan'] : [],
        ];
    }
}
