<?php

namespace App\Exceptions;

use App\Models\ProjectMilestone;
use RuntimeException;

class MilestoneBlockedException extends RuntimeException
{
    public function __construct(public readonly ProjectMilestone $milestone, string $reason)
    {
        parent::__construct($reason);
    }
}
