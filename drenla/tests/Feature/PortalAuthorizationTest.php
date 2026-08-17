<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\Proposal;
use App\Models\User;

function portalClient(string $email): Client
{
    return Client::create([
        'name' => 'Client '.$email,
        'email' => $email,
        'status' => 'active',
        'portal_access_enabled' => true,
        'portal_access_token' => 'token-'.$email,
    ]);
}

it('prevents a client from viewing another client\'s project', function () {
    $owner = portalClient('owner@example.com');
    $other = portalClient('other@example.com');

    $project = Project::create([
        'client_id' => $owner->id,
        'title' => 'Owner Project',
        'status' => 'active',
    ]);

    $this->actingAs($other, 'client')
        ->get(route('portal.projects.show', $project))
        ->assertNotFound();

    $this->actingAs($owner, 'client')
        ->get(route('portal.projects.show', $project))
        ->assertOk();
});

it('prevents a client from viewing another client\'s proposal', function () {
    $owner = portalClient('owner2@example.com');
    $other = portalClient('other2@example.com');

    $proposal = Proposal::create([
        'client_id' => $owner->id,
        'title' => 'Owner Proposal',
        'is_client_visible' => true,
    ]);

    $this->actingAs($other, 'client')
        ->get(route('portal.proposals.show', $proposal))
        ->assertNotFound();

    $this->actingAs($owner, 'client')
        ->get(route('portal.proposals.show', $proposal))
        ->assertOk();
});

it('hides a proposal from its own client until it is marked client-visible', function () {
    $owner = portalClient('owner3@example.com');

    $proposal = Proposal::create([
        'client_id' => $owner->id,
        'title' => 'Draft Proposal',
        'is_client_visible' => false,
    ]);

    $this->actingAs($owner, 'client')
        ->get(route('portal.proposals.show', $proposal))
        ->assertNotFound();
});

it('prevents a client from viewing another client\'s finance document', function () {
    $owner = portalClient('owner4@example.com');
    $other = portalClient('other4@example.com');

    $document = FinanceDocument::create([
        'client_id' => $owner->id,
        'type' => 'invoice',
        'reference_number' => 'INV-TEST-001',
        'issue_date' => now(),
    ]);

    $this->actingAs($other, 'client')
        ->get(route('portal.finance.show', $document))
        ->assertNotFound();

    $this->actingAs($owner, 'client')
        ->get(route('portal.finance.show', $document))
        ->assertOk();
});

it('only lists the authenticated client\'s own finance documents', function () {
    $owner = portalClient('owner5@example.com');
    $other = portalClient('other5@example.com');

    FinanceDocument::create([
        'client_id' => $owner->id,
        'type' => 'invoice',
        'reference_number' => 'INV-TEST-002',
        'issue_date' => now(),
    ]);

    FinanceDocument::create([
        'client_id' => $other->id,
        'type' => 'invoice',
        'reference_number' => 'INV-TEST-003',
        'issue_date' => now(),
    ]);

    $this->actingAs($owner, 'client')
        ->get(route('portal.finance.index'))
        ->assertOk()
        ->assertSee('INV-TEST-002')
        ->assertDontSee('INV-TEST-003');
});

it('hides a blocked milestone\'s tasks and everything after it from the client', function () {
    $client = portalClient('gated@example.com');

    $project = Project::create([
        'client_id' => $client->id,
        'title' => 'Gated Project',
        'status' => 'active',
    ]);

    $unlocked = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Concept',
        'sort_order' => 1,
        'payment_required' => false,
        'payment_status' => 'not_applicable',
    ]);

    $blocked = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Design Development',
        'sort_order' => 2,
        'payment_required' => true,
        'payment_status' => 'invoiced',
    ]);

    ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Construction Docs',
        'sort_order' => 3,
        'payment_required' => true,
        'payment_status' => 'not_applicable',
    ]);

    $response = $this->actingAs($client, 'client')
        ->get(route('portal.projects.show', $project))
        ->assertOk();

    $response->assertSee('Concept');
    $response->assertSee('Design Development');
    $response->assertDontSee('Construction Docs');
});

