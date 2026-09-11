<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class subject_file extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];

    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'subject_id');
    }

    public function stepRequirements(): HasMany
    {
        return $this->hasMany(InvestmentStepDocumentRequirement::class, 'subject_file_id');
    }
}
