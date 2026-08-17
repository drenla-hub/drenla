<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Permission;

it('redirects guests away from role management', function () {
    $this->get(route('admin.roles.index'))->assertRedirect('/admin/login');
});

it('prevents users without manage_users permission from accessing roles', function () {
    $editor = User::factory()->create(['role' => 'editor']);

    $this->actingAs($editor)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

it('lists existing roles for a super admin', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Super Admin');
});

it('creates a custom role and immediately grants its permissions', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'label' => 'Delivery Lead',
            'description' => 'Oversees active engagements.',
            'sort_order' => 10,
            'permissions' => [Permission::ACCESS_ADMIN],
        ])
        ->assertRedirect();

    $role = Role::where('label', 'Delivery Lead')->firstOrFail();
    expect($role->slug)->toBe('delivery_lead');
    expect($role->is_system)->toBeFalse();
    expect($role->permissions)->toBe([Permission::ACCESS_ADMIN]);

    $staff = User::factory()->create(['role' => $role->slug]);
    expect($staff->isAdmin())->toBeTrue();
    expect($staff->canManageUsers())->toBeFalse();
});

it('refuses to delete a system role', function () {
    $admin = User::factory()->superAdmin()->create();
    $role = Role::where('slug', 'editor')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertStatus(422);

    expect(Role::find($role->id))->not->toBeNull();
});

it('refuses to delete a role that still has staff assigned', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'label' => 'Site Coordinator',
            'permissions' => [],
        ]);

    $role = Role::where('label', 'Site Coordinator')->firstOrFail();
    User::factory()->create(['role' => $role->slug]);

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertStatus(422);

    expect(Role::find($role->id))->not->toBeNull();
});

it('deletes an unused custom role', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'label' => 'Temp Role',
            'permissions' => [],
        ]);

    $role = Role::where('label', 'Temp Role')->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::find($role->id))->toBeNull();
});
