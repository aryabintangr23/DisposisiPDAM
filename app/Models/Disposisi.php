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

    /**
     * Setiap disposisi baru otomatis mengirim "pesan" ke penerima (mirip
     * notifikasi email), supaya menu Pesan penerima langsung bertambah dan
     * berisi ringkasan surat + instruksi yang didisposisikan.
     */
    protected static function booted(): void
    {
        static::created(function (Disposisi $disposisi) {
            $disposisi->loadMissing(['surat', 'pengirim']);

            $surat = $disposisi->surat;
            $pengirim = $disposisi->pengirim;

            $body = "Anda menerima disposisi surat \"{$surat->perihal}\" (No. {$surat->nomor_surat}) dari {$pengirim->nama}.\n\n"
                ."Prioritas: {$disposisi->prioritas->label()}";

            if ($disposisi->batas_waktu) {
                $body .= "\nBatas waktu: {$disposisi->batas_waktu->format('d-m-Y')}";
            }

            if ($disposisi->instruksi) {
                $body .= "\n\nInstruksi:\n{$disposisi->instruksi}";
            }

            Message::create([
                'sender_id' => $disposisi->pengirim_id,
                'receiver_id' => $disposisi->penerima_id,
                'surat_id' => $disposisi->surat_id,
                'subject' => 'Disposisi Surat: '.$surat->nomor_surat,
                'body' => $body,
            ]);
        });
    }

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

