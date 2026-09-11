<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'role_id',
        'invest_step_id',
        'project_stage_instance_id',
        'assignment_type',
        'assigned_by',
        'is_active',
        'assigned_at',
        'accepted_at',
        'ended_at',
        'ended_reason',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function investStep(): BelongsTo
    {
        return $this->belongsTo(Investstep::class, 'invest_step_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStageInstance::class, 'project_stage_instance_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
