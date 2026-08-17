<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalConversionService;

function convertibleClient(): Client
{
    return Client::create(['name' => 'Elias Kiptoo', 'email' => 'elias@example.com', 'status' => 'active']);
}

it('refuses to convert a proposal that is not won', function () {
    $proposal = Proposal::create([
        'client_id' => convertibleClient()->id,
        'title' => 'Pending Proposal',
        'status' => 'sent',
    ]);

    (new ProposalConversionService)->convert($proposal);
})->throws(RuntimeException::class, 'Only a proposal marked "won" can be converted into a project.');

it('refuses to convert a proposal with no client', function () {
    $proposal = Proposal::create([
        'title' => 'Orphan Proposal',
        'status' => 'won',
    ]);

    (new ProposalConversionService)->convert($proposal);
})->throws(RuntimeException::class, 'This proposal has no client and cannot be converted into a project.');

it('refuses to convert a proposal that already has a project', function () {
    $client = convertibleClient();

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'title' => 'Already Converted',
        'status' => 'won',
    ]);

    (new ProposalConversionService)->convert($proposal);

    (new ProposalConversionService)->convert($proposal->fresh());
})->throws(RuntimeException::class, 'This proposal has already been converted into a project.');

it('converts a won proposal into a planned project linked back to the proposal', function () {
    $client = convertibleClient();
    $actor = User::factory()->superAdmin()->create();

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'title' => 'Lakeview Residence Visualization',
        'status' => 'won',
        'summary' => 'Full residential archviz package.',
        'body' => 'Detailed scope of work.',
    ]);

    $project = (new ProposalConversionService)->convert($proposal, $actor);

    expect($project->exists)->toBeTrue();
    expect($project->client_id)->toBe($client->id);
    expect($project->proposal_id)->toBe($proposal->id);
    expect($project->created_by)->toBe($actor->id);
    expect($project->title)->toBe('Lakeview Residence Visualization');
    expect($project->status)->toBe('planned');
    expect($project->summary)->toBe('Full residential archviz package.');

    expect($proposal->projects()->count())->toBe(1);
    expect($project->proposal->is($proposal))->toBeTrue();
});

it('links proposal, project, and finance documents together end to end', function () {
    $client = convertibleClient();

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'title' => 'Riverside Office Fitout',
        'status' => 'won',
        'value' => 500000,
    ]);

    $project = (new ProposalConversionService)->convert($proposal);

    $invoice = FinanceDocument::create([
        'client_id' => $client->id,
        'proposal_id' => $proposal->id,
        'project_id' => $project->id,
        'type' => 'invoice',
        'reference_number' => 'INV-LINKAGE-001',
        'issue_date' => now(),
        'total_amount' => 250000,
    ]);

    expect($project->financeDocuments()->first()->is($invoice))->toBeTrue();
    expect($invoice->proposal->is($proposal))->toBeTrue();
    expect($invoice->project->is($project))->toBeTrue();
    expect($proposal->financeDocuments()->first()->is($invoice))->toBeTrue();
    expect($client->projects()->first()->is($project))->toBeTrue();
});
