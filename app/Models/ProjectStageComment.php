<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStageComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_stage_instance_id', 'author_id', 'project_assignment_id',
        'type', 'visibility', 'body', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStageInstance::class, 'project_stage_instance_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ProjectAssignment::class, 'project_assignment_id');
    }
}
