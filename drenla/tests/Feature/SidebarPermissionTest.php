<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Permission;

it('only shows overview items for an admin with no module permissions', function () {
    $role = Role::create([
        'slug' => 'overview_only',
        'label' => 'Overview Only',
        'sort_order' => 990,
        'permissions' => [Permission::ACCESS_ADMIN],
    ]);
    $user = User::factory()->create(['role' => $role->slug]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Dashboard');
    $response->assertSee('Journal');
    $response->assertDontSee('Project Briefs');
    $response->assertDontSee('Finance');
    $response->assertDontSee('Media');
    $response->assertDontSee('Staff');
    $response->assertDontSee('Roles');
    $response->assertDontSee('Settings');
});

it('shows content and media items to a user with content and media permissions', function () {
    $role = Role::create([
        'slug' => 'content_editor',
        'label' => 'Content Editor',
        'sort_order' => 991,
        'permissions' => [Permission::ACCESS_ADMIN, Permission::VIEW_CONTENT, Permission::VIEW_MEDIA],
    ]);
    $user = User::factory()->create(['role' => $role->slug]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Homepage');
    $response->assertSee('Case Studies');
    $response->assertSee('Articles');
    $response->assertSee('Focus Areas');
    $response->assertSee('Media');
    $response->assertDontSee('Staff');
    $response->assertDontSee('Roles');
    $response->assertDontSee('Finance');
});

it('shows all navigation groups to super admin', function () {
    $user = User::factory()->superAdmin()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Dashboard');
    $response->assertSee('Journal');
    $response->assertSee('Clients');
    $response->assertSee('Finance');
    $response->assertSee('Conversations');
    $response->assertSee('Project Briefs');
    $response->assertSee('Projects');
    $response->assertSee('Inquiries');
    $response->assertSee('Staff');
    $response->assertSee('Roles');
    $response->assertSee('Settings');
});
