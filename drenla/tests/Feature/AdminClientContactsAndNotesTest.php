<?php

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\User;

it('adds a contact to a client', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create(['name' => 'Baraka Otieno', 'email' => 'baraka@example.com', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('admin.clients.contacts.store', $client), [
            'name' => 'Fatuma Ali',
            'role' => 'Finance lead',
            'email' => 'fatuma@example.com',
            'is_primary' => '1',
        ])
        ->assertRedirect(route('admin.clients.edit', $client));

    $contact = ClientContact::where('client_id', $client->id)->firstOrFail();
    expect($contact->name)->toBe('Fatuma Ali');
    expect($contact->is_primary)->toBeTrue();
});

it('removes a client contact', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create(['name' => 'Baraka Otieno', 'email' => 'baraka2@example.com', 'status' => 'active']);
    $contact = $client->contacts()->create(['name' => 'Fatuma Ali']);

    $this->actingAs($admin)
        ->delete(route('admin.clients.contacts.destroy', [$client, $contact]))
        ->assertRedirect(route('admin.clients.edit', $client));

    expect(ClientContact::find($contact->id))->toBeNull();
});

it('adds a relationship note to a client', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create(['name' => 'Baraka Otieno', 'email' => 'baraka3@example.com', 'status' => 'active']);

    $this->actingAs($admin)
        ->post(route('admin.clients.lead-notes.store', $client), [
            'body' => 'Client asked about accelerating the timeline.',
        ])
        ->assertRedirect(route('admin.clients.edit', $client));

    $note = $client->leadNotes()->firstOrFail();
    expect($note->body)->toBe('Client asked about accelerating the timeline.');
    expect($note->user_id)->toBe($admin->id);
});

it('regenerates a client portal token and invalidates the old one', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Baraka Otieno',
        'email' => 'baraka4@example.com',
        'status' => 'active',
        'portal_access_enabled' => true,
        'portal_access_token' => 'old-token-value',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.clients.regenerate-token', $client))
        ->assertRedirect(route('admin.clients.edit', $client));

    expect($client->fresh()->portal_access_token)->not->toBe('old-token-value');
});

it('refuses to regenerate a token when portal access is disabled', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Baraka Otieno',
        'email' => 'baraka5@example.com',
        'status' => 'active',
        'portal_access_enabled' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.clients.regenerate-token', $client))
        ->assertStatus(422);
});

it('shows the portal link, contacts, and notes on the client edit screen', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Baraka Otieno',
        'email' => 'baraka6@example.com',
        'status' => 'active',
        'portal_access_enabled' => true,
        'portal_access_token' => 'visible-token-value',
    ]);
    $client->contacts()->create(['name' => 'Fatuma Ali', 'role' => 'Finance lead']);
    $client->leadNotes()->create(['user_id' => $admin->id, 'body' => 'First relationship note.']);

    $this->actingAs($admin)
        ->get(route('admin.clients.edit', $client))
        ->assertOk()
        ->assertSee('Portal link')
        ->assertSee('Fatuma Ali')
        ->assertSee('First relationship note.');
});
