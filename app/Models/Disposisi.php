<?php

namespace App\Models;

use App\Enums\Prioritas;
use App\Enums\StatusDisposisi;
use App\Enums\StatusSurat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disposisi extends Model
{
    protected $table = 'disposisi';

    protected $fillable = [
        'surat_id',
        'pengirim_id',
        'penerima_id',
        'tujuan_jabatan',
        'tujuan_bagian',
        'tanggal_disposisi',
        'prioritas',
        'batas_waktu',
        'instruksi',
        'status',
        'tanggal_diterima',
    ];

    protected $casts = [
        'prioritas' => Prioritas::class,
        'status' => StatusDisposisi::class,
        'tanggal_disposisi' => 'date',
        'batas_waktu' => 'date',
        'tanggal_diterima' => 'date',
    ];

    /**
     * Otomatis buat pesan notifikasi ke penerima saat disposisi baru dibuat.
     * Disposisi yang ditujukan ke jabatan (penerima_id null) dilewati — tidak ada
     * akun penerima, notifikasinya dibuat manual oleh alur yang bersangkutan.
     */
    protected static function booted(): void
    {
        static::created(function (Disposisi $disposisi) {
            if ($disposisi->penerima_id === null) {
                return;
            }

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

    public function tautanPublik(): HasMany
    {
        return $this->hasMany(TautanPublikDisposisi::class);
    }

    /**
     * Label tujuan disposisi: nama akun penerima bila ada, atau jabatan tujuan
     * (plus bagian bila diisi) untuk disposisi yang tidak memiliki akun penerima.
     */
    public function keTujuanLabel(): string
    {
        if ($this->penerima_id !== null) {
            return $this->penerima?->nama ?? '-';
        }

        $jabatan = $this->tujuan_jabatan ?? '-';

        return $this->tujuan_bagian
            ? "{$jabatan} ({$this->tujuan_bagian})"
            : $jabatan;
    }

    /**
     * Cek apakah disposisi sudah melewati batas waktu.
     */
    public function isOverdue(): bool
    {
        if ($this->status === StatusDisposisi::Selesai || $this->batas_waktu === null) {
            return false;
        }

        return now()->startOfDay()->gt($this->batas_waktu);
    }

    /**
     * Scope filter disposisi yang terlambat.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', StatusDisposisi::Selesai->value)
            ->whereNotNull('batas_waktu')
            ->whereDate('batas_waktu', '<', now()->toDateString());
    }

    /**
     * Scope filter disposisi yang mendekati tenggat waktu (untuk peringatan dashboard).
     */
    public function scopeMendekatiBatas(Builder $query, int $dalamHari = 3): Builder
    {
        return $query->where('status', '!=', StatusDisposisi::Selesai->value)
            ->whereNotNull('batas_waktu')
            ->whereHas('surat', fn (Builder $q) => $q->whereNotIn('status', [
                StatusSurat::Diterima->value,
                StatusSurat::Ditolak->value,
            ]))
            ->whereDate('batas_waktu', '>=', now()->toDateString())
            ->whereDate('batas_waktu', '<=', now()->addDays($dalamHari)->toDateString());
    }
}
