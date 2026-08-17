<?php

namespace App\Services;

use App\Models\Proposal;
use Carbon\CarbonInterface;

class ProposalReferenceGenerator
{
    public function generate(?CarbonInterface $issueDate = null): string
    {
        $issueDate ??= now();

        $month = strtoupper($issueDate->format('M'));
        $year = $issueDate->format('Y');
        $prefix = sprintf('DNR-%s-', $month);
        $suffix = sprintf('-%s', $year);

        $latestReference = Proposal::query()
            ->whereNotNull('reference_number')
            ->where('reference_number', 'like', $prefix.'%'.$suffix)
            ->latest('id')
            ->value('reference_number');

        $sequence = 1;

        if ($latestReference && preg_match('/^DNR-[A-Z]{3}-(\d{3})-\d{4}$/', $latestReference, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('DNR-%s-%03d-%s', $month, $sequence, $year);
    }
}
