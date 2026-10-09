<?php

namespace App\Models;

use App\Support\LocalizedInputNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finance extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'amount',
        'date',
        'serial',
        'description',
        'docserial',
        'finance_type',
        'idempotency_key',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeFilter($query, $request)
    {
        $projectId = $request->input('project_id', $request->input('company_id'));
        $fromDate = LocalizedInputNormalizer::jalaliDate($request->input('from_date'));
        $toDate = LocalizedInputNormalizer::jalaliDate($request->input('to_date'));

        return $query
            ->when($projectId, fn ($q) => $q->where('project_id', (int) $projectId))
            ->when($fromDate, fn ($q) => $q->where('date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->where('date', '<=', $toDate));
    }
}
