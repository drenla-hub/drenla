<?php

namespace App\Models;

use App\Support\Permission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Convenience slug constants for factories/tests/seeders — NOT the source of
     * truth for what roles exist or what they can do. That's the `roles` table
     * (App\Models\Role), which staff can add to/edit fully; these five are just the
     * ones the app ships with. See collaboration-notes.md "Role system" decision
     * (2026-07-02, CO).
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_COMMERCIAL_OWNER = 'commercial_owner';

    public const ROLE_CLIENT_LEAD = 'client_lead';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_EDITOR = 'editor';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return array<string, string> role slug => label, sourced from the `roles` table — includes any custom roles staff have added */
    public static function roleOptions(): array
    {
        return Role::orderBy('sort_order')->pluck('label', 'slug')->all();
    }

    /** @return array<string, string> role slug => description, sourced from the `roles` table */
    public static function roleDescriptions(): array
    {
        return Role::orderBy('sort_order')->pluck('description', 'slug')->all();
    }

    /** The `roles` row this user's `role` slug points at. Null if the slug doesn't match any row (e.g. a role was deleted out from under a user). */
    public function roleRecord()
    {
        return $this->belongsTo(Role::class, 'role', 'slug');
    }

    /** Does this user's role have the given permission key? */
    public function hasPermission(string $key): bool
    {
        return (bool) $this->roleRecord?->hasPermission($key);
    }

    /** Does this user's role have at least one of the given permission keys? Used for "manage implies view" checks. */
    public function hasAnyPermission(string ...$keys): bool
    {
        foreach ($keys as $key) {
            if ($this->hasPermission($key)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission(Permission::ACCESS_ADMIN);
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission(Permission::MANAGE_USERS);
    }

    public function assignedProjectTasks()
    {
        return $this->hasMany(ProjectTask::class, 'assigned_to');
    }

    public function ownedProjects()
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function journalConversations()
    {
        return $this->hasMany(JournalConversation::class);
    }
}
