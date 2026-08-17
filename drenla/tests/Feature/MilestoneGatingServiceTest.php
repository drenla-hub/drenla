<?php

use App\Exceptions\MilestoneBlockedException;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Services\MilestoneGatingService;

function gatingProject(): Project
{
    $client = Client::create(['name' => 'Amaya Studio', 'email' => 'amaya@example.com', 'status' => 'active']);

    return Project::create([
        'client_id' => $client->id,
        'title' => 'Coastal Villa Fitout',
        'status' => 'active',
    ]);
}

it('is not blocked when payment is not required', function () {
    $milestone = ProjectMilestone::create([
        'project_id' => gatingProject()->id,
        'title' => 'Concept',
        'payment_required' => false,
        'payment_status' => 'not_applicable',
    ]);

    expect((new MilestoneGatingService)->isBlocked($milestone))->toBeFalse();
});

it('is blocked when payment is required and unpaid', function () {
    $milestone = ProjectMilestone::create([
        'project_id' => gatingProject()->id,
        'title' => 'Design Development',
        'payment_required' => true,
        'payment_status' => 'invoiced',
    ]);

    $service = new MilestoneGatingService;

    expect($service->isBlocked($milestone))->toBeTrue();
    expect($service->blockingReason($milestone))->toContain('invoiced');
});

it('is unblocked once payment status is paid', function () {
    $service = new MilestoneGatingService;
    $project = gatingProject();

    $paid = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Paid stage',
        'payment_required' => true,
        'payment_status' => 'paid',
    ]);

    expect($service->isBlocked($paid))->toBeFalse();
});

it('throws when completing a blocked milestone', function () {
    $milestone = ProjectMilestone::create([
        'project_id' => gatingProject()->id,
        'title' => 'Fit-out',
        'payment_required' => true,
        'payment_status' => 'pending',
    ]);

    (new MilestoneGatingService)->assertCanComplete($milestone);
})->throws(MilestoneBlockedException::class);

it('throws when completing a task under a blocked milestone', function () {
    $project = gatingProject();

    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Fit-out',
        'payment_required' => true,
        'payment_status' => 'pending',
    ]);

    $task = ProjectTask::create([
        'project_id' => $project->id,
        'project_milestone_id' => $milestone->id,
        'title' => 'Install cabinetry',
        'status' => 'in_progress',
    ]);

    (new MilestoneGatingService)->assertCanCompleteTask($task);
})->throws(MilestoneBlockedException::class);

it('allows completing a task whose milestone is not blocked', function () {
    $project = gatingProject();

    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Handover',
        'payment_required' => true,
        'payment_status' => 'paid',
    ]);

    $task = ProjectTask::create([
        'project_id' => $project->id,
        'project_milestone_id' => $milestone->id,
        'title' => 'Final walkthrough',
        'status' => 'in_progress',
    ]);

    (new MilestoneGatingService)->assertCanCompleteTask($task);
})->throwsNoExceptions();

it('stops portal visibility at the first blocked milestone', function () {
    $project = gatingProject();

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

    $views = (new MilestoneGatingService)->visibleMilestonesFor($project->fresh());

    expect($views)->toHaveCount(2);
    expect($views->get(0)->milestone->id)->toBe($unlocked->id);
    expect($views->get(0)->isBlocked)->toBeFalse();
    expect($views->get(0)->tasksVisible)->toBeTrue();
    expect($views->get(1)->milestone->id)->toBe($blocked->id);
    expect($views->get(1)->isBlocked)->toBeTrue();
    expect($views->get(1)->tasksVisible)->toBeFalse();
});
