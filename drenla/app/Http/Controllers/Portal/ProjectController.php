<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\MilestoneGatingService;
use App\Support\ProjectTimeline;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private readonly MilestoneGatingService $gating) {}

    /**
     * List of the authenticated client's own projects.
     */
    public function index(Request $request): View
    {
        $projects = $request->user('client')->projects()->latest()->get();

        return view('portal.projects.index', compact('projects'));
    }

    /**
     * Scoped strictly to the authenticated client's own project. Milestone/task
     * visibility beyond a blocked payment gate is decided by MilestoneGatingService,
     * never re-derived here.
     */
    public function show(Request $request, Project $project): View
    {
        $client = $request->user('client');

        abort_unless($project->client_id === $client->id, 404);

        $project->load('proposal');

        $milestoneViews = $this->gating->visibleMilestonesFor($project);

        foreach ($milestoneViews as $view) {
            if ($view->tasksVisible) {
                $view->milestone->setRelation('tasks', $view->milestone->tasks()->orderBy('due_date')->get());
            }
        }

        $visibleIds = $milestoneViews->pluck('milestone.id')->all();
        $taskVisibleIds = $milestoneViews->filter->tasksVisible->pluck('milestone.id')->all();

        $timeline = ProjectTimeline::build($project, $visibleIds, $taskVisibleIds);

        return view('portal.projects.show', [
            'project' => $project,
            'milestoneViews' => $milestoneViews,
            ...$timeline,
        ]);
    }
}
