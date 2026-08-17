<?php

use App\Support\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Expands the seeded roles' permissions from the original binary access_admin/
 * manage_users set to the full view/manage module catalog. Pure data migration
 * (no schema change) — see App\Support\Permission and collaboration-notes.md
 * "Module-level permission system" decision (2026-07-02, CO) for the rationale
 * behind each role's assignment.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('slug', 'super_admin')->update([
            'permissions' => json_encode([
                Permission::ACCESS_ADMIN,
                Permission::MANAGE_USERS,
                Permission::MANAGE_CONTENT,
                Permission::MANAGE_MEDIA,
                Permission::MANAGE_CLIENTS,
                Permission::MANAGE_PROPOSALS,
                Permission::MANAGE_PROJECTS,
                Permission::MANAGE_FINANCE,
                Permission::MANAGE_SETTINGS,
            ]),
        ]);

        DB::table('roles')->where('slug', 'commercial_owner')->update([
            'permissions' => json_encode([
                Permission::ACCESS_ADMIN,
                Permission::MANAGE_CLIENTS,
                Permission::MANAGE_PROPOSALS,
                Permission::MANAGE_FINANCE,
                Permission::MANAGE_PROJECTS,
            ]),
        ]);

        DB::table('roles')->where('slug', 'client_lead')->update([
            'permissions' => json_encode([
                Permission::ACCESS_ADMIN,
                Permission::MANAGE_CLIENTS,
                Permission::MANAGE_PROJECTS,
            ]),
        ]);

        DB::table('roles')->where('slug', 'manager')->update([
            'permissions' => json_encode([
                Permission::ACCESS_ADMIN,
                Permission::MANAGE_CONTENT,
                Permission::MANAGE_MEDIA,
                Permission::MANAGE_CLIENTS,
                Permission::MANAGE_PROJECTS,
            ]),
        ]);

        DB::table('roles')->where('slug', 'editor')->update([
            'permissions' => json_encode([
                Permission::ACCESS_ADMIN,
                Permission::MANAGE_CONTENT,
                Permission::MANAGE_MEDIA,
            ]),
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'super_admin')->update([
            'permissions' => json_encode([Permission::ACCESS_ADMIN, Permission::MANAGE_USERS]),
        ]);

        DB::table('roles')->whereIn('slug', [
            'commercial_owner', 'client_lead', 'manager', 'editor',
        ])->update([
            'permissions' => json_encode([Permission::ACCESS_ADMIN]),
        ]);
    }
};
