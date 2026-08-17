<?php

namespace App\AiAssistant\Tools;

use App\AiAssistant\Contracts\AssistantTool;
use App\Models\Project;

class ListProjectsTool implements AssistantTool
{
    public function name(): string
    {
        return 'list_projects';
    }

    public function description(): string
    {
        return 'Return recent projects with status, client, and delivery dates.';
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'arguments' => [
                'limit' => 'Optional integer limit between 1 and 20.',
                'status' => 'Optional project status filter.',
            ],
        ];
    }

    public function handle(array $arguments = []): array
    {
        $limit = max(1, min((int) ($arguments['limit'] ?? 5), 20));

        $query = Project::query()
            ->with('client:id,name')
            ->latest()
            ->limit($limit);

        if (is_string($arguments['status'] ?? null) && $arguments['status'] !== '') {
            $query->where('status', $arguments['status']);
        }

        return [
            'projects' => $query->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status,
                    'client' => $project->client?->name,
                    'start_date' => optional($project->start_date)?->toDateString(),
                    'due_date' => optional($project->due_date)?->toDateString(),
                    'progress_percentage' => $project->progress_percentage,
                ])
                ->all(),
        ];
    }
}
