<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalNotificationDelivery extends Model
{
    protected $fillable = [
        'fingerprint',
        'event_key',
        'trigger_key',
        'channel',
        'recipient_user_id',
        'recipient_phone',
        'related_type',
        'related_id',
        'status',
        'attempts',
        'sent_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
