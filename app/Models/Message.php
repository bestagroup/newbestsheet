<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'parent_id',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /* ---------------- Relations ---------------- */

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id')->orderBy('created_at');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function recipients()
    {
        return $this->hasMany(MessageRecipient::class);
    }
}
