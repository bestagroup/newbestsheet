<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submenu extends Model
{
    use HasFactory;
    use Sluggable;

    protected $fillable = [
        'priority', 'title', 'label', 'menu_id', 'slug', 'class', 'controller', 'tab_title',
        'page_title', 'keyword', 'description', 'status', 'user_id', 'viewcount',
    ];

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
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
