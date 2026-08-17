@extends('layouts.admin')

@section('title', 'Projects')

@section('content')
<div class="space-y-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Delivery</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Projects</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Active engagements, tasks, and payment milestone tracking.</p>
        </div>
        <a href="{{ route('admin.projects.create') }}"
           class="inline-flex items-center gap-2 border border-white bg-white px-5 py-2.5
                  text-[10px] font-bold uppercase tracking-[0.2em] text-black
                  transition hover:bg-transparent hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New project
        </a>
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────────── --}}
    @if ($projects->isEmpty())
        <p class="py-16 text-center text-[12px] text-[#444]">No projects yet.</p>
    @else
        <div class="border border-[#1a1a1a]">
            {{-- Col header --}}
            <div class="grid border-b border-[#1a1a1a] bg-[#080808] px-5 py-3"
                 style="grid-template-columns:1fr 72px 72px 72px 140px 180px 120px">
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Project</span>
                <span class="text-center text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Total</span>
                <span class="text-center text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Done</span>
                <span class="text-center text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Active</span>
                <span class="text-center text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Invoice</span>
                <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Progress</span>
                <span></span>
            </div>

            @foreach ($projects as $project)
                @php
                    $tasks     = $project->tasks;
                    $total     = $tasks->count();
                    $done      = $tasks->where('status', 'done')->count();
                    $active    = $tasks->where('status', 'in_progress')->count();
                    $pct       = $total > 0 ? round($done / $total * 100) : 0;
                    $invoice   = $project->financeDocuments->where('type', 'invoice')->sortByDesc('issue_date')->first();

                    $sMap = ['planned' => '#555', 'active' => '#7c9', 'blocked' => '#c44', 'on_hold' => '#ca7', 'completed' => '#4c4'];
                    $sColor = $sMap[$project->status] ?? '#555';
                @endphp

                <div class="grid items-center border-b border-[#0e0e0e] transition-colors hover:bg-[#080808]"
                     style="grid-template-columns:1fr 72px 72px 72px 140px 180px 120px">

                    {{-- Project name --}}
                    <a href="{{ route('admin.projects.show', $project) }}"
                       class="block px-5 py-4">
                        <p class="text-[13px] font-medium text-white">{{ $project->title }}</p>
                        <p class="mt-0.5 text-[11px] text-[#444]">{{ $project->client?->name }}</p>
                        <span class="mt-1 inline-block text-[9px] font-black uppercase tracking-[0.15em]"
                              style="color: {{ $sColor }}">{{ $project->status }}</span>
                    </a>

                    <span class="py-4 text-center text-[17px] font-light text-[#888]">{{ $total ?: '—' }}</span>
                    <span class="py-4 text-center text-[17px] font-light text-[#7c9]">{{ $done ?: '—' }}</span>
                    <span class="py-4 text-center text-[17px] font-light text-[#7af]">{{ $active ?: '—' }}</span>

                    {{-- Linked invoice value --}}
                    <span class="py-4 text-center">
                        @if ($invoice)
                            <span class="block text-[13px] font-medium text-white/80">KES {{ number_format((float) $invoice->total_amount, 0) }}</span>
                            <span class="block text-[9px] text-[#555]">{{ $invoice->reference_number ?: 'Invoice linked' }}</span>
                        @else
                            <span class="text-[#333]">—</span>
                        @endif
                    </span>

                    {{-- Progress bar --}}
                    <div class="py-4 pr-4">
                        @if ($total > 0)
                            <div class="flex items-center gap-3">
                                <div class="relative h-px flex-1 bg-[#1a1a1a]">
                                    <div class="absolute left-0 top-0 h-full bg-white/40 transition-all"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="shrink-0 font-mono text-[10px] text-[#555]">{{ $pct }}%</span>
                            </div>
                        @else
                            <span class="text-[11px] text-[#333]">No tasks</span>
                        @endif
                    </div>

                    {{-- Open --}}
                    <div class="py-4 pr-5">
                        <a href="{{ route('admin.projects.show', $project) }}"
                           class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
                            Open →
                        </a>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
