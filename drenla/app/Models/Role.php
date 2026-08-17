<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A staff role, fully admin-customizable — this is real data, not a PHP enum.
 * `is_system` marks the five roles the app ships with (super_admin, commercial_owner,
 * client_lead, manager, editor); a future role-management UI should refuse to delete
 * a system role (nothing currently prevents renaming one, only deletion should be
 * guarded, so staff can still relabel/reword a shipped role).
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'label',
        'description',
        'sort_order',
        'is_system',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissions ?? [], true);
    }
}
