@extends('layouts.portal')

@section('title', 'Proposals')

@section('content')
<div class="space-y-8">
    <div>
        <a href="{{ route('portal.dashboard') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a7f6c] hover:text-[#161219]">&larr; Overview</a>
        <h1 class="mt-5 text-[28px] font-light tracking-[-0.02em] text-[#161219]">Proposals</h1>
    </div>

    @if ($proposals->isEmpty())
        <p class="text-[13px] text-[#8a7f6c]">No proposals shared yet.</p>
    @else
        <div class="divide-y divide-[#e4dfd6] border-t border-[#e4dfd6]">
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
</div>
@endsection
