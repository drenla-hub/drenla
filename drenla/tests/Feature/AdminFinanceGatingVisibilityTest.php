<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;

it('shows a payment gate warning on a finance document linked to a project with a blocked milestone', function () {
    $admin = User::factory()->superAdmin()->create();

    $client = Client::create([
        'name' => 'Odhiambo Achieng',
        'email' => 'odhiambo@example.com',
        'status' => 'active',
    ]);

    $project = Project::create([
        'client_id' => $client->id,
        'title' => 'Coastal Retreat Delivery',
        'slug' => 'coastal-retreat-delivery',
        'status' => 'active',
    ]);

    ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Foundation approval',
        'status' => 'ready_for_payment',
        'payment_required' => true,
        'payment_required_amount' => 300000,
        'payment_status' => 'invoiced',
    ]);

    $document = FinanceDocument::create([
        'reference_number' => 'INV-'.$client->id.'-'.uniqid(),
        'client_id' => $client->id,
        'project_id' => $project->id,
        'type' => 'invoice',
        'status' => 'sent',
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.finance.edit', $document))
        ->assertOk()
        ->assertSee('payment-gated milestones')
        ->assertSee('Foundation approval');
});

it('does not show a payment gate warning for a finance document with no blocked milestones', function () {
    $admin = User::factory()->superAdmin()->create();

    $client = Client::create([
        'name' => 'Odhiambo Achieng',
        'email' => 'odhiambo2@example.com',
        'status' => 'active',
    ]);

    $document = FinanceDocument::create([
        'reference_number' => 'INV-'.$client->id.'-'.uniqid(),
        'client_id' => $client->id,
        'type' => 'invoice',
        'status' => 'sent',
        'currency' => 'KES',
        'issue_date' => now()->toDateString(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.finance.edit', $document))
        ->assertOk()
        ->assertDontSee('payment-gated milestones');
});
