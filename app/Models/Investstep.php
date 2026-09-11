<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investstep extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'code',
        'sequence',
        'description',
        'content',
        'status',
        'weight',
        'is_system_locked',
        'assignment_required',
        'sla_hours',
    ];

    protected $casts = [
        'weight' => 'decimal:3',
        'sequence' => 'integer',
        'is_system_locked' => 'boolean',
        'assignment_required' => 'boolean',
        'sla_hours' => 'integer',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'invest_step_id');
    }

    public function documentRequirements(): HasMany
    {
        return $this->hasMany(InvestmentStepDocumentRequirement::class, 'invest_step_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function projectStages(): HasMany
    {
        return $this->hasMany(ProjectStageInstance::class, 'invest_step_id');
    }

    public function formDefinitions(): HasMany
    {
        return $this->hasMany(StageFormDefinition::class, 'invest_step_id');
    }
}
