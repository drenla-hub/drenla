<?php

namespace App\Support;

use App\Models\Project;
use Carbon\Carbon;

/**
 * Builds week-based Gantt data for a Project.
 *
 * Y axis  → milestones (each milestone = one swimlane row)
 * X axis  → weeks (Monday-based)
 * Payment → vertical marker lines at the week a payment milestone is due
 */
final class ProjectTimeline
{
    /**
     * @param  array<int>|null  $visibleMilestoneIds  Restrict swimlane rows to these milestone
     *                                                ids (preserving sort_order) — used by the client portal to hide milestones after a
     *                                                payment-blocked one. Null (admin's call site) means show every milestone.
     * @param  array<int>|null  $taskVisibleMilestoneIds  Restrict task bars/stats to tasks
     *                                                    belonging to these milestone ids, and drop the "unassigned" row entirely — used by
     *                                                    the portal to hide a blocked milestone's tasks while still showing its row. Null
     *                                                    means show every task, including unassigned ones (admin's call site).
     */
    public static function build(Project $project, ?array $visibleMilestoneIds = null, ?array $taskVisibleMilestoneIds = null): array
    {
        $scoped = $visibleMilestoneIds !== null;

        $tasks = $project->tasks()->with(['assignee', 'milestone'])->orderBy('due_date')->get();
        $milestones = $project->milestones()->with('tasks')->get();

        if ($scoped) {
            $milestones = $milestones->whereIn('id', $visibleMilestoneIds)->values();
            $tasks = $tasks->whereIn('project_milestone_id', $taskVisibleMilestoneIds ?? [])->values();
        }

        // ── Compute week range ────────────────────────────────────────────
        $dates = collect();
        foreach ($tasks as $t) {
            if ($t->start_date) {
                $dates->push($t->start_date);
            }
            if ($t->due_date) {
                $dates->push($t->due_date);
            }
        }
        foreach ($milestones as $m) {
            if ($m->due_date) {
                $dates->push($m->due_date);
            }
        }
        $dates->push(now());

        $timelineStart = $dates->min()->copy()->startOfWeek(Carbon::MONDAY);
        $timelineEnd = $dates->max()->copy()->addWeeks(1)->endOfWeek(Carbon::SUNDAY);

        // Ensure at least 12 weeks visible
        if ($timelineStart->diffInWeeks($timelineEnd) < 12) {
            $timelineEnd = $timelineStart->copy()->addWeeks(12)->endOfWeek(Carbon::SUNDAY);
        }

        $weeks = [];
        $cursor = $timelineStart->copy();
        while ($cursor->lte($timelineEnd)) {
            $weeks[] = $cursor->copy();
            $cursor->addWeek();
        }

        $weekKeys = array_map(fn ($w) => $w->format('o-\WW'), $weeks);
        $todayIdx = array_search(now()->format('o-\WW'), $weekKeys, true);
        $colWidth = 120; // px per week column

        // ── Build payment markers ─────────────────────────────────────────
        // Each marker = { milestoneId, weekIdx, amount, title, paymentStatus }
        $paymentMarkers = [];
        foreach ($milestones as $m) {
            if (! $m->payment_required || ! $m->due_date) {
                continue;
            }

            $weekKey = $m->due_date->copy()->startOfWeek(Carbon::MONDAY)->format('o-\WW');
            $wi = array_search($weekKey, $weekKeys, true);
            if ($wi === false) {
                continue;
            }

            $paymentMarkers[] = [
                'milestoneId' => $m->id,
                'weekIdx' => (int) $wi,
                'amount' => (float) $m->payment_required_amount,
                'title' => $m->title,
                'paymentStatus' => $m->payment_status, // pending | invoiced | paid | not_applicable
                'dueDate' => $m->due_date->format('d M Y'),
            ];
        }

        // ── Build swimlane rows (one per milestone + one "unassigned") ────
        $rows = [];

        foreach ($milestones as $milestone) {
            $milestoneTasks = $tasks->where('project_milestone_id', $milestone->id);

            $bars = [];
            $unscheduled = [];
            $laneEnds = [];

            foreach ($milestoneTasks as $task) {
                if (! $task->due_date && ! $task->start_date) {
                    $unscheduled[] = $task;

                    continue;
                }

                $s = ($task->start_date ?? $task->due_date)->copy()->startOfWeek(Carbon::MONDAY);
                $e = ($task->due_date ?? $task->start_date)->copy()->startOfWeek(Carbon::MONDAY);

                $si = (int) max(0, array_search($s->format('o-\WW'), $weekKeys, true) ?: 0);
                $ei = (int) min(count($weekKeys) - 1, array_search($e->format('o-\WW'), $weekKeys, true) ?: count($weekKeys) - 1);
                $ei = max($si, $ei);

                // Lane packing — find first lane that ends before this bar starts
                $laneIdx = count($laneEnds);
                foreach ($laneEnds as $li => $lEnd) {
                    if ($lEnd < $si) {
                        $laneIdx = $li;
                        break;
                    }
                }
                $laneEnds[$laneIdx] = $ei;

                $bars[] = [
                    'task' => $task,
                    'left' => $si * $colWidth,
                    'width' => max($colWidth - 6, ($ei - $si + 1) * $colWidth - 6),
                    'top' => $laneIdx * 46 + 6,
                    'si' => $si,
                    'ei' => $ei,
                ];
            }

            $maxLane = count($laneEnds) > 0 ? (count($laneEnds) - 1) : -1;
            $rowHeight = max(58, ($maxLane + 1) * 46 + 12);

            $rows[] = [
                'type' => 'milestone',
                'milestone' => $milestone,
                'bars' => $bars,
                'unscheduled' => $unscheduled,
                'rowHeight' => $rowHeight,
            ];
        }

        // Unassigned tasks row — skipped entirely when scoped (portal use): ungrouped
        // backlog tasks aren't tied to a client-visible milestone/payment gate.
        $unassigned = $scoped ? collect() : $tasks->whereNull('project_milestone_id');
        $uBars = [];
        $uUnscheduled = [];
        $uLaneEnds = [];

        foreach ($unassigned as $task) {
            if (! $task->due_date && ! $task->start_date) {
                $uUnscheduled[] = $task;

                continue;
            }

            $s = ($task->start_date ?? $task->due_date)->copy()->startOfWeek(Carbon::MONDAY);
            $e = ($task->due_date ?? $task->start_date)->copy()->startOfWeek(Carbon::MONDAY);

            $si = (int) max(0, array_search($s->format('o-\WW'), $weekKeys, true) ?: 0);
            $ei = (int) min(count($weekKeys) - 1, array_search($e->format('o-\WW'), $weekKeys, true) ?: count($weekKeys) - 1);
            $ei = max($si, $ei);

            $laneIdx = count($uLaneEnds);
            foreach ($uLaneEnds as $li => $lEnd) {
                if ($lEnd < $si) {
                    $laneIdx = $li;
                    break;
                }
            }
            $uLaneEnds[$laneIdx] = $ei;

            $uBars[] = [
                'task' => $task,
                'left' => $si * $colWidth,
                'width' => max($colWidth - 6, ($ei - $si + 1) * $colWidth - 6),
                'top' => $laneIdx * 46 + 6,
                'si' => $si,
                'ei' => $ei,
            ];
        }

        if (! empty($uBars) || ! empty($uUnscheduled)) {
            $maxLane = count($uLaneEnds) > 0 ? count($uLaneEnds) - 1 : -1;
            $rows[] = [
                'type' => 'unassigned',
                'milestone' => null,
                'bars' => $uBars,
                'unscheduled' => $uUnscheduled,
                'rowHeight' => max(58, ($maxLane + 1) * 46 + 12),
            ];
        }

        // ── Stats ─────────────────────────────────────────────────────────
        $totalHours = (int) $tasks->sum('estimated_hours');
        $doneHours = (int) $tasks->where('status', 'done')->sum('estimated_hours');
        $pct = $totalHours > 0
            ? round($doneHours / max(1, $totalHours) * 100)
            : ($tasks->count() > 0
                ? round($tasks->where('status', 'done')->count() / $tasks->count() * 100)
                : 0);

        $stats = [
            'total' => $tasks->count(),
            'done' => $tasks->where('status', 'done')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'pending' => $tasks->where('status', 'todo')->count(),
            'blocked' => $tasks->where('status', 'blocked')->count(),
            'overdue' => $tasks->filter(fn ($t) => $t->due_date && $t->due_date->isPast() && $t->status !== 'done')->count(),
            'total_hours' => $totalHours,
            'done_hours' => $doneHours,
            'pct' => $pct,
            'hours_based' => $totalHours > 0,
        ];

        return compact('tasks', 'milestones', 'weeks', 'weekKeys', 'rows', 'paymentMarkers', 'todayIdx', 'colWidth', 'stats');
    }
}
