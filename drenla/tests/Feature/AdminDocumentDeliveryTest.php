<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\DocumentDeliveryService;

it('sends a finance document to the client', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Wanjiku Kamau',
        'email' => 'wanjiku@example.com',
        'status' => 'active',
        'portal_access_enabled' => true,
    ]);

    $document = FinanceDocument::create([
        'reference_number' => 'INV-SEND-0001',
        'client_id' => $client->id,
        'type' => 'invoice',
        'status' => 'sent',
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
    ]);

    $this->mock(DocumentDeliveryService::class, function ($mock) {
        $mock->shouldReceive('sendFinanceDocument')->once();
    });

    $this->actingAs($admin)
        ->post(route('admin.finance.send', $document))
        ->assertRedirect()
        ->assertSessionHas('status');
});

it('flashes an error when finance document delivery fails', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Wanjiku Kamau',
        'email' => 'wanjiku2@example.com',
        'status' => 'active',
    ]);

    $document = FinanceDocument::create([
        'reference_number' => 'INV-SEND-0002',
        'client_id' => $client->id,
        'type' => 'invoice',
        'status' => 'sent',
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
    ]);

    $this->mock(DocumentDeliveryService::class, function ($mock) {
        $mock->shouldReceive('sendFinanceDocument')
            ->once()
            ->andThrow(new RuntimeException('Enable portal access for this client before sending them a document.'));
    });

    $this->actingAs($admin)
        ->post(route('admin.finance.send', $document))
        ->assertRedirect()
        ->assertSessionHasErrors('send');
});

it('sends a proposal to the client', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Wanjiku Kamau',
        'email' => 'wanjiku3@example.com',
        'status' => 'active',
        'portal_access_enabled' => true,
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'title' => 'Riverside Brand System',
        'slug' => 'riverside-brand-system',
        'status' => 'draft',
        'document_status' => 'approved',
        'is_client_visible' => true,
    ]);

    $this->mock(DocumentDeliveryService::class, function ($mock) {
        $mock->shouldReceive('sendProposal')->once();
    });

    $this->actingAs($admin)
        ->post(route('admin.proposals.send', $proposal))
        ->assertRedirect()
        ->assertSessionHas('status');
});
