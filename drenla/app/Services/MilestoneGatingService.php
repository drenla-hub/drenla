<?php

namespace App\Services;

use App\Exceptions\MilestoneBlockedException;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Support\PortalMilestoneView;
use Illuminate\Support\Collection;

/**
 * Single source of truth for whether a payment-gated milestone blocks downstream
 * progression. See collaboration-notes.md "Decisions Log" (2026-07-02, CO) for the
 * rule rationale. Admin and portal must both call into this service rather than
 * re-deriving the rule from payment_status directly.
 */
class MilestoneGatingService
{
    /**
     * Payment statuses that do NOT block progression. Only `paid` today — CL's admin
     * milestone form currently validates payment_status to
     * not_applicable|pending|invoiced|paid (see Admin\ProjectController), so a
     * `waived` status can't actually be set yet. If a pro-bono/waived-fee case comes
     * up, add the status there first, then add it here.
     */
    private const UNBLOCKING_STATUSES = ['paid'];

    public function isBlocked(ProjectMilestone $milestone): bool
    {
        return $milestone->payment_required
            && ! in_array($milestone->payment_status, self::UNBLOCKING_STATUSES, true);
    }

    public function blockingReason(ProjectMilestone $milestone): ?string
    {
        if (! $this->isBlocked($milestone)) {
            return null;
        }

        return $milestone->blocked_reason
            ?: sprintf('Payment required (%s) before this milestone can proceed.', $milestone->payment_status);
    }

    /**
     * @throws MilestoneBlockedException
     */
    public function assertCanComplete(ProjectMilestone $milestone): void
    {
        if ($this->isBlocked($milestone)) {
            throw new MilestoneBlockedException($milestone, $this->blockingReason($milestone));
        }
    }

    /**
     * @throws MilestoneBlockedException
     */
    public function assertCanCompleteTask(ProjectTask $task): void
    {
        $milestone = $task->milestone;

        if ($milestone && $this->isBlocked($milestone)) {
            throw new MilestoneBlockedException($milestone, $this->blockingReason($milestone));
        }
    }

    /**
     * Client-portal visibility rule: milestones are returned in sort order up to and
     * including the first blocked one; nothing after a blocked milestone is returned
     * at all, and a blocked milestone's own task list is marked not-visible. Admin
     * views never call this — admin always sees every milestone and task regardless
     * of gating state.
     *
     * @return Collection<int, PortalMilestoneView>
     */
    public function visibleMilestonesFor(Project $project): Collection
    {
        $views = collect();

        foreach ($project->milestones as $milestone) {
            $blocked = $this->isBlocked($milestone);

            $views->push(new PortalMilestoneView(
                milestone: $milestone,
                isBlocked: $blocked,
                tasksVisible: ! $blocked,
            ));

            if ($blocked) {
                break;
            }
        }

        return $views;
    }
}
