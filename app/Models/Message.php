<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sender_id', 'receiver_id', 'surat_id', 'subject', 'body', 'is_read', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
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

    public function tandaiSudahDibaca(): void
    {
        if (! $this->is_read) {
            $this->update(['is_read' => true, 'read_at' => now()]);
        }
    }
}
