<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title',
        'label',
        'slug',
        'menu_panel_id',
        'submenu_panel_id',
        'user_id',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role', 'permission_id', 'role_id')
            ->withPivot(['can_view', 'can_insert', 'can_edit', 'can_delete'])
            ->withTimestamps();
    }

    /**
     * Backward-compatible alias for legacy callers.
     */
    public function role(): BelongsToMany
    {
        return $this->roles();
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

    protected function shouldSlug(): bool
    {
        return $this->id != 1;
    }
}
