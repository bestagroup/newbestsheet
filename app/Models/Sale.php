<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'count_customers',
        'count_sales',
        'production_count',
        'amount_sales',
        'monthly_income',
        'current_cost',
        'financial_cost',
        'date',
        'description',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'count_customers' => 'integer',
        'count_sales' => 'integer',
        'production_count' => 'integer',
        'date' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
