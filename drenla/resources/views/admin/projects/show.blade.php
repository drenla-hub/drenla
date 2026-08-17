@extends('layouts.admin')

@section('title', $project->title)

@php
    $sColors = ['planned'=>'#666','active'=>'#7c9','blocked'=>'#c44','on_hold'=>'#ca7','completed'=>'#4c4'];
    $sColor  = $sColors[$project->status] ?? '#666';
@endphp

@section('content')
<div class="flex flex-col gap-0 -mx-8 -mt-8 xl:-mx-10 xl:-mt-10">

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{--  HEADER BAR                                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div style="background:#050505;border-bottom:1px solid rgba(255,255,255,0.07);padding:1rem 2rem;">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.projects.index') }}"
               style="color:rgba(255,255,255,0.25);flex-shrink:0;transition:color 0.15s;"
               onmouseover="this.style.color='rgba(255,255,255,0.7)'"
               onmouseout="this.style.color='rgba(255,255,255,0.25)'">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
                </svg>
            </a>
            <div>
                <p style="font-size:9px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#444;">
                    {{ $project->client?->name }}
                </p>
                <h1 style="font-size:1.1rem;font-weight:300;color:#fff;margin-top:2px;letter-spacing:-0.01em;">
                    {{ $project->title }}
                </h1>
            </div>
            <span style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;padding:3px 10px;border:1px solid;color:{{ $sColor }};border-color:{{ $sColor }}33;">
                {{ $project->status }}
            </span>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="openNewMilestonePanel()"
                    style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.55);border:1px solid rgba(255,255,255,0.1);padding:6px 14px;background:none;cursor:pointer;transition:border-color 0.15s;"
                    onmouseover="this.style.borderColor='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">+ Milestone</button>
            <button onclick="openNewTaskPanel()"
                    style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.55);border:1px solid rgba(255,255,255,0.1);padding:6px 14px;background:none;cursor:pointer;transition:border-color 0.15s;"
                    onmouseover="this.style.borderColor='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">+ Task</button>
            <a href="{{ route('admin.projects.edit', $project) }}"
               style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.35);border:1px solid rgba(255,255,255,0.07);padding:6px 14px;text-decoration:none;transition:all 0.15s;"
               onmouseover="this.style.color='rgba(255,255,255,0.7)'"
               onmouseout="this.style.color='rgba(255,255,255,0.35)'">Edit</a>
        </div>
    </div>

    {{-- Stats strip --}}
    <div class="flex items-center gap-6 flex-wrap" style="margin-top:0.6rem;">
        @foreach ([
            ['val' => $stats['total'],       'label' => 'Total',   'color' => 'rgba(255,255,255,0.6)'],
            ['val' => $stats['done'],        'label' => 'Done',    'color' => '#4c4'],
            ['val' => $stats['in_progress'], 'label' => 'Active',  'color' => '#7af'],
            ['val' => $stats['pending'],     'label' => 'To do',   'color' => 'rgba(255,255,255,0.25)'],
            ['val' => $stats['blocked'],     'label' => 'Blocked', 'color' => '#c44'],
            ['val' => $stats['overdue'],     'label' => 'Overdue', 'color' => '#fa7'],
        ] as $s)
            <div class="flex items-center gap-2">
                <span style="font-size:1.05rem;font-weight:300;color:{{ $s['color'] }};">{{ $s['val'] }}</span>
                <span style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.2);">{{ $s['label'] }}</span>
            </div>
        @endforeach

        @if ($stats['total'] > 0)
            <div class="flex items-center gap-2 ml-auto">
                <div style="width:140px;height:2px;background:rgba(255,255,255,0.07);">
                    <div style="height:2px;background:rgba(255,255,255,0.4);width:{{ $stats['pct'] }}%;transition:width 0.3s;"></div>
                </div>
                <span style="font-size:9px;font-family:monospace;color:rgba(255,255,255,0.3);">
                    @if ($stats['hours_based']){{ $stats['done_hours'] }}h / {{ $stats['total_hours'] }}h ·@endif
                    {{ $stats['pct'] }}%
                </span>
            </div>
        @endif
    </div>
</div>

