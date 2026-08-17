@extends('layouts.portal')

@section('title', $project->title)

@section('content')
<div class="space-y-10">
    <div>
        <a href="{{ route('portal.projects.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a7f6c] hover:text-[#161219]">&larr; Projects</a>

        <p class="mt-5 text-[10px] font-semibold uppercase tracking-[0.35em] text-[#8a7f6c]">Project</p>
        <h1 class="mt-3 text-[32px] font-light tracking-[-0.02em] text-[#161219]">{{ $project->title }}</h1>
        @if ($project->summary)
            <p class="mt-3 text-[13px] leading-relaxed text-[#5f5648]">{{ $project->summary }}</p>
        @endif

        <div class="mt-6 h-1 w-full bg-[#e4dfd6]">
            <div class="h-1 bg-[#161219]" style="width: {{ $project->progress_percentage }}%"></div>
        </div>
        <p class="mt-2 text-[11px] uppercase tracking-[0.15em] text-[#8a7f6c]">{{ $project->progress_percentage }}% complete</p>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  STATS STRIP                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center gap-6 border-y border-[#e4dfd6] py-5">
        @foreach ([
            ['val' => $stats['total'],       'label' => 'Total',   'color' => '#161219'],
            ['val' => $stats['done'],        'label' => 'Done',    'color' => '#3a9c56'],
            ['val' => $stats['in_progress'], 'label' => 'Active',  'color' => '#4a72c9'],
            ['val' => $stats['pending'],     'label' => 'To do',   'color' => '#8a7f6c'],
            ['val' => $stats['blocked'],     'label' => 'Blocked', 'color' => '#c0392b'],
            ['val' => $stats['overdue'],     'label' => 'Overdue', 'color' => '#b8860b'],
        ] as $s)
            <div class="flex items-center gap-2">
                <span class="text-[18px] font-light" style="color: {{ $s['color'] }}">{{ $s['val'] }}</span>
                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#a49a86]">{{ $s['label'] }}</span>
            </div>
        @endforeach

        @if ($stats['total'] > 0)
            <div class="ml-auto flex items-center gap-3">
                <div class="h-[2px] w-[140px] bg-[#e4dfd6]">
                    <div class="h-[2px] bg-[#161219]" style="width: {{ $stats['pct'] }}%"></div>
                </div>
                <span class="text-[10px] font-semibold text-[#8a7f6c]">{{ $stats['pct'] }}%</span>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  PROGRESS TABLE — full-bleed, breaks out of the reading column   --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    @php
        $totalCols = count($weeks);
        $labelW    = 200; // px — sticky left label column
        $noDateW   = 130; // px — sticky "unscheduled" column
    @endphp

    <div style="overflow-x:auto;">
        <div style="position:relative;width:{{ $labelW + $noDateW + $totalCols * $colWidth }}px;">
        <table style="border-collapse:collapse;table-layout:fixed;width:{{ $labelW + $noDateW + $totalCols * $colWidth }}px;background:#fff;border:1px solid #e4dfd6;">
                <colgroup>
                    <col style="width:{{ $labelW }}px;">
                    <col style="width:{{ $noDateW }}px;">
                    @foreach ($weeks as $_)
                        <col style="width:{{ $colWidth }}px;">
                    @endforeach
                </colgroup>

                {{-- ── Week header ──────────────────────────────────────────── --}}
                <thead>
                    <tr style="height:34px;background:#faf8f4;">
                        <th style="height:34px;padding:0 1rem;border-right:1px solid #e4dfd6;border-bottom:1px solid #e4dfd6;text-align:left;position:sticky;left:0;top:0;background:#faf8f4;z-index:21;">
                            <span style="font-size:9px;font-weight:700;letter-spacing:0.25em;text-transform:uppercase;color:#a49a86;">Milestone</span>
                        </th>
                        <th style="height:34px;padding:0 0.5rem;border-right:1px solid #e4dfd6;border-bottom:1px solid #e4dfd6;text-align:left;position:sticky;top:0;background:#faf8f4;z-index:20;">
                            <span style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#a49a86;">No date</span>
                        </th>
                        @foreach ($weeks as $wi => $week)
                            @php $isToday = ($wi === $todayIdx); @endphp
                            <th style="height:34px;padding:0 2px;border-right:1px solid #eee9de;border-bottom:1px solid #e4dfd6;text-align:center;vertical-align:middle;position:sticky;top:0;z-index:19;background:{{ $isToday ? '#eee6d8' : '#faf8f4' }};">
                                <span style="font-size:9px;font-weight:600;color:{{ $isToday ? '#161219' : '#a49a86' }};white-space:nowrap;">{{ $week->format('M j') }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                {{-- ── Swimlane rows ────────────────────────────────────────── --}}
                <tbody>
                @forelse ($rows as $row)
                    @php
                        $milestone = $row['milestone'];
                        $bars      = $row['bars'];
                        $unscheduled = $row['unscheduled'];
                        $rowHeight = $row['rowHeight'];
                        $isBlockedRow = false;

                        $isOverdueRow = false;

                        if ($milestone) {
                            $isBlockedRow = $milestoneViews->firstWhere('milestone.id', $milestone->id)?->isBlocked ?? false;
                            $isOverdueRow = $isBlockedRow && $milestone->due_date && $milestone->due_date->isPast();

                            // Payment gating (isBlockedRow/isOverdueRow) always wins over the
                            // milestone's own workflow `status` field — `status` can independently
                            // hold the literal value "paid" (a workflow stage) even when the
                            // separate `payment_status` field isn't actually paid, which would
                            // otherwise render a green "paid" dot for a milestone that hasn't
                            // really been paid for. Never show green unless payment is genuinely
                            // settled (i.e. not blocked).
                            if ($isOverdueRow) {
                                $mColor = '#c2410c'; // overdue — orange
                            } elseif ($isBlockedRow) {
                                $mColor = '#b8860b'; // pending, not yet overdue — amber
                            } else {
                                $mColors = ['planned'=>'#8a7f6c','in_progress'=>'#4a72c9','blocked'=>'#c0392b','ready_for_payment'=>'#b8860b','paid'=>'#3a9c56','released'=>'#3a9c56','completed'=>'#3a9c56'];
                                $mColor  = $mColors[$milestone->status] ?? '#8a7f6c';
                            }
                        }
                    @endphp

                    <tr style="border-bottom:1px solid #eee9de;">
                        {{-- ── Row label (sticky left) ──────────────────────── --}}
                        <td style="padding:0.6rem 0.75rem;border-right:1px solid #e4dfd6;vertical-align:top;position:sticky;left:0;background:{{ $isBlockedRow ? '#fbf9f5' : '#fff' }};z-index:5;height:{{ $rowHeight }}px;">
                            @if ($milestone)
                                <div class="flex items-center gap-2" style="margin-bottom:3px;">
                                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $mColor }};flex-shrink:0;"></span>
                                    <span style="font-size:11px;font-weight:500;color:#161219;line-height:1.3;">{{ $milestone->title }}</span>
                                </div>
                                @if ($milestone->payment_required && $milestone->payment_required_amount)
                                    <p style="font-size:10px;font-weight:700;color:#5f5648;padding-left:15px;margin-top:5px;">
                                        KES {{ number_format($milestone->payment_required_amount, 0) }}
                                    </p>
                                @endif
                                @if ($isBlockedRow)
                                    <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:{{ $isOverdueRow ? '#c2410c' : 'hsl(260 60% 45%)' }};padding-left:15px;margin-top:4px;">
                                        Locked &middot; payment {{ $isOverdueRow ? 'overdue' : 'pending' }}
                                    </p>
                                @endif
                            @endif
                            <p style="font-size:9px;font-family:monospace;color:#c2b9a8;padding-left:15px;margin-top:4px;">
                                {{ count($bars) + count($unscheduled) }} tasks
                            </p>
                        </td>

                        {{-- ── Unscheduled chips ─────────────────────────────── --}}
                        <td style="padding:4px;border-right:1px solid #e4dfd6;vertical-align:top;background:{{ $isBlockedRow ? '#fbf9f5' : '#fff' }};">
                            @foreach ($unscheduled as $task)
                                @php
                                    $tBg = ['todo'=>'#f7f5f2','in_progress'=>'rgba(74,114,201,0.10)','review'=>'rgba(184,134,11,0.10)','done'=>'rgba(58,156,86,0.10)','blocked'=>'rgba(192,57,43,0.10)'];
                                    $tBl = ['todo'=>'#d8d0c2','in_progress'=>'#4a72c9','review'=>'#b8860b','done'=>'#3a9c56','blocked'=>'#c0392b'];
                                @endphp
                                <div style="margin-bottom:3px;padding:4px 7px;background:{{ $tBg[$task->status] ?? '#f7f5f2' }};border-left:2px solid {{ $tBl[$task->status] ?? '#d8d0c2' }};">
                                    <p style="font-size:10px;font-weight:500;color:{{ $task->status === 'done' ? '#a49a86' : '#161219' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:110px;{{ $task->status === 'done' ? 'text-decoration:line-through;' : '' }}">
                                        {{ $task->title }}
                                    </p>
                                </div>
                            @endforeach
                        </td>

                        {{-- ── Timeline bar area ─────────────────────────────── --}}
                        <td colspan="{{ $totalCols }}" style="padding:0;position:relative;background:{{ $isBlockedRow ? '#fbf9f5' : '#fff' }};height:{{ $rowHeight }}px;overflow:visible;">
                            <div style="position:relative;width:{{ $totalCols * $colWidth }}px;height:{{ $rowHeight }}px;">

                                @foreach ($weeks as $wi => $__)
                                    <div style="position:absolute;left:{{ $wi * $colWidth }}px;top:0;bottom:0;width:1px;background:{{ $wi === $todayIdx ? '#e4dfd6' : '#f2efe8' }};pointer-events:none;"></div>
                                @endforeach

                                @if ($todayIdx !== false)
                                    <div style="position:absolute;left:{{ $todayIdx * $colWidth }}px;top:0;bottom:0;width:{{ $colWidth }}px;background:#f2ead9;pointer-events:none;opacity:0.5;"></div>
                                @endif

                                {{-- Task bars — title/status/dates only, no assignee or estimated hours --}}
                                @foreach ($bars as $bar)
                                    @php
                                        $t         = $bar['task'];
                                        $isOverdue = $t->due_date && $t->due_date->isPast() && $t->status !== 'done';
                                        $isDone    = $t->status === 'done';
                                        $bgMap = ['todo'=>'#f7f5f2','in_progress'=>'rgba(74,114,201,0.12)','review'=>'rgba(184,134,11,0.12)','done'=>'rgba(58,156,86,0.10)','blocked'=>'rgba(192,57,43,0.10)'];
                                        $blMap = ['todo'=>'#d8d0c2','in_progress'=>'#4a72c9','review'=>'#b8860b','done'=>'#3a9c56','blocked'=>'#c0392b'];
                                        $barBg = $isOverdue ? 'rgba(192,57,43,0.12)' : ($bgMap[$t->status] ?? '#f7f5f2');
                                        $barBl = $isOverdue ? '#c0392b' : ($blMap[$t->status] ?? '#d8d0c2');
                                    @endphp
                                    <div style="position:absolute;left:{{ $bar['left'] }}px;width:{{ $bar['width'] }}px;top:{{ $bar['top'] }}px;height:40px;background:{{ $barBg }};border-left:2px solid {{ $barBl }};padding:4px 10px;overflow:hidden;z-index:2;display:flex;flex-direction:column;justify-content:center;gap:2px;"
                                         title="{{ $t->title }}">
                                        <span style="font-size:10px;font-weight:500;color:{{ $isDone ? '#a49a86' : '#161219' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;{{ $isDone ? 'text-decoration:line-through;' : '' }}">
                                            {{ $t->title }}
                                        </span>
                                        <span style="font-size:8px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:{{ $barBl }};">
                                            {{ str_replace('_', ' ', $t->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $totalCols + 2 }}" style="padding:2rem;text-align:center;">
                            <span style="font-size:12px;color:#a49a86;">No milestones yet.</span>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            {{-- ── Payment date markers — a payment milestone is a date event, not a
                 per-task/per-row thing, so these span the full table height rather than
                 living inside any single milestone's swimlane row. ──────────────────── --}}
            @foreach ($paymentMarkers as $pm)
                @php
                    $pmView = $milestoneViews->firstWhere('milestone.id', $pm['milestoneId']);
                    $pmBlocked = $pmView?->isBlocked ?? false;
                    $pmOverdue = $pmBlocked && $pmView->milestone->due_date && $pmView->milestone->due_date->isPast();
                    $pmLabel = $pmOverdue ? 'overdue' : $pm['paymentStatus'];
                    $pmLineColor = $pmOverdue
                        ? '#c2410c'
                        : ($pmBlocked
                            ? '#b8860b'
                            : match ($pm['paymentStatus']) {
                                'paid' => '#3a9c56',
                                'invoiced' => '#4a72c9',
                                default => '#b8860b',
                            });
                    $pmLeft = $labelW + $noDateW + $pm['weekIdx'] * $colWidth + intval($colWidth / 2);
                @endphp
                <div style="position:absolute;left:{{ $pmLeft }}px;top:34px;bottom:0;width:2px;background:{{ $pmLineColor }};opacity:0.55;pointer-events:none;z-index:10;">
                    <div style="position:absolute;top:4px;left:4px;white-space:nowrap;background:#fff;border:1px solid {{ $pmLineColor }}55;padding:2px 6px;">
                        <p style="font-size:8px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $pmLineColor }};">
                            {{ number_format($pm['amount'], 0) }}
                        </p>
                        <p style="font-size:7px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:{{ $pmLineColor }};opacity:0.7;">
                            {{ $pmLabel }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  MILESTONE DETAIL LIST                                          --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="space-y-6 border-t border-[#e4dfd6] pt-8">
        <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Milestones</p>

        @foreach ($milestoneViews as $view)
            @php
                $milestone = $view->milestone;
                $isOverdue = $view->isBlocked && $milestone->due_date && $milestone->due_date->isPast();
            @endphp
            <div class="border border-[#e4dfd6] {{ $view->isBlocked ? 'bg-[#fbf9f5]' : 'bg-white' }} px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[14px] font-medium text-[#161219]">{{ $milestone->title }}</p>
                        @if ($milestone->due_date)
                            <p class="mt-1 text-[11px] uppercase tracking-[0.15em] text-[#8a7f6c]">Due {{ $milestone->due_date->format('d M Y') }}</p>
                        @endif
                    </div>

                    @if ($view->isBlocked)
                        <span class="shrink-0 border px-3 py-1 text-[10px] font-bold uppercase tracking-[0.15em]" style="color: {{ $isOverdue ? '#c2410c' : 'hsl(260 60% 45%)' }}; border-color: {{ $isOverdue ? '#c2410c66' : 'hsl(260 60% 55% / 0.4)' }}">
                            Locked &middot; payment {{ $isOverdue ? 'overdue' : 'pending' }}
                        </span>
                    @else
                        <span class="shrink-0 text-[10px] font-bold uppercase tracking-[0.15em]" style="color: {{ $milestone->status === 'completed' ? '#3a9c56' : '#8a7f6c' }}">
                            {{ str_replace('_', ' ', $milestone->status) }}
                        </span>
                    @endif
                </div>

                @if ($view->isBlocked)
                    @php
                        $amountNote = $milestone->payment_required_amount
                            ? ' (KES '.number_format((float) $milestone->payment_required_amount, 2).')'
                            : '';
                    @endphp
                    <p class="mt-4 text-[12px] leading-relaxed text-[#5f5648]">
                        This stage unlocks once payment is received{{ $amountNote }}.
                        Reach out to your Drenla contact if you need an invoice.
                    </p>
                @else
                    @if ($milestone->description)
                        <p class="mt-3 text-[13px] leading-relaxed text-[#3a3327]">{{ $milestone->description }}</p>
                    @endif
                @endif

                @if ($view->tasksVisible && $milestone->tasks->isNotEmpty())
                    <div class="mt-4 divide-y divide-[#eee9de] border-t border-[#eee9de]">
                        @foreach ($milestone->tasks as $task)
                            <div class="flex items-center justify-between py-2.5">
                                <span class="text-[13px] text-[#3a3327]">{{ $task->title }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em]" style="color: {{ $task->status === 'done' ? '#3a9c56' : '#8a7f6c' }}">
                                    {{ str_replace('_', ' ', $task->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
