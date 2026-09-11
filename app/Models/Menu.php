<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;
    use Sluggable;

    protected $fillable = [
        'priority', 'title', 'label', 'icon', 'slug', 'tab_title', 'page_title', 'submenu',
        'class', 'controller', 'status', 'home_show', 'keyword', 'description', 'user_id', 'viewcount',
    ];

    public function submenus(): HasMany
    {
        return $this->hasMany(Submenu::class, 'menu_id');
    }

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
}
