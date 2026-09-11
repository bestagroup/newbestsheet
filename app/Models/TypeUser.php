<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeUser extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'title_fa'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'type_id');
    }
}
