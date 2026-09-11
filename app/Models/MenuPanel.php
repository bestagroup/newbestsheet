<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuPanel extends Model
{
    use HasFactory;
    use Sluggable;

    protected $fillable = ['title', 'label', 'slug', 'icon'];

    public function submenus(): HasMany
    {
        return $this->hasMany(SubmenuPanel::class, 'menu_id');
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
