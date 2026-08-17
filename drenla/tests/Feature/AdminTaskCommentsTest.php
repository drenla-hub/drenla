<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskComment;
use App\Models\User;

function createTaskForComments(): ProjectTask
{
    $client = Client::create([
        'name' => 'Zawadi Mwangi',
        'email' => 'zawadi@example.com',
        'status' => 'active',
    ]);

    $project = Project::create([
        'client_id' => $client->id,
        'title' => 'Harbourfront Signage Program',
        'slug' => 'harbourfront-signage-program',
        'status' => 'active',
    ]);

    return ProjectTask::create([
        'project_id' => $project->id,
        'title' => 'Fabricate signage prototypes',
        'status' => 'in_progress',
        'priority' => 'medium',
    ]);
}

it('redirects guests away from task comments', function () {
    $task = createTaskForComments();

    $this->getJson(route('admin.projects.tasks.comments.index', [$task->project, $task]))
        ->assertUnauthorized();
});

it('lists staff and client comments on a task in newest-first order', function () {
    $admin = User::factory()->superAdmin()->create();
    $task = createTaskForComments();

    TaskComment::create([
        'project_task_id' => $task->id,
        'user_id' => $admin->id,
        'body' => 'First comment from staff.',
    ]);

    TaskComment::create([
        'project_task_id' => $task->id,
        'client_name' => 'Zawadi Mwangi',
        'body' => 'Client follow-up question.',
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('admin.projects.tasks.comments.index', [$task->project, $task]))
        ->assertOk()
        ->json();

    expect($response)->toHaveCount(2);
    expect($response[0]['body'])->toBe('Client follow-up question.');
    expect($response[0]['is_client'])->toBeTrue();
    expect($response[1]['author'])->toBe($admin->name);
    expect($response[1]['is_client'])->toBeFalse();
});

it('lets a staff member post a comment on a task', function () {
    $admin = User::factory()->superAdmin()->create();
    $task = createTaskForComments();

    $this->actingAs($admin)
        ->postJson(route('admin.projects.tasks.comments.store', [$task->project, $task]), [
            'body' => 'Prototype approved for fabrication.',
        ])
        ->assertCreated()
        ->assertJsonPath('body', 'Prototype approved for fabrication.')
        ->assertJsonPath('is_client', false);

    expect(TaskComment::where('project_task_id', $task->id)->count())->toBe(1);
    expect(TaskComment::first()->user_id)->toBe($admin->id);
});

it('requires a body to post a task comment', function () {
    $admin = User::factory()->superAdmin()->create();
    $task = createTaskForComments();

    $this->actingAs($admin)
        ->postJson(route('admin.projects.tasks.comments.store', [$task->project, $task]), [])
        ->assertStatus(422);
});
