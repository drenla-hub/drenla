<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalRenderService;
use App\Support\ProposalDocumentData;

it('links a quotation to a proposal from the proposal update flow and syncs value and quotation page', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create(['name' => 'Acme Corp', 'status' => 'active']);

    $proposal = Proposal::create([
        'title' => 'Brand Identity Proposal',
        'client_id' => $client->id,
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => ProposalDocumentData::TEMPLATE_KEY,
        'template_version' => ProposalDocumentData::TEMPLATE_VERSION,
        'document_data' => ProposalDocumentData::defaults(),
    ]);

    $quotation = FinanceDocument::create([
        'client_id' => $client->id,
        'type' => 'quotation',
        'status' => 'draft',
        'reference_number' => 'QUO-ACME-001',
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
        'subtotal' => 500000,
        'tax_amount' => 80000,
        'total_amount' => 580000,
    ]);

    $this->actingAs($user)->put(route('admin.proposals.update', $proposal), [
        'title' => 'Brand Identity Proposal',
        'client_id' => $client->id,
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => ProposalDocumentData::TEMPLATE_KEY,
        'finance_document_id' => $quotation->id,
        'document' => [
            'header' => [
                'document_label' => 'Project Brief',
                'client_label' => 'Client',
                'scope_label' => 'Scope',
                'reference_label' => 'Ref',
                'date_label' => 'Date',
                'client_name' => 'Acme Corp',
                'scope' => "Line 1\nLine 2",
            ],
            'hero' => [
                'title' => 'Brand Identity',
                'subtitle' => 'Comprehensive Brief',
            ],
            'intro' => [
                'section_label' => 'Project Description',
                'left_heading' => 'Brief',
                'right_heading' => 'Key Objective',
                'left_body' => 'Left content',
                'right_body' => 'Right content',
            ],
            'sections' => [],
        ],
    ])->assertRedirect(route('admin.proposals.edit', $proposal));

    expect($proposal->fresh()->value)->toBe('580000.00');
    expect($quotation->fresh()->proposal_id)->toBe($proposal->id);

    $render = app(ProposalRenderService::class)->build($proposal->fresh());
    expect($render->hasQuotation())->toBeTrue();
    expect($render->pages[count($render->pages) - 1]['kind'])->toBe('quotation');
});

it('syncs proposal value when finance document is created with proposal_id', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create(['name' => 'Nexus Inc', 'status' => 'active']);

    $proposal = Proposal::create([
        'title' => 'Nexus Visualization',
        'client_id' => $client->id,
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => ProposalDocumentData::TEMPLATE_KEY,
        'template_version' => ProposalDocumentData::TEMPLATE_VERSION,
        'document_data' => ProposalDocumentData::defaults(),
    ]);

    $this->actingAs($user)->post(route('admin.finance.store'), [
        'client_id' => $client->id,
        'proposal_id' => $proposal->id,
        'type' => 'quotation',
        'status' => 'draft',
        'reference_number' => 'QUO-NEXUS-001',
        'currency' => 'USD',
        'issue_date' => now()->toDateString(),
        'items' => [
            ['title' => 'Exterior Renderings', 'quantity' => 2, 'unit_price' => 1500],
            ['title' => 'Interior Views', 'quantity' => 4, 'unit_price' => 800],
        ],
    ])->assertRedirect();

    $proposal->refresh();
    expect((float) $proposal->value)->toBe(6200.0);
});
