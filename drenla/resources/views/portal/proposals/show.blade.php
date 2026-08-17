@extends('layouts.portal')

@section('title', $proposal->title)

@section('content')
<div class="space-y-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('portal.proposals.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a7f6c] hover:text-[#161219]">&larr; Proposals</a>

            <p class="mt-5 text-[10px] font-semibold uppercase tracking-[0.35em] text-[#8a7f6c]">Proposal &middot; {{ $proposal->reference_number }}</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.02em] text-[#161219]">{{ $proposal->title }}</h1>

            <div class="mt-6 flex flex-wrap gap-x-10 gap-y-2 text-[12px] text-[#5f5648]">
                <span>Issued {{ $proposal->issue_date?->format('d M Y') }}</span>
                @if ($proposal->value)
                    <span>Value KES {{ number_format((float) $proposal->value, 2) }}</span>
                @endif
            </div>
        </div>

        <a href="{{ route('portal.proposals.download', $proposal) }}"
           class="shrink-0 border border-[#161219] px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.15em] text-[#161219] transition-colors hover:bg-[#161219] hover:text-white">
            Download PDF
        </a>
    </div>

    {{-- Same branded document admin sees in "Preview" — embedded read-only via a
         short-lived signed URL to the print route, not a flattened re-render. --}}
    <div style="border:1px solid #e4dfd6;background:#eee9de;">
        <iframe
            src="{{ $printUrl }}"
            title="{{ $proposal->title }}"
            style="width:100%;height:80vh;border:0;display:block;"
            loading="lazy">
        </iframe>
    </div>

    @if ($files->isNotEmpty())
        <div class="border-t border-[#e4dfd6] pt-8">
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Attachments</p>
            <div class="mt-4 divide-y divide-[#e4dfd6]">
                @foreach ($files as $file)
                    <a href="{{ $file->mediaAsset->url }}" target="_blank" rel="noopener" class="flex items-center justify-between py-3 text-[13px] text-[#161219] hover:text-[#8a7f6c]">
                        <span>{{ $file->display_title }}</span>
                        <span class="text-[11px] uppercase tracking-[0.15em] text-[#8a7f6c]">Open</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
