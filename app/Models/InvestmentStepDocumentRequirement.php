<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentStepDocumentRequirement extends Model
{
    use HasFactory;

    protected $table = 'invest_step_document_requirements';

    protected $fillable = [
        'invest_step_id',
        'subject_file_id',
        'is_required',
        'minimum_files',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'minimum_files' => 'integer',
        'sort_order' => 'integer',
    ];

    public function investStep(): BelongsTo
    {
        return $this->belongsTo(Investstep::class, 'invest_step_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(subject_file::class, 'subject_file_id');
    }
}