{{-- New task slide panel is rendered after the Gantt, handled by JS --}}

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{--  GANTT BOARD                                                              --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
@php
    $totalCols = count($weeks);
    $labelW    = 180; // px — sticky left label column
    $noDateW   = 110; // px — sticky "unscheduled" column
@endphp

<div style="overflow-x:auto;border-bottom:1px solid rgba(255,255,255,0.06);">
    <table data-gantt style="border-collapse:collapse;table-layout:fixed;width:{{ $labelW + $noDateW + $totalCols * $colWidth }}px;">
        <colgroup>
            <col style="width:{{ $labelW }}px;">
            <col style="width:{{ $noDateW }}px;">
            @foreach ($weeks as $_)
                <col style="width:{{ $colWidth }}px;">
            @endforeach
        </colgroup>

        {{-- ── Week header ──────────────────────────────────────────────── --}}
        <thead>
            <tr style="height:34px;background:#020202;">
                {{-- Row label header --}}
                <th style="height:34px;padding:0 1rem;border-right:1px solid rgba(255,255,255,0.06);border-bottom:1px solid rgba(255,255,255,0.08);text-align:left;position:sticky;left:0;top:0;background:#020202;z-index:21;">
                    <span style="font-size:9px;font-weight:700;letter-spacing:0.25em;text-transform:uppercase;color:rgba(255,255,255,0.18);">Milestone</span>
                </th>
                {{-- No-date header --}}
                <th style="height:34px;padding:0 0.5rem;border-right:1px solid rgba(255,255,255,0.05);border-bottom:1px solid rgba(255,255,255,0.08);text-align:left;position:sticky;top:0;background:#020202;z-index:20;">
                    <span style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.15);">No date</span>
                </th>
                {{-- Week columns — date label only, payment lines are drawn in the body --}}
                @foreach ($weeks as $wi => $week)
                    @php $isToday = ($wi === $todayIdx); @endphp
                    <th style="height:34px;padding:0 2px;border-right:1px solid rgba(255,255,255,0.03);border-bottom:1px solid rgba(255,255,255,0.08);text-align:center;vertical-align:middle;position:sticky;top:0;z-index:19;background:{{ $isToday ? 'rgba(255,255,255,0.05)' : '#020202' }};">
                        <span style="font-size:9px;font-weight:600;color:{{ $isToday ? '#fff' : 'rgba(255,255,255,0.22)' }};white-space:nowrap;">{{ $week->format('M j') }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>

        {{-- ── Swimlane rows ────────────────────────────────────────────── --}}
        <tbody>
        @forelse ($rows as $row)
            @php
                $milestone = $row['milestone'];
                $bars      = $row['bars'];
                $unscheduled = $row['unscheduled'];
                $rowHeight = $row['rowHeight'];

                if ($milestone) {
                    $mColors  = ['planned'=>'#666','in_progress'=>'#7af','blocked'=>'#c44','ready_for_payment'=>'#ca7','paid'=>'#4c4','released'=>'#7c9','completed'=>'#4c4'];
                    $mColor   = $mColors[$milestone->status] ?? '#555';
                    $payColor = match($milestone->payment_status ?? 'not_applicable') {
                        'paid'     => '#4c4',
                        'invoiced' => '#7af',
                        'pending'  => '#ca7',
                        default    => null,
                    };
                }
            @endphp

            <tr style="border-bottom:1px solid rgba(255,255,255,0.04);">

                {{-- ── Row label (sticky left) ──────────────────────────── --}}
                <td style="padding:0.6rem 0.75rem;border-right:1px solid rgba(255,255,255,0.06);vertical-align:top;position:sticky;left:0;background:#050505;z-index:5;height:{{ $rowHeight }}px;">
                    @if ($milestone)
                        @php
                            $milestoneData = json_encode([
                                'id' => $milestone->id,
                                'title' => $milestone->title,
                                'description' => $milestone->description,
                                'status' => $milestone->status,
                                'sort_order' => $milestone->sort_order,
                                'due_date' => $milestone->due_date?->toISOString(),
                                'payment_required' => (bool) $milestone->payment_required,
                                'payment_required_amount' => $milestone->payment_required_amount,
                                'payment_status' => $milestone->payment_status,
                                'blocked_reason' => $milestone->blocked_reason,
                                'is_blocked' => $milestoneGating[$milestone->id]['is_blocked'] ?? false,
                                'blocking_reason' => $milestoneGating[$milestone->id]['reason'] ?? null,
                            ]);
                        @endphp
                        <div class="flex items-center gap-2" style="margin-bottom:3px;cursor:pointer;" onclick='openMilestone({{ $milestoneData }})'>
                            <span style="width:7px;height:7px;border-radius:50%;background:{{ $mColor }};flex-shrink:0;"></span>
                            <span style="font-size:11px;font-weight:500;color:rgba(255,255,255,0.8);line-height:1.3;">{{ $milestone->title }}</span>
                        </div>
                        @if ($milestone->payment_required && $milestone->payment_required_amount)
                            <p style="font-size:10px;font-weight:700;color:rgba(255,255,255,0.62);padding-left:15px;margin-top:5px;">
                                KES {{ number_format($milestone->payment_required_amount, 0) }}
                            </p>
                        @endif
                        @if ($milestoneGating[$milestone->id]['is_blocked'] ?? false)
                            <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#c44;padding-left:15px;margin-top:4px;" title="{{ $milestoneGating[$milestone->id]['reason'] }}">
                                ⛔ Payment gate — downstream progress blocked
                            </p>
                        @endif
                    @else
                        <div class="flex items-center gap-2">
                            <span style="width:7px;height:7px;border-radius:50%;background:#333;flex-shrink:0;"></span>
                            <span style="font-size:11px;font-weight:500;color:rgba(255,255,255,0.3);">No milestone</span>
                        </div>
                    @endif
                    {{-- task count --}}
                    <p style="font-size:9px;font-family:monospace;color:rgba(255,255,255,0.15);padding-left:15px;margin-top:4px;">
                        {{ count($bars) + count($unscheduled) }} tasks
                    </p>
                </td>

                {{-- ── Unscheduled chips ───────────────────────────────── --}}
                <td style="padding:4px;border-right:1px solid rgba(255,255,255,0.04);vertical-align:top;background:#050505;">
                    @foreach ($unscheduled as $task)
                        @php
                            $tColors = ['todo'=>'rgba(255,255,255,0.07)','in_progress'=>'rgba(120,180,255,0.12)','review'=>'rgba(250,160,80,0.12)','done'=>'rgba(68,204,68,0.10)','blocked'=>'rgba(204,68,68,0.10)'];
                            $tBc     = ['todo'=>'rgba(255,255,255,0.1)','in_progress'=>'#7af','review'=>'#fa7','done'=>'#4c4','blocked'=>'#c44'];
                            $taskBg     = $tColors[$task->status] ?? 'rgba(255,255,255,0.04)';
                            $taskBorder = $tBc[$task->status] ?? 'rgba(255,255,255,0.08)';
                            $priC = ['low'=>'#555','medium'=>'#ca7','high'=>'#c44'];
                            $taskData = json_encode(['id'=>$task->id,'title'=>$task->title,'status'=>$task->status,'priority'=>$task->priority,'assigned_to'=>$task->assigned_to,'due_date'=>$task->due_date?->toISOString(),'start_date'=>$task->start_date?->toISOString(),'description'=>$task->description,'estimated_hours'=>$task->estimated_hours,'project_milestone_id'=>$task->project_milestone_id,'assignee_name'=>$task->assignee?->name]);
                        @endphp
                        <div onclick='openTask({{ $taskData }})'
                             style="margin-bottom:3px;padding:4px 7px 4px 5px;background:{{ $taskBg }};border-left:2px solid {{ $taskBorder }};cursor:pointer;transition:background 0.12s;"
                             onmouseover="this.style.background='rgba(255,255,255,0.07)'"
                             onmouseout="this.style.background='{{ $taskBg }}'">
                            <p style="font-size:10px;font-weight:500;color:{{ $task->status==='done' ? 'rgba(255,255,255,0.3)' : '#e8e8ec' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px;{{ $task->status==='done' ? 'text-decoration:line-through;' : '' }}">
                                {{ $task->title }}
                            </p>
                            <div class="flex items-center gap-1.5" style="margin-top:2px;">
                                <span style="width:4px;height:4px;border-radius:50%;background:{{ $priC[$task->priority ?? 'medium'] ?? '#ca7' }};flex-shrink:0;"></span>
                                @if ($task->assignee)
                                    <span style="font-size:8px;font-family:monospace;color:rgba(255,255,255,0.3);overflow:hidden;text-overflow:ellipsis;max-width:60px;white-space:nowrap;">{{ explode(' ', $task->assignee->name)[0] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </td>

                {{-- ── Timeline bar area ────────────────────────────────── --}}
                <td colspan="{{ $totalCols }}" style="padding:0;position:relative;background:#050505;height:{{ $rowHeight }}px;overflow:visible;">
                    <div style="position:relative;width:{{ $totalCols * $colWidth }}px;height:{{ $rowHeight }}px;">

                        {{-- Week grid lines --}}
                        @foreach ($weeks as $wi => $__)
                            <div style="position:absolute;left:{{ $wi * $colWidth }}px;top:0;bottom:0;width:1px;background:{{ $wi === $todayIdx ? 'rgba(255,255,255,0.1)' : 'rgba(255,255,255,0.025)' }};pointer-events:none;"></div>
                        @endforeach

                        {{-- Today column highlight --}}
                        @if ($todayIdx !== false)
                            <div style="position:absolute;left:{{ $todayIdx * $colWidth }}px;top:0;bottom:0;width:{{ $colWidth }}px;background:rgba(255,255,255,0.025);pointer-events:none;"></div>
                        @endif

                        {{-- ── PAYMENT VERTICAL LINES ──────────────────────── --}}
                        @foreach ($paymentMarkers as $pm)
                            @php
                                $pmLineColor = match($pm['paymentStatus']) {
                                    'paid'     => '#4c4',
                                    'invoiced' => '#7af',
                                    default    => '#ca7',
                                };
                            @endphp
                            <div style="position:absolute;left:{{ $pm['weekIdx'] * $colWidth + intval($colWidth/2) }}px;top:0;bottom:0;width:2px;background:{{ $pmLineColor }};opacity:0.45;pointer-events:none;z-index:3;"
                                 title="{{ $pm['title'] }} — due {{ $pm['dueDate'] }} · {{ $pm['paymentStatus'] }}">
                                {{-- Label only in the first swimlane row --}}
                                @if ($loop->parent->first)
                                    <div style="position:absolute;top:4px;left:4px;white-space:nowrap;background:#050505;border:1px solid {{ $pmLineColor }}55;padding:2px 6px;pointer-events:auto;">
                                        <p style="font-size:8px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $pmLineColor }};">
                                            {{ number_format($pm['amount'], 0) }}
                                        </p>
                                        <p style="font-size:7px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:{{ $pmLineColor }}88;">
                                            {{ $pm['paymentStatus'] }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        {{-- ── Task bars ──────────────────────────────────── --}}
                        @foreach ($bars as $bar)
                            @php
                                $t       = $bar['task'];
                                $isOverdue = $t->due_date && $t->due_date->isPast() && $t->status !== 'done';
                                $isDone  = $t->status === 'done';
                                $bgMap   = ['todo'=>'rgba(255,255,255,0.06)','in_progress'=>'rgba(120,180,255,0.13)','review'=>'rgba(250,160,80,0.12)','done'=>'rgba(68,204,68,0.09)','blocked'=>'rgba(204,68,68,0.10)'];
                                $bhMap   = ['todo'=>'rgba(255,255,255,0.11)','in_progress'=>'rgba(120,180,255,0.22)','review'=>'rgba(250,160,80,0.20)','done'=>'rgba(68,204,68,0.15)','blocked'=>'rgba(204,68,68,0.18)'];
                                $blMap   = ['todo'=>'rgba(255,255,255,0.18)','in_progress'=>'#7af','review'=>'#fa7','done'=>'#4c4','blocked'=>'#c44'];
                                $barBg   = $isOverdue ? 'rgba(204,68,68,0.12)' : ($bgMap[$t->status] ?? 'rgba(255,255,255,0.05)');
                                $barBgH  = $isOverdue ? 'rgba(204,68,68,0.20)' : ($bhMap[$t->status] ?? 'rgba(255,255,255,0.09)');
                                $barBL   = $isOverdue ? '#c44' : ($blMap[$t->status] ?? 'rgba(255,255,255,0.2)');
                                $priC    = ['low'=>'#555','medium'=>'#ca7','high'=>'#c44'];
                                $taskData = json_encode(['id'=>$t->id,'title'=>$t->title,'status'=>$t->status,'priority'=>$t->priority,'assigned_to'=>$t->assigned_to,'due_date'=>$t->due_date?->toISOString(),'start_date'=>$t->start_date?->toISOString(),'description'=>$t->description,'estimated_hours'=>$t->estimated_hours,'project_milestone_id'=>$t->project_milestone_id,'assignee_name'=>$t->assignee?->name]);
                            @endphp
                            <div onclick='openTask(event, {{ $taskData }})'
                                 data-task-bar
                                 data-task-id="{{ $t->id }}"
                                 data-start-index="{{ $bar['si'] }}"
                                 data-end-index="{{ $bar['ei'] }}"
                                 data-start-date="{{ $t->start_date?->toDateString() }}"
                                 data-due-date="{{ $t->due_date?->toDateString() }}"
                                 data-default-left="{{ $bar['left'] }}"
                                 data-default-width="{{ max($colWidth - 6, ($bar['ei'] - $bar['si'] + 1) * $colWidth - 6) }}"
                                 style="position:absolute;left:{{ $bar['left'] }}px;width:{{ $bar['width'] }}px;top:{{ $bar['top'] }}px;height:40px;background:{{ $barBg }};border-left:2px solid {{ $barBL }};cursor:pointer;padding:4px 16px 4px 7px;overflow:hidden;transition:background 0.12s;z-index:2;display:flex;flex-direction:column;justify-content:center;gap:2px;"
                                 onmouseover="this.style.background='{{ $barBgH }}'"
                                 onmouseout="this.style.background='{{ $barBg }}'"
                                 title="{{ addslashes($t->title) }}">
                                <div data-task-shift-handle
                                     title="Drag to move task while preserving duration"
                                     style="position:absolute;top:0;left:0;width:12px;height:100%;cursor:ew-resize;background:linear-gradient(270deg, rgba(255,255,255,0), rgba(255,255,255,0.16));border-right:1px solid rgba(255,255,255,0.14);"></div>

                                {{-- Row 1: done circle + priority dot + title --}}
                                <div style="display:flex;align-items:center;gap:5px;overflow:hidden;">
                                    <span style="width:4px;height:4px;border-radius:50%;background:{{ $priC[$t->priority ?? 'medium'] ?? '#ca7' }};flex-shrink:0;"></span>
                                    <span style="font-size:10px;font-weight:500;color:{{ $isDone ? 'rgba(255,255,255,0.3)' : '#e8e8ec' }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;{{ $isDone ? 'text-decoration:line-through;' : '' }}">
                                        {{ $t->title }}
                                    </span>
                                    @if ($isOverdue)
                                        <span style="font-size:9px;font-weight:700;color:#c44;flex-shrink:0;">!</span>
                                    @endif
                                </div>

                                {{-- Row 2: assignee + hours --}}
                                <div style="display:flex;align-items:center;gap:5px;overflow:hidden;">
                                    @if ($t->assignee)
                                        <span style="font-size:8px;font-family:monospace;color:rgba(255,255,255,0.35);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $t->assignee->name }}</span>
                                    @endif
                                    @if ($t->estimated_hours)
                                        <span style="font-size:8px;font-family:monospace;color:rgba(255,255,255,0.18);flex-shrink:0;">{{ $t->estimated_hours }}h</span>
                                    @endif
                                </div>
                                <div data-task-resize-handle
                                     title="Drag to extend or shorten task"
                                     style="position:absolute;top:0;right:0;width:12px;height:100%;cursor:ew-resize;background:linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.16));border-left:1px solid rgba(255,255,255,0.14);"></div>
                            </div>
                        @endforeach

                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $totalCols + 2 }}" style="height:160px;padding:0 1.25rem;border-top:1px solid rgba(255,255,255,0.04);color:rgba(255,255,255,0.35);font-size:11px;letter-spacing:0.08em;text-transform:uppercase;background:#050505;">
                    No milestones or scheduled tasks yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{--  PAYMENT MILESTONES SUMMARY BAR (below Gantt)                            --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
@php
    $payMs    = $milestones->where('payment_required', true);
    $totalPay = $payMs->sum('payment_required_amount');
    $paidPay  = $payMs->where('payment_status', 'paid')->sum('payment_required_amount');
@endphp
@if ($payMs->count() > 0)
<div style="background:#030303;border-bottom:1px solid rgba(255,255,255,0.06);padding:1rem 2rem;">
    <div class="flex items-center gap-8 flex-wrap">
        <p style="font-size:9px;font-weight:700;letter-spacing:0.3em;text-transform:uppercase;color:#444;">Payment summary</p>
        <div class="flex items-center gap-2">
            <span style="font-size:18px;font-weight:300;color:rgba(255,255,255,0.6);">{{ number_format($totalPay, 0) }}</span>
            <span style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#333;">Contracted</span>
        </div>
        <div class="flex items-center gap-2">
            <span style="font-size:18px;font-weight:300;color:#4c4;">{{ number_format($paidPay, 0) }}</span>
            <span style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#333;">Received</span>
        </div>
        <div class="flex items-center gap-2">
            <span style="font-size:18px;font-weight:300;color:#ca7;">{{ number_format($totalPay - $paidPay, 0) }}</span>
            <span style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#333;">Outstanding</span>
        </div>

        {{-- Payment markers legend --}}
        <div class="flex items-center gap-4 ml-auto">
            @foreach ($paymentMarkers as $pm)
                @php
                    $pmC = match($pm['paymentStatus']) { 'paid'=>'#4c4','invoiced'=>'#7af',default=>'#ca7' };
                @endphp
                <div class="flex items-center gap-1.5">
                    <div style="width:2px;height:14px;background:{{ $pmC }};opacity:0.7;"></div>
                    <span style="font-size:9px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $pmC }};">
                        {{ $pm['dueDate'] }} · {{ number_format($pm['amount'], 0) }} · {{ $pm['paymentStatus'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

</div>{{-- end full-bleed wrapper --}}

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{--  NEW TASK PANEL (slide-in from right)                                     --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div id="new-task-panel"
     style="position:fixed;right:0;top:0;bottom:0;width:400px;background:#080808;border-left:1px solid rgba(255,255,255,0.08);z-index:52;overflow-y:auto;transform:translateX(100%);transition:transform 0.25s ease;display:flex;flex-direction:column;">

    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.07);flex-shrink:0;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:9px;font-weight:700;letter-spacing:0.25em;text-transform:uppercase;color:#444;">New Task</span>
        <button onclick="closeNewTaskPanel()"
                style="color:rgba(255,255,255,0.3);background:none;border:none;cursor:pointer;font-size:20px;line-height:1;">&times;</button>
    </div>

    <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}"
          style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
        @csrf

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Title *</label>
            <input type="text" name="title" placeholder="What needs to be done?" required
                   style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:9px 10px;outline:none;"
                   onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                   onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Status</label>
            <div id="new-task-status-btns" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
            <input type="hidden" id="new-task-status" name="status" value="todo">
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Priority</label>
            <div id="new-task-priority-btns" style="display:flex;gap:6px;"></div>
            <input type="hidden" id="new-task-priority" name="priority" value="medium">
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Assignee</label>
            <select name="assigned_to"
                    style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;"
                    onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                    onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                <option value="">Unassigned</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Milestone</label>
            <select name="project_milestone_id"
                    style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;"
                    onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                    onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                <option value="">No milestone</option>
                @foreach ($milestones as $m)
                    <option value="{{ $m->id }}">{{ $m->title }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Start date</label>
                <input type="date" name="start_date"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Due date</label>
                <input type="date" name="due_date"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Est. hours</label>
            <input type="number" name="estimated_hours" min="1" placeholder="e.g. 8"
                   style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:8px 10px;outline:none;font-family:monospace;"
                   onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                   onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Description</label>
            <textarea name="description" rows="3"
                      style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;resize:vertical;line-height:1.5;"
                      onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                      onblur="this.style.borderColor='rgba(255,255,255,0.08)'"></textarea>
        </div>

        <button type="submit"
                style="width:100%;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#000;background:#fff;border:none;padding:11px;cursor:pointer;margin-top:4px;transition:opacity 0.15s;"
                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
            Create Task
        </button>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{--  TASK DETAIL PANEL (slide-in from right)                                  --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div id="task-panel"
     style="position:fixed;right:0;top:0;bottom:0;width:380px;background:#080808;border-left:1px solid rgba(255,255,255,0.08);z-index:50;overflow-y:auto;transform:translateX(100%);transition:transform 0.25s ease;display:flex;flex-direction:column;">

    {{-- Panel header --}}
    <div style="padding:1.25rem 1.5rem 0;border-bottom:1px solid rgba(255,255,255,0.07);flex-shrink:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;">Task</span>
                <span id="panel-saved" style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#4c4;display:none;">✓ Saved</span>
            </div>
            <button onclick="closePanel()"
                    style="color:rgba(255,255,255,0.3);background:none;border:none;cursor:pointer;font-size:20px;line-height:1;">&times;</button>
        </div>
    </div>

    {{-- Panel body — form --}}
    <form id="task-panel-form" method="POST" style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem;">
        @csrf @method('PATCH')
        <input type="hidden" name="_task_id" id="panel-task-id">

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Title</label>
            <input type="text" id="panel-title" name="title"
                   style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:8px 10px;outline:none;"
                   onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                   onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Status</label>
            <div id="panel-status-btns" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
            <input type="hidden" id="panel-status" name="status">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Priority</label>
            <div id="panel-priority-btns" style="display:flex;gap:6px;"></div>
            <input type="hidden" id="panel-priority" name="priority">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Assignee</label>
            <select id="panel-assignee" name="assigned_to"
                    style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;"
                    onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                    onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                <option value="">Unassigned</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Milestone</label>
            <select id="panel-milestone" name="project_milestone_id"
                    style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;"
                    onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                    onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
                <option value="">No milestone</option>
                @foreach ($milestones as $m)
                    <option value="{{ $m->id }}">{{ $m->title }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1.25rem;">
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Start date</label>
                <input type="date" id="panel-start-date" name="start_date"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Due date</label>
                <input type="date" id="panel-due-date" name="due_date"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Est. hours</label>
            <input type="number" id="panel-hours" name="estimated_hours" min="1" placeholder="e.g. 8"
                   style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:8px 10px;outline:none;font-family:monospace;"
                   onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                   onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
        </div>

        <div style="margin-bottom:1.5rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Description</label>
            <textarea id="panel-desc" name="description" rows="3"
                      style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;resize:vertical;line-height:1.5;"
                      onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                      onblur="this.style.borderColor='rgba(255,255,255,0.08)'"></textarea>
        </div>

        <button type="submit"
                style="width:100%;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#000;background:#fff;border:none;padding:10px;cursor:pointer;margin-bottom:10px;transition:opacity 0.15s;"
                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
            Save Changes
        </button>
        <button type="button" onclick="deleteTask()"
                style="width:100%;font-size:10px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:rgba(204,68,68,0.5);border:1px solid rgba(204,68,68,0.2);background:none;padding:8px;cursor:pointer;transition:all 0.15s;"
                onmouseover="this.style.color='#c44';this.style.borderColor='rgba(204,68,68,0.5)'"
                onmouseout="this.style.color='rgba(204,68,68,0.5)';this.style.borderColor='rgba(204,68,68,0.2)'">
            Delete Task
        </button>
    </form>

    {{-- Comments — separate from the task form so posting one never interferes with unsaved field edits --}}
    <div style="padding:0 1.5rem 1.5rem;border-top:1px solid rgba(255,255,255,0.07);margin-top:0.5rem;">
        <p style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;margin:1.25rem 0 10px;">Comments</p>
        <div id="task-panel-comments-list" style="display:flex;flex-direction:column;gap:10px;margin-bottom:12px;">
            <p style="font-size:11px;color:rgba(255,255,255,0.25);">No comments yet.</p>
        </div>
        <div style="display:flex;gap:8px;">
            <textarea id="task-panel-comment-input" rows="2" placeholder="Add a comment…"
                      style="flex:1;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;resize:vertical;line-height:1.4;"
                      onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                      onblur="this.style.borderColor='rgba(255,255,255,0.08)'"></textarea>
            <button type="button" onclick="submitTaskComment()"
                    style="flex-shrink:0;font-size:10px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#000;background:#fff;border:none;padding:0 14px;cursor:pointer;transition:opacity 0.15s;"
                    onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                Post
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{--  NEW MILESTONE PANEL (slide-in from right)                                --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div id="new-milestone-panel"
     style="position:fixed;right:0;top:0;bottom:0;width:400px;background:#080808;border-left:1px solid rgba(255,255,255,0.08);z-index:52;overflow-y:auto;transform:translateX(100%);transition:transform 0.25s ease;display:flex;flex-direction:column;">

    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.07);flex-shrink:0;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:9px;font-weight:700;letter-spacing:0.25em;text-transform:uppercase;color:#444;">New Milestone</span>
        <button onclick="closeNewMilestonePanel()"
                style="color:rgba(255,255,255,0.3);background:none;border:none;cursor:pointer;font-size:20px;line-height:1;">&times;</button>
    </div>

    <form method="POST" action="{{ route('admin.projects.milestones.store', $project) }}"
          style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
        @csrf

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Title *</label>
            <input type="text" name="title" placeholder="e.g. Strategy alignment" required
                   style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:9px 10px;outline:none;"
                   onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                   onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Status</label>
            <div id="new-milestone-status-btns" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
            <input type="hidden" id="new-milestone-status" name="status" value="planned">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Due date</label>
                <input type="date" name="due_date"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Sort order</label>
                <input type="number" name="sort_order" min="0" value="0"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
        </div>

        <div>
            <label style="display:flex;align-items:center;gap:8px;font-size:11px;color:rgba(255,255,255,0.6);cursor:pointer;">
                <input type="checkbox" id="new-milestone-payment-required" name="payment_required" value="1" onchange="togglePaymentFields('new-milestone')">
                Payment required to unblock downstream progress
            </label>
        </div>

        <div id="new-milestone-payment-fields" style="display:none;flex-direction:column;gap:1.1rem;">
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Amount (KES)</label>
                <input type="number" name="payment_required_amount" min="0" step="0.01"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:8px 10px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Payment status</label>
                <div id="new-milestone-payment-status-btns" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
                <input type="hidden" id="new-milestone-payment-status" name="payment_status" value="not_applicable">
            </div>
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Blocked reason</label>
            <textarea name="blocked_reason" rows="2" placeholder="Optional — overrides the default gating message"
                      style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;resize:vertical;line-height:1.5;"
                      onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                      onblur="this.style.borderColor='rgba(255,255,255,0.08)'"></textarea>
        </div>

        <div>
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Description</label>
            <textarea name="description" rows="3"
                      style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;resize:vertical;line-height:1.5;"
                      onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                      onblur="this.style.borderColor='rgba(255,255,255,0.08)'"></textarea>
        </div>

        <button type="submit"
                style="width:100%;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#000;background:#fff;border:none;padding:11px;cursor:pointer;margin-top:4px;transition:opacity 0.15s;"
                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
            Create Milestone
        </button>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{--  MILESTONE DETAIL PANEL (slide-in from right)                             --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div id="milestone-panel"
     style="position:fixed;right:0;top:0;bottom:0;width:400px;background:#080808;border-left:1px solid rgba(255,255,255,0.08);z-index:52;overflow-y:auto;transform:translateX(100%);transition:transform 0.25s ease;display:flex;flex-direction:column;">

    <div style="padding:1.25rem 1.5rem 0;border-bottom:1px solid rgba(255,255,255,0.07);flex-shrink:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;">Milestone</span>
                <span id="milestone-panel-saved" style="font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#4c4;display:none;">✓ Saved</span>
            </div>
            <button onclick="closeMilestonePanel()"
                    style="color:rgba(255,255,255,0.3);background:none;border:none;cursor:pointer;font-size:20px;line-height:1;">&times;</button>
        </div>
    </div>

    <div id="milestone-panel-gate-warning" style="display:none;margin:1rem 1.5rem 0;padding:10px 12px;border:1px solid rgba(204,68,68,0.3);background:rgba(204,68,68,0.08);">
        <p style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#c44;">⛔ Payment gate active</p>
        <p id="milestone-panel-gate-reason" style="font-size:11px;color:rgba(255,255,255,0.6);margin-top:4px;line-height:1.4;"></p>
    </div>

    <form id="milestone-panel-form" method="POST" style="flex:1;overflow-y:auto;padding:1.25rem 1.5rem;">
        @csrf @method('PATCH')
        <input type="hidden" name="_milestone_id" id="milestone-panel-id">

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Title</label>
            <input type="text" id="milestone-panel-title" name="title"
                   style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:8px 10px;outline:none;"
                   onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                   onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Status</label>
            <div id="milestone-panel-status-btns" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
            <input type="hidden" id="milestone-panel-status" name="status">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:1.25rem;">
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Due date</label>
                <input type="date" id="milestone-panel-due-date" name="due_date"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
            <div>
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Sort order</label>
                <input type="number" id="milestone-panel-sort-order" name="sort_order" min="0"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:11px;padding:7px 8px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="display:flex;align-items:center;gap:8px;font-size:11px;color:rgba(255,255,255,0.6);cursor:pointer;">
                <input type="checkbox" id="milestone-panel-payment-required" name="payment_required" value="1" onchange="togglePaymentFields('milestone-panel')">
                Payment required to unblock downstream progress
            </label>
        </div>

        <div id="milestone-panel-payment-fields" style="display:none;flex-direction:column;gap:1.25rem;">
            <div style="margin-bottom:1.25rem;">
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Amount (KES)</label>
                <input type="number" id="milestone-panel-amount" name="payment_required_amount" min="0" step="0.01"
                       style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:13px;padding:8px 10px;outline:none;font-family:monospace;"
                       onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                       onblur="this.style.borderColor='rgba(255,255,255,0.08)'">
            </div>
            <div style="margin-bottom:1.25rem;">
                <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Payment status</label>
                <div id="milestone-panel-payment-status-btns" style="display:flex;flex-wrap:wrap;gap:6px;"></div>
                <input type="hidden" id="milestone-panel-payment-status" name="payment_status">
            </div>
        </div>

        <div style="margin-bottom:1.25rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Blocked reason</label>
            <textarea id="milestone-panel-blocked-reason" name="blocked_reason" rows="2" placeholder="Optional — overrides the default gating message"
                      style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;resize:vertical;line-height:1.5;"
                      onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                      onblur="this.style.borderColor='rgba(255,255,255,0.08)'"></textarea>
        </div>

        <div style="margin-bottom:1.5rem;">
            <label style="font-size:9px;font-weight:700;letter-spacing:0.2em;text-transform:uppercase;color:#444;display:block;margin-bottom:6px;">Description</label>
            <textarea id="milestone-panel-desc" name="description" rows="3"
                      style="width:100%;background:#050505;border:1px solid rgba(255,255,255,0.08);color:#e8e8ec;font-size:12px;padding:8px 10px;outline:none;resize:vertical;line-height:1.5;"
                      onfocus="this.style.borderColor='rgba(255,255,255,0.25)'"
                      onblur="this.style.borderColor='rgba(255,255,255,0.08)'"></textarea>
        </div>

        <button type="submit"
                style="width:100%;font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#000;background:#fff;border:none;padding:10px;cursor:pointer;transition:opacity 0.15s;"
                onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
            Save Changes
        </button>
    </form>
</div>

{{-- Shared backdrop for all panels --}}
<div id="task-panel-backdrop"
     onclick="closePanel(); closeNewTaskPanel(); closeMilestonePanel(); closeNewMilestonePanel();"
     style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:49;display:none;"></div>

@push('scripts')
<script>
var _currentTaskId = null;
var ganttColWidth = {{ $colWidth }};
var ganttWeekDates = @json(array_map(fn ($week) => $week->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString(), $weeks));
var resizeEndpointTemplate = @json(route('admin.projects.tasks.timeline', [$project, '__TASK__']));
var activeResize = null;
var suppressTaskOpenUntil = 0;

var statusOpts = [
    {val:'todo',       label:'To do',      color:'rgba(255,255,255,0.3)'},
    {val:'in_progress',label:'In progress',color:'#7af'},
    {val:'review',     label:'Review',     color:'#fa7'},
    {val:'done',       label:'Done',       color:'#4c4'},
    {val:'blocked',    label:'Blocked',    color:'#c44'},
];
var priorityOpts = [
    {val:'low',    label:'Low',    color:'#555'},
    {val:'medium', label:'Medium', color:'#ca7'},
    {val:'high',   label:'High',   color:'#c44'},
];

function renderToggleBtns(containerId, hiddenId, opts, current) {
    var c = document.getElementById(containerId);
    var h = document.getElementById(hiddenId);
    c.innerHTML = '';
    opts.forEach(function(opt) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = opt.label;
        var isActive = current === opt.val;
        btn.style.cssText = 'font-size:9px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;padding:5px 11px;border:1px solid;background:none;cursor:pointer;transition:all 0.12s;' +
            (isActive ? 'color:' + opt.color + ';border-color:' + opt.color + ';' : 'color:rgba(255,255,255,0.25);border-color:rgba(255,255,255,0.1);');
        btn.addEventListener('click', function() {
            h.value = opt.val;
            renderToggleBtns(containerId, hiddenId, opts, opt.val);
        });
        c.appendChild(btn);
    });
}

function openTask(eventOrTask, maybeTask) {
    var task = maybeTask || eventOrTask;
    if (maybeTask && Date.now() < suppressTaskOpenUntil) return;

    closeNewTaskPanel(); // close create panel if open
    _currentTaskId = task.id;

    document.getElementById('panel-task-id').value  = task.id;
    document.getElementById('panel-title').value     = task.title || '';
    document.getElementById('panel-desc').value      = task.description || '';
    document.getElementById('panel-hours').value     = task.estimated_hours || '';
    document.getElementById('panel-start-date').value = task.start_date ? task.start_date.substring(0, 10) : '';
    document.getElementById('panel-due-date').value   = task.due_date   ? task.due_date.substring(0, 10)   : '';

    // Assignee
    var sel = document.getElementById('panel-assignee');
    sel.value = task.assigned_to != null ? String(task.assigned_to) : '';

    // Milestone
    var mSel = document.getElementById('panel-milestone');
    mSel.value = task.project_milestone_id != null ? String(task.project_milestone_id) : '';

    // Toggle buttons
    renderToggleBtns('panel-status-btns',   'panel-status',   statusOpts,   task.status   || 'todo');
    renderToggleBtns('panel-priority-btns', 'panel-priority', priorityOpts, task.priority || 'medium');

    document.getElementById('panel-status').value   = task.status   || 'todo';
    document.getElementById('panel-priority').value = task.priority || 'medium';

    // Wire up the form action
    var form = document.getElementById('task-panel-form');
    form.action = '/admin/projects/{{ $project->id }}/tasks/' + task.id;

    // Show panel
    document.getElementById('task-panel').style.transform = 'translateX(0)';
    document.getElementById('task-panel-backdrop').style.display = 'block';
    document.getElementById('panel-saved').style.display = 'none';

    loadTaskComments(task.id);
}

function closePanel() {
    document.getElementById('task-panel').style.transform = 'translateX(100%)';
    document.getElementById('task-panel-backdrop').style.display = 'none';
    _currentTaskId = null;
}

// ── Task comments ────────────────────────────────────────────────────────
function renderTaskComments(comments) {
    var list = document.getElementById('task-panel-comments-list');
    if (!comments.length) {
        list.innerHTML = '<p style="font-size:11px;color:rgba(255,255,255,0.25);">No comments yet.</p>';
        return;
    }
    list.innerHTML = comments.map(function (c) {
        var when = new Date(c.created_at).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        var authorColor = c.is_client ? '#7af' : 'rgba(255,255,255,0.6)';
        return '<div style="border-left:2px solid rgba(255,255,255,0.08);padding:2px 0 2px 10px;">' +
            '<div style="display:flex;align-items:baseline;gap:8px;margin-bottom:2px;">' +
            '<span style="font-size:10px;font-weight:700;color:' + authorColor + ';">' + escapeHtml(c.author) + '</span>' +
            '<span style="font-size:9px;color:rgba(255,255,255,0.25);">' + when + '</span>' +
            '</div>' +
            '<p style="font-size:12px;color:rgba(255,255,255,0.75);line-height:1.4;white-space:pre-line;">' + escapeHtml(c.body) + '</p>' +
            '</div>';
    }).join('');
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

function loadTaskComments(taskId) {
    var list = document.getElementById('task-panel-comments-list');
    list.innerHTML = '<p style="font-size:11px;color:rgba(255,255,255,0.25);">Loading…</p>';
    fetch('/admin/projects/{{ $project->id }}/tasks/' + taskId + '/comments', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(renderTaskComments)
        .catch(function () { list.innerHTML = '<p style="font-size:11px;color:#c44;">Could not load comments.</p>'; });
}

function submitTaskComment() {
    if (!_currentTaskId) return;
    var input = document.getElementById('task-panel-comment-input');
    var body = input.value.trim();
    if (!body) return;

    var token = document.querySelector('meta[name="csrf-token"]').content;
    fetch('/admin/projects/{{ $project->id }}/tasks/' + _currentTaskId + '/comments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ body: body }),
    })
        .then(function (r) { return r.json(); })
        .then(function () {
            input.value = '';
            loadTaskComments(_currentTaskId);
        });
}

function deleteTask() {
    if (!_currentTaskId || !confirm('Delete this task?')) return;
    var token = document.querySelector('meta[name="csrf-token"]').content;
    fetch('/admin/projects/{{ $project->id }}/tasks/' + _currentTaskId, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_method=DELETE',
    }).then(function() {
        closePanel();
        location.reload();
    });
}

// Intercept form submit → AJAX PATCH then reload
document.getElementById('task-panel-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = e.target;
    var data = new FormData(form);
    // FormData already has _method=PATCH from the hidden field
    fetch(form.action, { method: 'POST', body: data })
        .then(function() {
            var saved = document.getElementById('panel-saved');
            saved.style.display = 'inline';
            setTimeout(function() { saved.style.display = 'none'; }, 1800);
            location.reload();
        });
});

function bindTaskResizeHandles() {
    document.querySelectorAll('[data-task-resize-handle], [data-task-shift-handle]').forEach(function(handle) {
        handle.addEventListener('mousedown', startTaskResize);
    });
}

function addDaysToIsoDate(isoDate, days) {
    if (!isoDate) return null;
    var parts = isoDate.split('-').map(Number);
    var utcDate = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
    utcDate.setUTCDate(utcDate.getUTCDate() + days);
    return utcDate.toISOString().slice(0, 10);
}

function startTaskResize(e) {
    e.preventDefault();
    e.stopPropagation();

    var handle = e.currentTarget;
    var bar = handle.closest('[data-task-bar]');
    if (!bar) return;

    activeResize = {
        bar: bar,
        edge: handle.hasAttribute('data-task-shift-handle') ? 'left' : 'right',
        startX: e.clientX,
        originalStartDate: bar.dataset.startDate || null,
        originalDueDate: bar.dataset.dueDate || null,
        startLeft: parseInt(bar.dataset.defaultLeft || bar.style.left, 10),
        startWidth: parseInt(bar.dataset.defaultWidth || bar.offsetWidth, 10),
        startIndex: parseInt(bar.dataset.startIndex, 10),
        initialEndIndex: parseInt(bar.dataset.endIndex, 10),
    };

    suppressTaskOpenUntil = Date.now() + 400;
    bar.style.transition = 'none';
    document.body.style.userSelect = 'none';
    document.body.style.cursor = 'ew-resize';
    window.addEventListener('mousemove', onTaskResizeMove);
    window.addEventListener('mouseup', finishTaskResize);
}

function onTaskResizeMove(e) {
    if (!activeResize) return;

    var deltaX = e.clientX - activeResize.startX;
    var deltaWeeks = Math.round(deltaX / ganttColWidth);

    if (activeResize.edge === 'left') {
        var spanWeeks = (activeResize.initialEndIndex - activeResize.startIndex) + 1;
        var nextStartIndex = Math.max(0, activeResize.startIndex + deltaWeeks);
        var nextEndIndex = nextStartIndex + spanWeeks - 1;

        activeResize.bar.style.left = (nextStartIndex * ganttColWidth) + 'px';
        activeResize.bar.dataset.startIndex = String(nextStartIndex);
        activeResize.bar.dataset.endIndex = String(nextEndIndex);
        return;
    }

    var nextEndIndex = Math.max(activeResize.startIndex, activeResize.initialEndIndex + deltaWeeks);
    var weeksSpanned = (nextEndIndex - activeResize.startIndex) + 1;
    var nextWidth = Math.max(ganttColWidth - 6, (weeksSpanned * ganttColWidth) - 6);

    activeResize.bar.style.width = nextWidth + 'px';
    activeResize.bar.dataset.endIndex = String(nextEndIndex);
}

function finishTaskResize(e) {
    if (!activeResize) return;

    var state = activeResize;
    activeResize = null;
    window.removeEventListener('mousemove', onTaskResizeMove);
    window.removeEventListener('mouseup', finishTaskResize);
    document.body.style.userSelect = '';
    document.body.style.cursor = '';

    var nextStartIndex = parseInt(state.bar.dataset.startIndex, 10);
    var nextEndIndex = parseInt(state.bar.dataset.endIndex, 10);
    if (nextStartIndex === state.startIndex && nextEndIndex === state.initialEndIndex) {
        state.bar.style.transition = 'background 0.12s';
        return;
    }

    var shiftedDays = (nextStartIndex - state.startIndex) * 7;
    var startDate = state.edge === 'left'
        ? addDaysToIsoDate(state.originalStartDate, shiftedDays)
        : undefined;
    var dueDate = state.edge === 'left'
        ? addDaysToIsoDate(state.originalDueDate, shiftedDays)
        : ganttWeekDates[nextEndIndex];
    if (!dueDate || (state.edge === 'left' && !startDate)) {
        state.bar.style.left = state.startLeft + 'px';
        state.bar.style.width = state.startWidth + 'px';
        state.bar.dataset.startIndex = String(state.startIndex);
        state.bar.dataset.endIndex = String(state.initialEndIndex);
        state.bar.style.transition = 'background 0.12s';
        return;
    }

    var token = document.querySelector('meta[name="csrf-token"]').content;
    fetch(resizeEndpointTemplate.replace('__TASK__', state.bar.dataset.taskId), {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            start_date: state.edge === 'left' ? startDate : undefined,
            due_date: dueDate,
        }),
    }).then(function(response) {
        if (!response.ok) throw new Error('Resize failed');
        if (state.edge === 'left') {
            state.bar.dataset.startDate = startDate;
            state.bar.dataset.dueDate = dueDate;
        } else {
            state.bar.dataset.dueDate = dueDate;
        }
        state.bar.dataset.defaultLeft = state.bar.style.left.replace('px', '');
        state.bar.dataset.defaultWidth = state.bar.style.width.replace('px', '');
        location.reload();
    }).catch(function() {
        state.bar.style.left = state.startLeft + 'px';
        state.bar.style.width = state.startWidth + 'px';
        state.bar.dataset.startIndex = String(state.startIndex);
        state.bar.dataset.endIndex = String(state.initialEndIndex);
        state.bar.dataset.defaultLeft = String(state.startLeft);
        state.bar.dataset.defaultWidth = String(state.startWidth);
        state.bar.style.transition = 'background 0.12s';
        alert('Task resize failed. The end date was not updated.');
    });
}

// New task panel
function openNewTaskPanel() {
    closePanel(); // close edit panel if open
    renderToggleBtns('new-task-status-btns',   'new-task-status',   statusOpts,   'todo');
    renderToggleBtns('new-task-priority-btns', 'new-task-priority', priorityOpts, 'medium');
    document.getElementById('new-task-panel').style.transform = 'translateX(0)';
    document.getElementById('task-panel-backdrop').style.display = 'block';
}

function closeNewTaskPanel() {
    document.getElementById('new-task-panel').style.transform = 'translateX(100%)';
    document.getElementById('task-panel-backdrop').style.display = 'none';
}

// ── Milestone panels ─────────────────────────────────────────────────────
var milestoneStatusOpts = [
    {val:'planned',           label:'Planned',      color:'rgba(255,255,255,0.3)'},
    {val:'in_progress',       label:'In progress',  color:'#7af'},
    {val:'blocked',           label:'Blocked',      color:'#c44'},
    {val:'ready_for_payment', label:'Ready to pay', color:'#ca7'},
    {val:'paid',              label:'Paid',         color:'#4c4'},
    {val:'released',          label:'Released',     color:'#7c9'},
    {val:'completed',         label:'Completed',    color:'#4c4'},
];
var paymentStatusOpts = [
    {val:'not_applicable', label:'N/A',      color:'rgba(255,255,255,0.3)'},
    {val:'pending',        label:'Pending',  color:'#ca7'},
    {val:'invoiced',       label:'Invoiced', color:'#7af'},
    {val:'paid',           label:'Paid',     color:'#4c4'},
];

function togglePaymentFields(prefix) {
    var checked = document.getElementById(prefix + '-payment-required').checked;
    document.getElementById(prefix + '-payment-fields').style.display = checked ? 'flex' : 'none';
}

function openNewMilestonePanel() {
    closeMilestonePanel();
    renderToggleBtns('new-milestone-status-btns', 'new-milestone-status', milestoneStatusOpts, 'planned');
    renderToggleBtns('new-milestone-payment-status-btns', 'new-milestone-payment-status', paymentStatusOpts, 'not_applicable');
    document.getElementById('new-milestone-payment-fields').style.display = 'none';
    document.getElementById('new-milestone-panel').style.transform = 'translateX(0)';
    document.getElementById('task-panel-backdrop').style.display = 'block';
}

function closeNewMilestonePanel() {
    document.getElementById('new-milestone-panel').style.transform = 'translateX(100%)';
    document.getElementById('task-panel-backdrop').style.display = 'none';
}

function openMilestone(milestone) {
    closeNewMilestonePanel();

    document.getElementById('milestone-panel-id').value = milestone.id;
    document.getElementById('milestone-panel-title').value = milestone.title || '';
    document.getElementById('milestone-panel-desc').value = milestone.description || '';
    document.getElementById('milestone-panel-due-date').value = milestone.due_date ? milestone.due_date.substring(0, 10) : '';
    document.getElementById('milestone-panel-sort-order').value = milestone.sort_order || 0;
    document.getElementById('milestone-panel-blocked-reason').value = milestone.blocked_reason || '';

    var paymentRequired = !!milestone.payment_required;
    document.getElementById('milestone-panel-payment-required').checked = paymentRequired;
    document.getElementById('milestone-panel-amount').value = milestone.payment_required_amount || '';
    document.getElementById('milestone-panel-payment-fields').style.display = paymentRequired ? 'flex' : 'none';

    renderToggleBtns('milestone-panel-status-btns', 'milestone-panel-status', milestoneStatusOpts, milestone.status || 'planned');
    renderToggleBtns('milestone-panel-payment-status-btns', 'milestone-panel-payment-status', paymentStatusOpts, milestone.payment_status || 'not_applicable');

    var warning = document.getElementById('milestone-panel-gate-warning');
    if (milestone.is_blocked) {
        document.getElementById('milestone-panel-gate-reason').textContent = milestone.blocking_reason || '';
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }

    var form = document.getElementById('milestone-panel-form');
    form.action = '/admin/projects/{{ $project->id }}/milestones/' + milestone.id;

    document.getElementById('milestone-panel').style.transform = 'translateX(0)';
    document.getElementById('task-panel-backdrop').style.display = 'block';
    document.getElementById('milestone-panel-saved').style.display = 'none';
}

function closeMilestonePanel() {
    document.getElementById('milestone-panel').style.transform = 'translateX(100%)';
    document.getElementById('task-panel-backdrop').style.display = 'none';
}

document.getElementById('milestone-panel-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var form = e.target;
    var data = new FormData(form);
    fetch(form.action, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } })
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (body) {
                    alert(body.message || 'Could not save this milestone.');
                });
            }
            var saved = document.getElementById('milestone-panel-saved');
            saved.style.display = 'inline';
            setTimeout(function () { saved.style.display = 'none'; }, 1800);
            location.reload();
        });
});

// Close panels on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closePanel(); closeNewTaskPanel(); closeMilestonePanel(); closeNewMilestonePanel(); }
});

bindTaskResizeHandles();
</script>
@endpush

@endsection
