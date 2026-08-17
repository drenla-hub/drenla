<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\MilestoneBlockedException;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\Proposal;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\MilestoneGatingService;
use App\Support\ProjectTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function __construct(private readonly MilestoneGatingService $gating) {}

    public function index(): View
    {
        $projects = Project::with(['client', 'proposal', 'financeDocuments'])->latest()->get();

        return view('admin.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('admin.projects.form', [
            'project' => new Project,
            'clients' => Client::orderBy('name')->get(),
            'proposals' => Proposal::orderBy('title')->get(),
            'financeDocuments' => $this->projectInvoiceOptions(),
            'linkedFinanceDocumentId' => old('finance_document_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = Project::create($this->validatedProjectData($request) + [
            'created_by' => $request->user()->id,
        ]);

        $this->syncLinkedFinanceDocument($project, $request->input('finance_document_id'));

        return redirect()->route('admin.projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        $project->load(['client', 'proposal', 'milestones', 'financeDocuments']);

        $users = User::orderBy('name')->get();

        extract(ProjectTimeline::build($project));

        $milestoneGating = $milestones->mapWithKeys(fn (ProjectMilestone $milestone) => [
            $milestone->id => [
                'is_blocked' => $this->gating->isBlocked($milestone),
                'reason' => $this->gating->blockingReason($milestone),
            ],
        ]);

        return view('admin.projects.show', compact(
            'project', 'users',
            'tasks', 'milestones', 'weeks', 'weekKeys',
            'rows', 'paymentMarkers', 'todayIdx', 'colWidth', 'stats',
            'milestoneGating'
        ));
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.form', [
            'project' => $project,
            'clients' => Client::orderBy('name')->get(),
            'proposals' => Proposal::orderBy('title')->get(),
            'financeDocuments' => $this->projectInvoiceOptions($project),
            'linkedFinanceDocumentId' => old('finance_document_id', $project->financeDocuments()->where('type', 'invoice')->orderByDesc('issue_date')->value('id')),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validatedProjectData($request));
        $this->syncLinkedFinanceDocument($project, $request->input('finance_document_id'));

        return redirect()->route('admin.projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('admin.projects.index')->with('status', 'Project deleted.');
    }

    public function storeMilestone(Request $request, Project $project): RedirectResponse
    {
        $project->milestones()->create($this->validatedMilestoneData($request));

        return back()->with('status', 'Milestone added.');
    }

    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse|JsonResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);

        $data = $this->validatedMilestoneData($request);

        if (($data['status'] ?? null) === 'completed') {
            // Evaluate gating against the *incoming* state (a single request can both
            // clear the payment gate and complete the milestone) rather than the
            // stale in-memory model.
            $prospective = (clone $milestone)->forceFill($data);

            try {
                $this->gating->assertCanComplete($prospective);
            } catch (MilestoneBlockedException $exception) {
                if ($request->wantsJson()) {
                    return response()->json(['message' => $exception->getMessage()], 422);
                }

                return back()->withErrors(['status' => $exception->getMessage()])->withInput();
            }
        }

        $milestone->update($data);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'Milestone updated.']);
        }

        return back()->with('status', 'Milestone updated.');
    }

    public function storeTask(Request $request, Project $project): RedirectResponse
    {
        $project->tasks()->create($this->validatedTaskData($request));

        $this->refreshProgress($project);

        return back()->with('status', 'Task added.');
    }

    public function updateTask(Request $request, Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $data = $this->validatedTaskData($request);

        if (($data['status'] ?? null) === 'done') {
            try {
                $this->gating->assertCanCompleteTask($task);
            } catch (MilestoneBlockedException $exception) {
                return back()->withErrors(['status' => $exception->getMessage()])->withInput();
            }
        }

        $data['completed_at'] = ($data['status'] ?? null) === 'done' ? now() : null;

        $task->update($data);
        $this->refreshProgress($project);

        return back()->with('status', 'Task updated.');
    }

    public function updateTaskTimeline(Request $request, Project $project, ProjectTask $task): JsonResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
        ]);

        $startDate = $data['start_date'] ?? $task->start_date?->toDateString();

        if ($startDate && $data['due_date'] < $startDate) {
            return response()->json([
                'message' => 'Due date cannot be earlier than the task start date.',
            ], 422);
        }

        $task->update(array_filter([
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'],
        ], fn ($value) => $value !== null));

        return response()->json([
            'id' => $task->id,
            'start_date' => $task->fresh()->start_date?->toDateString(),
            'due_date' => $task->fresh()->due_date?->toDateString(),
        ]);
    }

    public function destroyTask(Project $project, ProjectTask $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $task->delete();
        $this->refreshProgress($project);

        return back()->with('status', 'Task deleted.');
    }

    public function taskComments(Project $project, ProjectTask $task): JsonResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        return response()->json(
            $task->comments()->with('user')->orderByDesc('id')->get()->map(fn (TaskComment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'author' => $comment->user?->name ?? $comment->client_name ?? 'Unknown',
                'is_client' => $comment->user_id === null,
                'created_at' => $comment->created_at->toISOString(),
            ])
        );
    }

    public function storeTaskComment(Request $request, Project $project, ProjectTask $task): JsonResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json([
            'id' => $comment->id,
            'body' => $comment->body,
            'author' => $request->user()->name,
            'is_client' => false,
            'created_at' => $comment->created_at->toISOString(),
        ], 201);
    }

    private function validatedProjectData(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'proposal_id' => ['nullable', 'exists:proposals,id'],
            'finance_document_id' => ['nullable', 'exists:finance_documents,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:projects,slug,'.($request->route('project')?->id ?? 'null')],
            'status' => ['required', 'in:planned,active,blocked,on_hold,completed'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);
    }

    private function projectInvoiceOptions(?Project $project = null)
    {
        $query = FinanceDocument::with(['client', 'project'])
            ->where('type', 'invoice')
            ->orderByDesc('issue_date');

        if ($project) {
            $query->where(function ($inner) use ($project) {
                $inner->whereNull('project_id')->orWhere('project_id', $project->id);
            });
        } else {
            $query->whereNull('project_id');
        }

        return $query->get();
    }

    private function syncLinkedFinanceDocument(Project $project, mixed $financeDocumentId): void
    {
        $project->financeDocuments()
            ->where('type', 'invoice')
            ->update(['project_id' => null]);

        if (! $financeDocumentId) {
            return;
        }

        $document = FinanceDocument::query()->find($financeDocumentId);
        if (! $document || $document->type !== 'invoice') {
            return;
        }

        $document->update([
            'project_id' => $project->id,
            'client_id' => $project->client_id,
            'proposal_id' => $project->proposal_id ?: $document->proposal_id,
        ]);
    }

    private function validatedMilestoneData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:planned,in_progress,blocked,ready_for_payment,paid,released,completed'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'payment_required' => ['nullable', 'boolean'],
            'payment_required_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:not_applicable,pending,invoiced,paid'],
            'blocked_reason' => ['nullable', 'string'],
        ]) + [
            'payment_required' => $request->boolean('payment_required'),
        ];
    }

    private function validatedTaskData(Request $request): array
    {
        return $request->validate([
            'project_milestone_id' => ['nullable', 'exists:project_milestones,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:todo,in_progress,review,done,blocked'],
            'priority' => ['required', 'in:low,medium,high'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function refreshProgress(Project $project): void
    {
        $total = $project->tasks()->count();
        $done = $project->tasks()->where('status', 'done')->count();

        $project->update([
            'progress_percentage' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
        ]);
    }
}
