<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Permission;

it('seeds the five shipped roles as real database rows, not PHP constants', function () {
    expect(Role::count())->toBe(5);
    expect(Role::where('slug', 'super_admin')->exists())->toBeTrue();
    expect(Role::where('slug', 'editor')->exists())->toBeTrue();
    expect(Role::where('is_system', true)->count())->toBe(5);
});

it('derives User::roleOptions() and roleDescriptions() from the roles table', function () {
    expect(User::roleOptions())->toBe([
        'super_admin' => 'Super Admin',
        'commercial_owner' => 'Commercial Owner',
        'client_lead' => 'Client Lead',
        'manager' => 'Manager',
        'editor' => 'Editor',
    ]);

    expect(User::roleDescriptions()['super_admin'])->toContain('Full system control');
});

it('only grants manage_users to the role that has that permission', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    $manager = User::factory()->create(['role' => 'manager']);

    expect($superAdmin->canManageUsers())->toBeTrue();
    expect($manager->canManageUsers())->toBeFalse();

    expect($superAdmin->isAdmin())->toBeTrue();
    expect($manager->isAdmin())->toBeTrue();
});

it('denies admin access to a user whose role slug matches nothing (e.g. an orphaned/deleted role)', function () {
    $user = User::factory()->create(['role' => 'ghost_role']);

    expect($user->isAdmin())->toBeFalse();
    expect($user->canManageUsers())->toBeFalse();
});

it('lets staff create a brand-new custom role that immediately works everywhere, with no code changes', function () {
    Role::create([
        'slug' => 'finance_lead',
        'label' => 'Finance Lead',
        'description' => 'Owns invoicing and payment gating decisions.',
        'sort_order' => 6,
        'is_system' => false,
        'permissions' => [Permission::ACCESS_ADMIN, Permission::MANAGE_USERS],
    ]);

    expect(User::roleOptions())->toHaveKey('finance_lead', 'Finance Lead');

    $user = User::factory()->create(['role' => 'finance_lead']);

    expect($user->isAdmin())->toBeTrue();
    expect($user->canManageUsers())->toBeTrue();
});

it('reflects a permission change on an existing role immediately, without touching users', function () {
    $manager = User::factory()->create(['role' => 'manager']);

    expect($manager->canManageUsers())->toBeFalse();

    Role::where('slug', 'manager')->update(['permissions' => [Permission::ACCESS_ADMIN, Permission::MANAGE_USERS]]);

    expect($manager->fresh()->canManageUsers())->toBeTrue();
});

it('has all 19 module-level permission keys in the catalog', function () {
    expect(Permission::catalog())->toHaveKeys([
        Permission::ACCESS_ADMIN,
        Permission::VIEW_USERS, Permission::MANAGE_USERS,
        Permission::VIEW_CONTENT, Permission::MANAGE_CONTENT,
        Permission::VIEW_MEDIA, Permission::MANAGE_MEDIA,
        Permission::VIEW_CLIENTS, Permission::MANAGE_CLIENTS,
        Permission::VIEW_PROPOSALS, Permission::MANAGE_PROPOSALS,
        Permission::VIEW_PROJECTS, Permission::MANAGE_PROJECTS,
        Permission::VIEW_FINANCE, Permission::MANAGE_FINANCE,
        Permission::VIEW_SETTINGS, Permission::MANAGE_SETTINGS,
        Permission::VIEW_CHAT, Permission::MANAGE_CHAT,
    ]);
    expect(Permission::catalog())->toHaveCount(19);
});

it('assigns the expanded default permission sets to the five seeded roles', function () {
    $permissionsOf = fn (string $slug) => Role::where('slug', $slug)->value('permissions');

    expect($permissionsOf('super_admin'))->toEqualCanonicalizing([
        Permission::ACCESS_ADMIN, Permission::MANAGE_USERS, Permission::MANAGE_CONTENT,
        Permission::MANAGE_MEDIA, Permission::MANAGE_CLIENTS, Permission::MANAGE_PROPOSALS,
        Permission::MANAGE_PROJECTS, Permission::MANAGE_FINANCE, Permission::MANAGE_SETTINGS,
        Permission::MANAGE_CHAT,
    ]);

    expect($permissionsOf('commercial_owner'))->toEqualCanonicalizing([
        Permission::ACCESS_ADMIN, Permission::MANAGE_CLIENTS, Permission::MANAGE_PROPOSALS,
        Permission::MANAGE_FINANCE, Permission::MANAGE_PROJECTS, Permission::MANAGE_CHAT,
    ]);

    expect($permissionsOf('client_lead'))->toEqualCanonicalizing([
        Permission::ACCESS_ADMIN, Permission::MANAGE_CLIENTS, Permission::MANAGE_PROJECTS,
    ]);

    expect($permissionsOf('manager'))->toEqualCanonicalizing([
        Permission::ACCESS_ADMIN, Permission::MANAGE_CONTENT, Permission::MANAGE_MEDIA,
        Permission::MANAGE_CLIENTS, Permission::MANAGE_PROJECTS,
    ]);

    expect($permissionsOf('editor'))->toEqualCanonicalizing([
        Permission::ACCESS_ADMIN, Permission::MANAGE_CONTENT, Permission::MANAGE_MEDIA,
    ]);
});

it('lets a view-only permission read but not write, while manage grants both', function () {
    Role::create([
        'slug' => 'clients_viewer',
        'label' => 'Clients Viewer',
        'sort_order' => 50,
        'permissions' => [Permission::ACCESS_ADMIN, Permission::VIEW_CLIENTS],
    ]);
    $viewer = User::factory()->create(['role' => 'clients_viewer']);

    expect($viewer->hasAnyPermission(Permission::VIEW_CLIENTS, Permission::MANAGE_CLIENTS))->toBeTrue();
    expect($viewer->hasPermission(Permission::MANAGE_CLIENTS))->toBeFalse();

    Role::create([
        'slug' => 'clients_manager',
        'label' => 'Clients Manager',
        'sort_order' => 51,
        'permissions' => [Permission::ACCESS_ADMIN, Permission::MANAGE_CLIENTS],
    ]);
    $manager = User::factory()->create(['role' => 'clients_manager']);

    expect($manager->hasAnyPermission(Permission::VIEW_CLIENTS, Permission::MANAGE_CLIENTS))->toBeTrue();
    expect($manager->hasPermission(Permission::MANAGE_CLIENTS))->toBeTrue();
});
