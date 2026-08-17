<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Proposal;
use App\Models\User;
use RuntimeException;

/**
 * Converts a won proposal into an active project delivery record. Deliberately
 * minimal — it creates the Project shell (client, proposal link, title, summary)
 * and nothing else; milestones/tasks/finance documents are staff-authored
 * afterwards in the admin project screen, not inferred from proposal document data.
 */
class ProposalConversionService
{
    public function convert(Proposal $proposal, ?User $actor = null): Project
    {
        if ($proposal->status !== 'won') {
            throw new RuntimeException('Only a proposal marked "won" can be converted into a project.');
        }

        if (! $proposal->client_id) {
            throw new RuntimeException('This proposal has no client and cannot be converted into a project.');
        }

        if ($proposal->projects()->exists()) {
            throw new RuntimeException('This proposal has already been converted into a project.');
        }

        return Project::create([
            'client_id' => $proposal->client_id,
            'proposal_id' => $proposal->id,
            'created_by' => $actor?->id,
            'title' => $proposal->title,
            'status' => 'planned',
            'summary' => $proposal->summary,
            'description' => $proposal->body,
            'start_date' => now()->toDateString(),
        ]);
    }
}
