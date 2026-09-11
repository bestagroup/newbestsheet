<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project_step extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'decided_at' => 'datetime',
        'decision_metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investStep(): BelongsTo
    {
        return $this->belongsTo(Investstep::class, 'step_number');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ProjectAssignment::class, 'project_assignment_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStageInstance::class, 'project_stage_instance_id');
    }

    public function actorRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'actor_role_id');
    }
}
