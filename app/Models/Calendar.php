<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by', 'title', 'label', 'start', 'end', 'all_day', 'url', 'location', 'description', 'guests', 'google_event_id', 'google_sync_status', 'google_sync_error',
    ];

    protected $casts = [
        'guests' => 'array',
        'all_day' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
