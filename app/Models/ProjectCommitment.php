<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCommitment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'commitment_id',
        'created_by',
        'due_date',
        'due_at',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'due_at' => 'date',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(Commitment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->completed_at !== null;
    }
}
