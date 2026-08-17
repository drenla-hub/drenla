<?php

use App\Models\Client;

function enabledClient(array $overrides = []): Client
{
    return Client::create(array_merge([
        'name' => 'Nadia Okoth',
        'email' => 'nadia@example.com',
        'status' => 'active',
        'portal_access_enabled' => true,
        'portal_access_token' => 'valid-token-123',
    ], $overrides));
}

it('redirects guests away from the portal dashboard', function () {
    $this->get('/portal')->assertRedirect(route('portal.login'));
});

it('logs a client in via the magic access link and redirects to the dashboard', function () {
    $client = enabledClient();

    $this->get(route('portal.access', $client->portal_access_token))
        ->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($client, 'client');
});

it('rejects an invalid access token', function () {
    enabledClient();

    $this->get(route('portal.access', 'not-a-real-token'))
        ->assertRedirect(route('portal.login'));

    $this->assertGuest('client');
});

it('rejects the access link when portal access is disabled', function () {
    $client = enabledClient(['portal_access_enabled' => false]);

    $this->get(route('portal.access', $client->portal_access_token))
        ->assertRedirect(route('portal.login'));

    $this->assertGuest('client');
});

it('allows manual token entry as a fallback login', function () {
    $client = enabledClient();

    $this->post(route('portal.login.store'), ['token' => $client->portal_access_token])
        ->assertRedirect(route('portal.dashboard'));

    $this->assertAuthenticatedAs($client, 'client');
});

it('logs the client out', function () {
    $client = enabledClient();

    $this->actingAs($client, 'client')
        ->post(route('portal.logout'))
        ->assertRedirect(route('portal.login'));

    $this->assertGuest('client');
});

it('revokes access mid-session the moment portal_access_enabled is turned off', function () {
    $client = enabledClient();

    $this->actingAs($client, 'client')
        ->get(route('portal.dashboard'))
        ->assertOk();

    $client->update(['portal_access_enabled' => false]);

    $this->actingAs($client, 'client')
        ->get(route('portal.dashboard'))
        ->assertForbidden();
});

it('renders the dashboard for an authenticated client', function () {
    $client = enabledClient();

    $this->actingAs($client, 'client')
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertSee($client->name);
});
