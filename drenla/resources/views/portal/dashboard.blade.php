@extends('layouts.portal')

@section('title', 'Overview')

@section('content')
<div class="space-y-14">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-[0.35em] text-[#8a7f6c]">Welcome back</p>
        <h1 class="mt-3 text-[32px] font-light tracking-[-0.02em] text-[#161219]">{{ $client->name }}</h1>
        @if ($client->company_name)
            <p class="mt-2 text-[13px] text-[#5f5648]">{{ $client->company_name }}</p>
        @endif
    </div>

    <section>
        <div class="flex items-baseline justify-between border-b border-[#e4dfd6] pb-4">
            <h2 class="text-[16px] font-light text-[#161219]">Projects</h2>
            <a href="{{ route('portal.projects.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a7f6c] hover:text-[#161219]">View all</a>
        </div>

        @if ($projects->isEmpty())
            <p class="mt-6 text-[13px] text-[#8a7f6c]">No active projects yet.</p>
        @else
            <div class="mt-4 divide-y divide-[#e4dfd6]">
                @foreach ($projects as $project)
                    <a href="{{ route('portal.projects.show', $project) }}" class="flex items-center justify-between py-5 transition-colors hover:bg-white/60">
                        <div>
                            <p class="text-[14px] font-medium text-[#161219]">{{ $project->title }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-[0.15em] text-[#8a7f6c]">{{ str_replace('_', ' ', $project->status) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[13px] text-[#5f5648]">{{ $project->progress_percentage }}% complete</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <div class="flex items-baseline justify-between border-b border-[#e4dfd6] pb-4">
            <h2 class="text-[16px] font-light text-[#161219]">Proposals</h2>
            <a href="{{ route('portal.proposals.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a7f6c] hover:text-[#161219]">View all</a>
        </div>

        @if ($proposals->isEmpty())
            <p class="mt-6 text-[13px] text-[#8a7f6c]">No proposals shared yet.</p>
        @else
            <div class="mt-4 divide-y divide-[#e4dfd6]">
                @foreach ($proposals as $proposal)
                    <a href="{{ route('portal.proposals.show', $proposal) }}" class="flex items-center justify-between py-5 transition-colors hover:bg-white/60">
                        <div>
                            <p class="text-[14px] font-medium text-[#161219]">{{ $proposal->title }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-[0.15em] text-[#8a7f6c]">{{ $proposal->reference_number }}</p>
                        </div>
                        <p class="text-[13px] text-[#5f5648]">{{ $proposal->issue_date?->format('d M Y') }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <div class="flex items-baseline justify-between border-b border-[#e4dfd6] pb-4">
            <h2 class="text-[16px] font-light text-[#161219]">Recent finance documents</h2>
            <a href="{{ route('portal.finance.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a7f6c] hover:text-[#161219]">View all</a>
        </div>

        @if ($financeDocuments->isEmpty())
            <p class="mt-6 text-[13px] text-[#8a7f6c]">No invoices, estimates, or receipts yet.</p>
        @else
            <div class="mt-4 divide-y divide-[#e4dfd6]">
                @foreach ($financeDocuments as $document)
                    <a href="{{ route('portal.finance.show', $document) }}" class="flex items-center justify-between py-5 transition-colors hover:bg-white/60">
                        <div>
                            <p class="text-[14px] font-medium text-[#161219]">{{ ucfirst($document->type) }} &middot; {{ $document->reference_number }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-[0.15em] text-[#8a7f6c]">{{ $document->status }}</p>
                        </div>
                        <p class="text-[13px] text-[#5f5648]">{{ $document->currency }} {{ number_format((float) $document->total_amount, 2) }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
