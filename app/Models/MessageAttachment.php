<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'path',
        'disk',
        'original_name',
        'mime_type',
        'size',
        'sha256',
        'scan_status',
        'scanned_at',
    ];

    protected $casts = ['scanned_at' => 'datetime'];

    /* ---------------- Relations ---------------- */

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /* ---------------- Accessors ---------------- */

    public function getUrlAttribute(): string
    {
        return route('message-attachments.download', ['attachment' => $this->getKey()]);
    }
}
