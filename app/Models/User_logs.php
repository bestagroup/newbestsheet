<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_logs extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'action', 'ip_address', 'user_agent', 'request_id', 'subject_type',
        'subject_id', 'status', 'description', 'old_values', 'new_values', 'metadata',
    ];

    protected $casts = [
        'status' => 'boolean',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
