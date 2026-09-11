<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_id', 'quarterly_performance_report_id', 'year', 'quarter', 'measured_value',
        'target_snapshot', 'achievement_percentage', 'status', 'notes',
        'evidence_media_file_id', 'submitted_by', 'submitted_at', 'reviewed_by',
        'reviewed_at', 'review_comment',
    ];

    protected $casts = [
        'year' => 'integer', 'quarter' => 'integer', 'measured_value' => 'decimal:6',
        'target_snapshot' => 'decimal:6', 'achievement_percentage' => 'decimal:3',
        'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(KPI::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(QuarterlyPerformanceReport::class, 'quarterly_performance_report_id');
    }
}
