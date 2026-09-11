<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id', 'contract_number', 'title', 'status', 'signed_at', 'starts_at',
        'ends_at', 'amount', 'equity_percentage', 'currency', 'contract_media_file_id',
        'created_by', 'terms',
    ];

    protected $casts = [
        'signed_at' => 'date', 'starts_at' => 'date', 'ends_at' => 'date',
        'amount' => 'decimal:0', 'equity_percentage' => 'decimal:4', 'terms' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(KPI::class);
    }

    public function currentKpis(): HasMany
    {
        return $this->kpis()->where('is_current', true);
    }
}
