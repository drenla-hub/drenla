<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\User;

it('creates a statement document with transactions and next-due fields through the real form', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Mercy Cheru',
        'email' => 'mercy-statement-form@example.com',
        'status' => 'active',
    ]);

    $payload = [
        'client_id' => $client->id,
        'type' => 'statement',
        'status' => 'sent',
        'reference_number' => 'DNR-MAY-004',
        'currency' => 'KES',
        'issue_date' => '2026-05-20',
        'next_due_label' => 'PHASE 3 - INV #DNR-SEP-2025 - DUE PAYMENT',
        'next_due_amount' => 116000,
        'transactions' => [
            ['transaction_date' => '2025-09-13', 'label' => 'INV #DNR-SEP-2025', 'type' => 'invoice', 'amount' => 580000],
            ['transaction_date' => '2025-10-15', 'label' => 'PMNT #0001', 'type' => 'payment', 'amount' => 174000],
        ],
    ];

    $this->actingAs($user)
        ->post(route('admin.finance.store'), $payload)
        ->assertRedirect();

    $document = FinanceDocument::where('client_id', $client->id)->firstOrFail();

    expect($document->type)->toBe('statement');
    expect($document->next_due_label)->toBe('PHASE 3 - INV #DNR-SEP-2025 - DUE PAYMENT');
    expect((float) $document->next_due_amount)->toBe(116000.0);
    expect($document->transactions)->toHaveCount(2);
    expect($document->transactions->first()->label)->toBe('INV #DNR-SEP-2025');
});

it('renders the statement print template with the ledger and aging summary', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Mercy Cheru',
        'email' => 'mercy-statement-preview@example.com',
        'status' => 'active',
    ]);

    $document = FinanceDocument::create([
        'client_id' => $client->id,
        'type' => 'statement',
        'reference_number' => 'DNR-MAY-004',
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
        'next_due_label' => 'PHASE 3 - INV #DNR-SEP-2025 - DUE PAYMENT',
        'next_due_amount' => 116000,
    ]);
    $document->transactions()->create(['transaction_date' => '2025-09-13', 'label' => 'INV #DNR-SEP-2025', 'type' => 'invoice', 'amount' => 580000, 'sort_order' => 0]);
    $document->transactions()->create(['transaction_date' => '2025-10-15', 'label' => 'PMNT #0001', 'type' => 'payment', 'amount' => 174000, 'sort_order' => 1]);

    $response = $this->actingAs($user)
        ->get(route('admin.finance.preview', $document))
        ->assertOk();

    $response->assertSee('STATEMENT');
    $response->assertSeeInOrder(['13-Sep-2025', 'INV #DNR-SEP-2025', '580,000.00']);
    $response->assertSeeInOrder(['15-Oct-2025', 'PMNT #0001', '30%', '406,000.00']);
    // "Current" is upper-cased visually via CSS text-transform, not in the HTML source.
    $response->assertSee('Current');
    $response->assertSee('PHASE 3 - INV #DNR-SEP-2025 - DUE PAYMENT');
    $response->assertSee('Due Amount: 116,000.00');
});

it('does not render the line-items table for a statement document', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Baraka Otieno',
        'email' => 'baraka-statement@example.com',
        'status' => 'active',
    ]);

    $document = FinanceDocument::create([
        'client_id' => $client->id,
        'type' => 'statement',
        'reference_number' => 'DNR-STMT-002',
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.finance.preview', $document))
        ->assertOk();

    $response->assertDontSee('Item &amp; Description', false);
    $response->assertSee('No transactions recorded yet.');
});
