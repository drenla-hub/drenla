<?php

namespace App\AiAssistant\Tools;

use App\AiAssistant\Contracts\AssistantTool;
use App\Models\Project;

class ProjectOverviewTool implements AssistantTool
{
    public function name(): string
    {
        return 'project_overview';
    }

    public function description(): string
    {
        return 'Return one project with milestone, task, and invoice summary.';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'arguments' => [
                'project_id' => 'Required integer project identifier.',
            ],
        ];
    }

    public function handle(array $arguments = []): array
    {
        $projectId = (int) ($arguments['project_id'] ?? 0);

        $project = Project::query()
            ->with([
                'client:id,name',
                'milestones:id,project_id,title,status,due_date',
                'tasks:id,project_id,title,status,start_date,due_date',
                'financeDocuments:id,project_id,document_number,status,total_amount,balance_due',
            ])
            ->findOrFail($projectId);

        return [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status,
                'client' => $project->client?->name,
                'start_date' => optional($project->start_date)?->toDateString(),
                'due_date' => optional($project->due_date)?->toDateString(),
                'summary' => $project->summary,
                'milestones' => $project->milestones->map(fn ($milestone): array => [
                    'id' => $milestone->id,
                    'title' => $milestone->title,
                    'status' => $milestone->status,
                    'due_date' => optional($milestone->due_date)?->toDateString(),
                ])->all(),
                'tasks' => $project->tasks->map(fn ($task): array => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'start_date' => optional($task->start_date)?->toDateString(),
                    'due_date' => optional($task->due_date)?->toDateString(),
                ])->all(),
                'invoices' => $project->financeDocuments->map(fn ($document): array => [
                    'id' => $document->id,
                    'document_number' => $document->document_number,
                    'status' => $document->status,
                    'total_amount' => $document->total_amount,
                    'balance_due' => $document->balance_due,
                ])->all(),
            ],
        ];
    }
}
