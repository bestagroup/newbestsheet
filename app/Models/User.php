<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'national_id',
        'father_name',
        'gender',
        'postalcode',
        'address',
        'level',
        'status',
        'role_id',
        'change_password',
        'password',
        'google_token',
        'google_refresh_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Canonical many-to-many role relation backed by the existing role_user pivot.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    /**
     * Backward-compatible alias used by existing controllers/views.
     */
    public function role(): BelongsToMany
    {
        return $this->roles();
    }

    public function activeCode(): HasMany
    {
        return $this->hasMany(ActiveCode::class);
    }

    public function permissionsWithActions()
    {
        $this->loadMissing('roles.permissions');

        $activeRoles = $this->roles->filter(
            static fn (Role $role) => is_null($role->status) || (int) $role->status === 4
        );

        return $activeRoles
            ->flatMap(function (Role $role) {
                return $role->permissions->map(function (Permission $permission) {
                    return [
                        'slug' => $permission->slug,
                        'can_view' => (bool) $permission->pivot->can_view,
                        'can_insert' => (bool) $permission->pivot->can_insert,
                        'can_edit' => (bool) $permission->pivot->can_edit,
                        'can_delete' => (bool) $permission->pivot->can_delete,
                    ];
                });
            })
            ->groupBy('slug')
            ->map(function ($permissions, string $slug) {
                return (object) [
                    'slug' => $slug,
                    'can_view' => $permissions->contains('can_view', true),
                    'can_insert' => $permissions->contains('can_insert', true),
                    'can_edit' => $permissions->contains('can_edit', true),
                    'can_delete' => $permissions->contains('can_delete', true),
                ];
            })
            ->values();
    }

    public function hasRole($role): bool
    {
        $this->loadMissing('roles');

        $activeRoles = $this->roles->filter(
            static fn (Role $assignedRole) => is_null($assignedRole->status) || (int) $assignedRole->status === 4
        );

        if (is_string($role)) {
            return $activeRoles->contains('title', $role);
        }

        $roleTitles = collect($role)->map(static function ($candidate) {
            return $candidate instanceof Role ? $candidate->title : (string) $candidate;
        });

        return $activeRoles->pluck('title')->intersect($roleTitles)->isNotEmpty();
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TypeUser::class, 'type_id');
    }

    public function project()
    {
        return $this->hasOne(Project::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activeCodes(): HasMany
    {
        return $this->hasMany(ActiveCode::class);
    }

    public function logs()
    {
        return $this->hasMany(User_logs::class);
    }

    public function lastLogin()
    {
        return $this->hasOne(User_logs::class)
            ->where('action', 'login')
            ->where('status', true)
            ->latestOfMany();
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot([
                'unread_count',
                'muted_at',
                'archived_at',
                'last_read_at',
            ])
            ->withTimestamps();
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function projectAssignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }
}
