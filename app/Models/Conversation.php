<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'subject',
        'type', // internal | external
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* ---------------- Relations ---------------- */

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot([
                'unread_count',
                'muted_at',
                'archived_at',
                'last_read_at',
            ])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}
