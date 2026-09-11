<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StageFormDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'invest_step_id', 'code', 'title', 'version', 'json_schema', 'ui_schema',
        'is_required', 'is_active', 'created_by',
    ];

    protected $casts = [
        'version' => 'integer', 'json_schema' => 'array', 'ui_schema' => 'array',
        'is_required' => 'boolean', 'is_active' => 'boolean',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(Investstep::class, 'invest_step_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProjectStageFormSubmission::class);
    }
}
