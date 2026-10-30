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
     * Ambil tautan publik yang masih aktif (belum kedaluwarsa) untuk disposisi ini,
     * kalau ada. Dipakai untuk menampilkan tautan otomatis pada riwayat disposisi
     * tanpa perlu query tambahan selama relasi `tautanPublik` sudah di-eager-load.
     */
    public function tautanPublikAktif(): ?TautanPublikDisposisi
    {
        return $this->tautanPublik
            ->filter(fn (TautanPublikDisposisi $t) => ! $t->isKadaluarsa())
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * Daftar jabatan tujuan disposisi keputusan Direktur. Disimpan sebagai JSON
     * array berisi objek {jabatan, bagian} supaya satu keputusan bisa diarahkan
     * ke lebih dari satu jabatan. Baris lama yang masih menyimpan satu jabatan
     * sebagai teks biasa (dengan bagian di kolom terpisah) tetap didukung.
     *
     * @return array<int, array{jabatan: string, bagian: ?string}>
     */
    public function tujuanJabatanList(): array
    {
        $mentah = $this->tujuan_jabatan;

        if (blank($mentah)) {
            return [];
        }

        $terurai = json_decode($mentah, true);

        if (is_array($terurai)) {
            return collect($terurai)
                ->map(function ($item) {
                    if (is_string($item)) {
                        return ['jabatan' => $item, 'bagian' => null];
                    }

                    if (! is_array($item) || blank($item['jabatan'] ?? null)) {
                        return null;
                    }

                    return [
                        'jabatan' => (string) $item['jabatan'],
                        'bagian' => filled($item['bagian'] ?? null) ? (string) $item['bagian'] : null,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return [[
            'jabatan' => (string) $mentah,
            'bagian' => filled($this->tujuan_bagian) ? (string) $this->tujuan_bagian : null,
        ]];
    }

    /**
     * Gabungan label seluruh jabatan tujuan, mis. "Kabag Umum (Bagian X), Kasubag".
     */
    public function tujuanJabatanLabel(): string
    {
        $label = collect($this->tujuanJabatanList())
            ->map(fn (array $tujuan) => $tujuan['bagian']
                ? "{$tujuan['jabatan']} ({$tujuan['bagian']})"
                : $tujuan['jabatan'])
            ->all();

        return $label === [] ? '-' : implode(', ', $label);
    }

    /**
     * Label tujuan disposisi: nama akun penerima bila ada, atau seluruh jabatan
     * tujuan (plus bagian bila diisi) untuk disposisi tanpa akun penerima.
     */
    public function keTujuanLabel(): string
    {
        if ($this->penerima_id !== null) {
            return $this->penerima?->nama ?? '-';
        }

        return $this->tujuanJabatanLabel();
    }

    /**
     * Cek apakah disposisi sudah melewati batas waktu.
     */
    public function isOverdue(): bool
    {
        // Langkah yang sudah diterima / diminta revisi / selesai tidak lagi terlambat.
        if ($this->status->sudahDitanggapi() || $this->batas_waktu === null) {
            return false;
        }

        return now()->startOfDay()->gt($this->batas_waktu);
    }

    /**
     * Scope filter disposisi yang terlambat.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotIn('status', [StatusDisposisi::Diterima->value, StatusDisposisi::Revisi->value, StatusDisposisi::Selesai->value])
            ->whereNotNull('batas_waktu')
            ->whereDate('batas_waktu', '<', now()->toDateString());
    }

    /**
     * Scope filter disposisi yang mendekati tenggat waktu (untuk peringatan dashboard).
     * Hanya disposisi dengan status Terkirim/Dibaca/Belum ditanggapi yang ditampilkan
     * agar hilang saat penerima sudah menanggapi (Diterima/Revisi) atau menyelesaikan.
     */
    public function scopeMendekatiBatas(Builder $query, int $dalamHari = 3): Builder
    {
        return $query->whereNotIn('status', [StatusDisposisi::Diterima->value, StatusDisposisi::Revisi->value, StatusDisposisi::Selesai->value])
            ->whereNotNull('batas_waktu')
            ->whereHas('surat', fn (Builder $q) => $q->whereNotIn('status', [
                StatusSurat::Diterima->value,
                StatusSurat::Ditolak->value,
            ]))
            ->whereDate('batas_waktu', '>=', now()->toDateString())
            ->whereDate('batas_waktu', '<=', now()->addDays($dalamHari)->toDateString());
    }
}
