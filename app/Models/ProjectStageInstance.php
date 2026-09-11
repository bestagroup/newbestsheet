<?php

namespace App\Models;

use App\Enums\ProjectStageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProjectStageInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'invest_step_id', 'stage_code', 'sequence', 'title_snapshot',
        'weight_snapshot', 'status', 'opened_at', 'submitted_at', 'review_started_at',
        'decided_at', 'due_at', 'decided_by', 'lock_version', 'metadata',
    ];

    protected $casts = [
        'status' => ProjectStageStatus::class,
        'weight_snapshot' => 'decimal:3',
        'sequence' => 'integer',
        'opened_at' => 'datetime',
        'submitted_at' => 'datetime',
        'review_started_at' => 'datetime',
        'decided_at' => 'datetime',
        'due_at' => 'datetime',
        'lock_version' => 'integer',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(Investstep::class, 'invest_step_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->where('is_active', true);
    }

    public function decision(): HasOne
    {
        return $this->hasOne(Project_step::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProjectStageComment::class)->latest('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(ProjectStageFormSubmission::class);
    }
}
