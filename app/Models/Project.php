<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public const PORTFOLIO_MINIMUM_STEP = 14;

    protected $guarded = [];

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(Investstep::class, 'invest_step');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function minutes(): HasMany
    {
        return $this->hasMany(Minute::class, 'project_id');
    }

    public function minute(): HasMany
    {
        return $this->minutes();
    }

    public function finances(): HasMany
    {
        return $this->hasMany(Finance::class);
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(KPI::class);
    }

    public function currentKpis(): HasMany
    {
        return $this->kpis()->where('is_current', true);
    }

    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class);
    }

    public function projectSteps(): HasMany
    {
        return $this->hasMany(Project_step::class);
    }

    public function financialStatements(): HasMany
    {
        return $this->hasMany(Financial_statement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function stageInstances(): HasMany
    {
        return $this->hasMany(ProjectStageInstance::class)->orderBy('sequence');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ProjectContract::class);
    }

    public function quarterlyPerformanceReports(): HasMany
    {
        return $this->hasMany(QuarterlyPerformanceReport::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(CompanyMembers::class);
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(ProjectCommitment::class);
    }
}
