<?php

namespace App\Support;

use App\Models\ProjectMilestone;

final class PortalMilestoneView
{
    public function __construct(
        public readonly ProjectMilestone $milestone,
        public readonly bool $isBlocked,
        public readonly bool $tasksVisible,
    ) {}
}
