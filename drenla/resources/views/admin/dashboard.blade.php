@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-10">

    {{-- ── Page header ────────────────────────────────────────────────────── --}}
    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Overview</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Control Plane</h1>
        </div>
        <p class="text-[11px] tracking-[0.1em] text-[#444]">{{ now()->format('D, d M Y') }}</p>
    </div>

    {{-- ── KPI grid ────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-px border border-[#1a1a1a] bg-[#1a1a1a] md:grid-cols-4">
        @foreach ([
            ['label' => 'Clients',              'value' => $stats['clients'],                  'unit' => null],
            ['label' => 'Inquiries',             'value' => $stats['inquiries'],                'unit' => null],
            ['label' => 'Active Projects',       'value' => $stats['active_projects'],          'unit' => null],
            ['label' => 'Open Proposals',        'value' => $stats['open_proposals'],           'unit' => null],
            ['label' => 'Case Studies',          'value' => $stats['published_case_studies'],   'unit' => null],
            ['label' => 'Articles',              'value' => $stats['published_articles'],       'unit' => null],
            ['label' => 'Focus Areas',           'value' => $stats['published_focus_areas'],    'unit' => null],
            ['label' => 'Outstanding',           'value' => number_format($stats['outstanding_invoice_total'], 0), 'unit' => 'KES'],
        ] as $kpi)
            <div class="bg-black px-6 py-5">
                <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">{{ $kpi['label'] }}</p>
                <p class="mt-4 flex items-baseline gap-1.5">
                    <span class="text-[36px] font-light leading-none tracking-[-0.03em] text-white">{{ $kpi['value'] }}</span>
                    @if ($kpi['unit'])
                        <span class="text-[10px] font-bold tracking-[0.2em] text-[#555]">{{ $kpi['unit'] }}</span>
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    {{-- ── Activity panels ─────────────────────────────────────────────────── --}}
    @php
        $user = auth()->user();
        $canClients = $user?->hasAnyPermission(\App\Support\Permission::VIEW_CLIENTS, \App\Support\Permission::MANAGE_CLIENTS) ?? false;
        $canProjects = $user?->hasAnyPermission(\App\Support\Permission::VIEW_PROJECTS, \App\Support\Permission::MANAGE_PROJECTS) ?? false;
        $canFinance = $user?->hasAnyPermission(\App\Support\Permission::VIEW_FINANCE, \App\Support\Permission::MANAGE_FINANCE) ?? false;
    @endphp

    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Inquiries --}}
        @if ($canClients)
            <section class="border border-[#1a1a1a]">
                <div class="flex items-center justify-between border-b border-[#1a1a1a] px-5 py-3.5">
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Recent Inquiries</p>
                    <a href="{{ route('admin.inquiries.index') }}"
                       class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
                        View all →
                    </a>
                </div>
                <div class="divide-y divide-[#111]">
                    @forelse ($recentInquiries as $inquiry)
                        <a href="{{ route('admin.inquiries.show', $inquiry) }}"
                           class="flex items-start justify-between gap-4 px-5 py-4 hover:bg-[#080808] transition-colors">
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-white truncate">{{ $inquiry->name }}</p>
                                <p class="mt-0.5 text-[11px] text-[#555] truncate">{{ $inquiry->email }}</p>
                            </div>
                            @php
                                $iColors = ['new' => '#666', 'qualified' => '#7c9', 'proposal_sent' => '#99f', 'won' => '#4c4', 'lost' => '#c44'];
                                $iColor  = $iColors[$inquiry->status] ?? '#555';
                            @endphp
                            <span class="shrink-0 text-[9px] font-black uppercase tracking-[0.2em]"
                                  style="color: {{ $iColor }}">{{ $inquiry->status }}</span>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-[12px] text-[#444]">No inquiries yet.</p>
                    @endforelse
                </div>
            </section>
        @endif

        {{-- Projects --}}
        @if ($canProjects)
            <section class="border border-[#1a1a1a]">
                <div class="flex items-center justify-between border-b border-[#1a1a1a] px-5 py-3.5">
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Recent Projects</p>
                    <a href="{{ route('admin.projects.index') }}"
                       class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
                        View all →
                    </a>
                </div>
                <div class="divide-y divide-[#111]">
                    @forelse ($recentProjects as $project)
                        <a href="{{ route('admin.projects.show', $project) }}"
                           class="flex items-start justify-between gap-4 px-5 py-4 hover:bg-[#080808] transition-colors">
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-white truncate">{{ $project->title }}</p>
                                <p class="mt-0.5 text-[11px] text-[#555] truncate">{{ $project->client?->name }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                @php
                                    $pColors = ['planning' => '#666', 'active' => '#7c9', 'on_hold' => '#a80', 'completed' => '#4c4', 'cancelled' => '#c44'];
                                    $pColor  = $pColors[$project->status] ?? '#555';
                                @endphp
                                <span class="text-[9px] font-black uppercase tracking-[0.2em]"
                                      style="color: {{ $pColor }}">{{ $project->status }}</span>
                                <p class="mt-1 text-[11px] text-[#444]">{{ $project->progress_percentage }}%</p>
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-[12px] text-[#444]">No projects yet.</p>
                    @endforelse
                </div>
            </section>
        @endif

        {{-- Finance documents --}}
        @if ($canFinance)
            <section class="border border-[#1a1a1a]">
                <div class="flex items-center justify-between border-b border-[#1a1a1a] px-5 py-3.5">
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Finance Documents</p>
                    <a href="{{ route('admin.finance.index') }}"
                       class="text-[10px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
                        View all →
                    </a>
                </div>
                <div class="divide-y divide-[#111]">
                    @forelse ($recentDocuments as $doc)
                        <a href="{{ route('admin.finance.index') }}"
                           class="flex items-start justify-between gap-4 px-5 py-4 hover:bg-[#080808] transition-colors">
                            <div class="min-w-0">
                                <p class="text-[13px] font-medium text-white truncate">{{ $doc->reference_number }}</p>
                                <p class="mt-0.5 text-[11px] text-[#555] truncate">{{ $doc->client?->name }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[#666]">{{ $doc->type }}</span>
                                <p class="mt-1 text-[11px] text-[#444]">{{ number_format($doc->total_amount, 0) }} {{ $doc->currency }}</p>
                            </div>
                        </a>
                    @empty
                        <p class="px-5 py-6 text-[12px] text-[#444]">No documents yet.</p>
                    @endforelse
                </div>
            </section>
        @endif

    </div>

</div>
@endsection
