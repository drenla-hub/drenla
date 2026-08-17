<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the hardcoded role list (previously PHP constants in App\Models\User)
 * with a real, admin-customizable table. `users.role` stays as-is (a string slug
 * column) so nothing that already queries/sets it breaks — this table is looked up
 * by slug, not joined via a foreign key. See collaboration-notes.md "Role system"
 * decision (2026-07-02, CO) for why.
 *
 * Seeded directly in this migration (not a seeder) so `roles` always exist after a
 * fresh `migrate` — including in tests, which run migrations but not seeders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            [
                'slug' => 'super_admin', 'label' => 'Super Admin',
                'description' => 'Full system control, staff management, and role changes.',
                'sort_order' => 1, 'is_system' => true,
                'permissions' => json_encode(['access_admin', 'manage_users']),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'commercial_owner', 'label' => 'Commercial Owner',
                'description' => 'Owns commercial workflow, clients, finance, and delivery oversight.',
                'sort_order' => 2, 'is_system' => true,
                'permissions' => json_encode(['access_admin']),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'client_lead', 'label' => 'Client Lead',
                'description' => 'Owns client-facing project progress, milestones, and task follow-through.',
                'sort_order' => 3, 'is_system' => true,
                'permissions' => json_encode(['access_admin']),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'manager', 'label' => 'Manager',
                'description' => 'Operates delivery records, content, projects, and client work.',
                'sort_order' => 4, 'is_system' => true,
                'permissions' => json_encode(['access_admin']),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'slug' => 'editor', 'label' => 'Editor',
                'description' => 'Maintains content and assigned operational work.',
                'sort_order' => 5, 'is_system' => true,
                'permissions' => json_encode(['access_admin']),
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