it('only lists the authenticated client\'s own projects and proposals', function () {
    $owner = portalClient('list-owner@example.com');
    $other = portalClient('list-other@example.com');

    Project::create(['client_id' => $owner->id, 'title' => 'Owner Project Index', 'status' => 'active']);
    Project::create(['client_id' => $other->id, 'title' => 'Other Project Index', 'status' => 'active']);

    Proposal::create(['client_id' => $owner->id, 'title' => 'Owner Proposal Index', 'is_client_visible' => true]);
    Proposal::create(['client_id' => $other->id, 'title' => 'Other Proposal Index', 'is_client_visible' => true]);

    $this->actingAs($owner, 'client')
        ->get(route('portal.projects.index'))
        ->assertOk()
        ->assertSee('Owner Project Index')
        ->assertDontSee('Other Project Index');

    $this->actingAs($owner, 'client')
        ->get(route('portal.proposals.index'))
        ->assertOk()
        ->assertSee('Owner Proposal Index')
        ->assertDontSee('Other Proposal Index');
});

it('shows task progress on the project page but never leaks assignee or estimated hours', function () {
    $client = portalClient('progress@example.com');
    $staffer = User::factory()->create(['name' => 'Internal Staffer Name']);

    $project = Project::create([
        'client_id' => $client->id,
        'title' => 'Progress Project',
        'status' => 'active',
    ]);

    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Build Phase',
        'sort_order' => 1,
        'payment_required' => false,
        'payment_status' => 'not_applicable',
    ]);

    ProjectTask::create([
        'project_id' => $project->id,
        'project_milestone_id' => $milestone->id,
        'assigned_to' => $staffer->id,
        'title' => 'Client Visible Task',
        'status' => 'in_progress',
        'estimated_hours' => 42,
        'due_date' => now()->addWeek(),
    ]);

    $response = $this->actingAs($client, 'client')
        ->get(route('portal.projects.show', $project))
        ->assertOk();

    $response->assertSee('Client Visible Task');
    $response->assertDontSee('Internal Staffer Name');
    $response->assertDontSee('42');
});

it('hides a blocked milestone\'s task bars from the progress table too', function () {
    $client = portalClient('gated-tasks@example.com');

    $project = Project::create([
        'client_id' => $client->id,
        'title' => 'Gated Tasks Project',
        'status' => 'active',
    ]);

    $blocked = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Blocked Stage',
        'sort_order' => 1,
        'payment_required' => true,
        'payment_status' => 'invoiced',
    ]);

    ProjectTask::create([
        'project_id' => $project->id,
        'project_milestone_id' => $blocked->id,
        'title' => 'Hidden Task Behind Gate',
        'status' => 'todo',
        'due_date' => now()->addWeek(),
    ]);

    $this->actingAs($client, 'client')
        ->get(route('portal.projects.show', $project))
        ->assertOk()
        ->assertDontSee('Hidden Task Behind Gate');
});

it('labels a past-due unpaid milestone as overdue, never as paid', function () {
    $client = portalClient('overdue@example.com');

    $project = Project::create([
        'client_id' => $client->id,
        'title' => 'Overdue Payment Project',
        'status' => 'active',
    ]);

    ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Overdue Stage',
        'sort_order' => 1,
        'payment_required' => true,
        'payment_required_amount' => 5000,
        'payment_status' => 'invoiced',
        'due_date' => now()->subWeek(),
    ]);

    $response = $this->actingAs($client, 'client')
        ->get(route('portal.projects.show', $project))
        ->assertOk();

    $response->assertSee('overdue');
    $response->assertSee('Overdue Stage');
    $response->assertDontSee('paid', escape: false);
});
