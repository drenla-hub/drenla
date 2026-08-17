<?php

use App\Support\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Grants the new manage_chat permission to the roles that already handle
 * client/lead-facing work — mirrors how view/manage pairs were added for other
 * modules in 2026_07_02_120000_expand_role_permissions.php. Roles are
 * admin-customizable data, so this only seeds a sensible default; staff can
 * change it afterward via the role-editing UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['super_admin', 'commercial_owner'] as $slug) {
            $role = DB::table('roles')->where('slug', $slug)->first();

            if (! $role) {
                continue;
            }

            $permissions = json_decode($role->permissions, true) ?? [];

            if (! in_array(Permission::MANAGE_CHAT, $permissions, true)) {
                $permissions[] = Permission::MANAGE_CHAT;
            }

            DB::table('roles')->where('slug', $slug)->update([
                'permissions' => json_encode($permissions),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['super_admin', 'commercial_owner'] as $slug) {
            $role = DB::table('roles')->where('slug', $slug)->first();

            if (! $role) {
                continue;
            }

            $permissions = array_values(array_diff(
                json_decode($role->permissions, true) ?? [],
                [Permission::MANAGE_CHAT]
            ));

            DB::table('roles')->where('slug', $slug)->update([
                'permissions' => json_encode($permissions),
            ]);
        }
    }
};
