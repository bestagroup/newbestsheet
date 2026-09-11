<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectStageFormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_stage_instance_id', 'stage_form_definition_id', 'revision', 'status',
        'payload', 'submitted_by', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_comment',
    ];

    protected $casts = [
        'revision' => 'integer', 'payload' => 'array',
        'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectStageInstance::class, 'project_stage_instance_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(StageFormDefinition::class, 'stage_form_definition_id');
    }
}
