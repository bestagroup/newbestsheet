<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuarterlyPerformanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id', 'project_contract_id', 'year', 'quarter', 'revision', 'is_current',
        'status', 'period_starts_at', 'period_ends_at', 'executive_summary', 'achievements',
        'challenges', 'risks', 'financial_snapshot', 'operational_snapshot', 'submitted_by',
        'submitted_at', 'reviewed_by', 'reviewed_at', 'review_comment',
    ];

    protected $casts = [
        'year' => 'integer', 'quarter' => 'integer', 'revision' => 'integer',
        'is_current' => 'boolean', 'period_starts_at' => 'date', 'period_ends_at' => 'date',
        'achievements' => 'array', 'challenges' => 'array', 'risks' => 'array',
        'financial_snapshot' => 'array', 'operational_snapshot' => 'array',
        'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProjectContract::class, 'project_contract_id');
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(KpiMeasurement::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
