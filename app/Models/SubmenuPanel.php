<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmenuPanel extends Model
{
    use HasFactory;
    use Sluggable;

    protected $fillable = ['title', 'slug', 'label', 'menu_id', 'user_id'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => $this->shouldSlug(),
            ],
        ];
    }

    protected function shouldSlug()
    {
        return $this->id != 1;
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MenuPanel::class, 'menu_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(SubmenuPermission::class);
    }
}
