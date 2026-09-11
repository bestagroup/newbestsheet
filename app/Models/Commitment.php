<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commitment extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'guarantee', 'status'];

    public function projectCommitments(): HasMany
    {
        return $this->hasMany(ProjectCommitment::class);
    }
}
