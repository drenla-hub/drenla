<?php

use App\Models\User;

it('redirects guests away from the admin dashboard', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('prevents non admin users from accessing the admin dashboard', function () {
    $user = User::factory()->create(['role' => 'client']);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('allows admin users to access the admin dashboard', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Control Plane');
});
