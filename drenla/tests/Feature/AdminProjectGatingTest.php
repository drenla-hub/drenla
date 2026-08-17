<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\User;

function createGatedProject(): Project
{
    $client = Client::create([
        'name' => 'Naliaka Otieno',
        'email' => 'naliaka@example.com',
        'status' => 'active',
    ]);

    return Project::create([
        'client_id' => $client->id,
        'title' => 'Riverside Pavilion Delivery',
        'slug' => 'riverside-pavilion-delivery',
        'status' => 'active',
    ]);
}

it('shows a payment gate warning on the project board for a blocked milestone', function () {
    $admin = User::factory()->superAdmin()->create();
    $project = createGatedProject();

    ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Design sign-off',
        'status' => 'ready_for_payment',
        'payment_required' => true,
        'payment_required_amount' => 400000,
        'payment_status' => 'invoiced',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.projects.show', $project))
        ->assertOk()
        ->assertSee('Payment gate')
        ->assertSee('Design sign-off');
});

it('does not show a payment gate warning once the milestone is paid', function () {
    $admin = User::factory()->superAdmin()->create();
    $project = createGatedProject();

    ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Design sign-off',
        'status' => 'ready_for_payment',
        'payment_required' => true,
        'payment_required_amount' => 400000,
        'payment_status' => 'paid',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.projects.show', $project))
        ->assertOk()
        ->assertDontSee('downstream progress blocked');
});

it('blocks completing a milestone with an unpaid required payment', function () {
    $admin = User::factory()->superAdmin()->create();
    $project = createGatedProject();

    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Design sign-off',
        'status' => 'ready_for_payment',
        'payment_required' => true,
        'payment_required_amount' => 400000,
        'payment_status' => 'invoiced',
    ]);

    $this->actingAs($admin)
        ->patchJson(route('admin.projects.milestones.update', [$project, $milestone]), [
            'title' => $milestone->title,
            'status' => 'completed',
            'payment_required' => true,
            'payment_status' => 'invoiced',
        ])
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Payment required (invoiced) before this milestone can proceed.']);

    expect($milestone->fresh()->status)->toBe('ready_for_payment');
});

it('allows completing a milestone in the same request that marks it paid', function () {
    $admin = User::factory()->superAdmin()->create();
    $project = createGatedProject();

    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Design sign-off',
        'status' => 'ready_for_payment',
        'payment_required' => true,
        'payment_required_amount' => 400000,
        'payment_status' => 'invoiced',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.projects.milestones.update', [$project, $milestone]), [
            'title' => $milestone->title,
            'status' => 'completed',
            'payment_required' => true,
            'payment_status' => 'paid',
        ])
        ->assertRedirect();

    expect($milestone->fresh()->status)->toBe('completed');
    expect($milestone->fresh()->payment_status)->toBe('paid');
});

it('blocks completing a task whose milestone is payment gated', function () {
    $admin = User::factory()->superAdmin()->create();
    $project = createGatedProject();

    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Design sign-off',
        'status' => 'ready_for_payment',
        'payment_required' => true,
        'payment_required_amount' => 400000,
        'payment_status' => 'pending',
    ]);

    $task = ProjectTask::create([
        'project_id' => $project->id,
        'project_milestone_id' => $milestone->id,
        'title' => 'Finalize drawings',
        'status' => 'in_progress',
        'priority' => 'high',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.projects.tasks.update', [$project, $task]), [
            'title' => $task->title,
            'status' => 'done',
            'priority' => 'high',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('status');

    expect($task->fresh()->status)->toBe('in_progress');
});

it('allows completing a task once its milestone gate is cleared', function () {
    $admin = User::factory()->superAdmin()->create();
    $project = createGatedProject();

    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Design sign-off',
        'status' => 'paid',
        'payment_required' => true,
        'payment_required_amount' => 400000,
        'payment_status' => 'paid',
    ]);

    $task = ProjectTask::create([
        'project_id' => $project->id,
        'project_milestone_id' => $milestone->id,
        'title' => 'Finalize drawings',
        'status' => 'in_progress',
        'priority' => 'high',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.projects.tasks.update', [$project, $task]), [
            'title' => $task->title,
            'status' => 'done',
            'priority' => 'high',
        ])
        ->assertRedirect();

    expect($task->fresh()->status)->toBe('done');
});
