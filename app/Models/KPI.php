<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KPI extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kpis';

    protected $fillable = [
        'project_id',
        'previous_kpi_id',
        'root_kpi_id',
        'revision_number',
        'is_current',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
        'project_contract_id',
        'code',
        'title',
        'description',
        'type',
        'direction',
        'type_value',
        'baseline_value',
        'target_value',
        'weight',
        'tolerance',
        'value',
        'unit',
        'deadline',
        'deadline_at',
        'period_time',
        'measurement_frequency',
        'time_step',
        'starts_at',
        'ends_at',
        'owner_user_id',
        'status',
        'completed_at',
        'kpi_number',
        'file_link',
    ];

    protected $casts = [
        'kpi_number' => 'integer',
        'revision_number' => 'integer',
        'is_current' => 'boolean',
        'reviewed_at' => 'datetime',
        'deadline_at' => 'date',
        'completed_at' => 'datetime',
        'baseline_value' => 'decimal:6',
        'target_value' => 'decimal:6',
        'weight' => 'decimal:3',
        'tolerance' => 'decimal:6',
        'starts_at' => 'date',
        'ends_at' => 'date',
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

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_kpi_id');
    }

    public function rootVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'root_kpi_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'root_kpi_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
