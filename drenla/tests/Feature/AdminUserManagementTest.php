<?php

use App\Models\User;

it('renders the new staff user form', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.create'))
        ->assertOk()
        ->assertSee('New staff user');
});

it('creates a staff user with a role and password', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Imani Njeri',
            'email' => 'imani@example.com',
            'role' => 'editor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'imani@example.com')->firstOrFail();
    expect($user->role)->toBe('editor');
});

it('updates a staff user without changing the password when left blank', function () {
    $admin = User::factory()->superAdmin()->create();
    $staff = User::factory()->create(['role' => 'editor', 'email' => 'staff@example.com']);
    $originalPassword = $staff->password;

    $this->actingAs($admin)
        ->put(route('admin.users.update', $staff), [
            'name' => 'Updated Name',
            'email' => 'staff@example.com',
            'role' => 'manager',
        ])
        ->assertRedirect(route('admin.users.index'));

    $staff->refresh();
    expect($staff->name)->toBe('Updated Name');
    expect($staff->role)->toBe('manager');
    expect($staff->password)->toBe($originalPassword);
});
