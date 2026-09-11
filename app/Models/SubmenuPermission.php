<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmenuPermission extends Model
{
    use HasFactory;

    protected $fillable = ['submenu_id', 'user_id', 'can_create', 'can_edit', 'can_delete'];

    public function submenu(): BelongsTo
    {
        return $this->belongsTo(SubmenuPanel::class, 'submenu_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
