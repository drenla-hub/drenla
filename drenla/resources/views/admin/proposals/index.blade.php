@extends('layouts.admin')

@section('title', 'Project Briefs')

@section('content')
<div class="space-y-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Commercial</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Project Briefs</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Structured brief workspaces with branded preview and PDF export.</p>
        </div>
        <a href="{{ route('admin.proposals.create') }}"
           class="inline-flex items-center gap-2 border border-white bg-white px-5 py-2.5
                  text-[10px] font-bold uppercase tracking-[0.2em] text-black
                  transition hover:bg-transparent hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New project brief
        </a>
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────────── --}}
    <div class="border border-[#1a1a1a]">
        <table class="min-w-full text-left">

            <thead class="border-b border-[#1a1a1a] bg-[#080808]">
                <tr>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Project Brief</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Client</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Status</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Reference</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Value</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-[#0e0e0e]">
                @forelse ($proposals as $proposal)
                    <tr class="transition-colors hover:bg-[#080808]">

                        <td class="px-5 py-4">
                            <p class="text-[13px] font-medium text-white">{{ $proposal->title }}</p>
                            <p class="mt-0.5 text-[11px] text-[#444]">{{ $proposal->slug }}</p>
                        </td>

                        <td class="px-5 py-4 text-[13px] text-[#777]">
                            {{ $proposal->client?->name ?: '—' }}
                        </td>

                        <td class="px-5 py-4">
                            @php
                                $sMap = ['draft' => '#555', 'sent' => '#7af', 'accepted' => '#4c4', 'declined' => '#c44'];
                                $sColor = $sMap[$proposal->status] ?? '#555';
                                $dMap = ['draft' => '#555', 'final' => '#7c9', 'archived' => '#666'];
                                $dColor = $dMap[$proposal->document_status ?? 'draft'] ?? '#555';
                            @endphp
                            <span class="text-[10px] font-bold uppercase tracking-[0.15em]"
                                  style="color: {{ $sColor }}">{{ $proposal->status }}</span>
                            <p class="mt-0.5 text-[10px] uppercase tracking-[0.12em]"
                               style="color: {{ $dColor }}">{{ $proposal->document_status ?: 'draft' }}</p>
                        </td>

                        <td class="px-5 py-4">
                            <p class="text-[13px] text-[#777]">{{ $proposal->reference_number ?: '—' }}</p>
                            <p class="mt-0.5 text-[11px] text-[#444]">{{ optional($proposal->issue_date)->format('d M Y') ?: '—' }}</p>
                        </td>

                        <td class="px-5 py-4 text-[13px] text-[#777]">
                            {{ $proposal->value ? number_format($proposal->value, 0) : '—' }}
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-5">
                                <a href="{{ route('admin.proposals.preview', $proposal) }}" target="_blank"
                                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#555] hover:text-white transition-colors">Preview</a>
                                <a href="{{ route('admin.proposals.export', $proposal) }}"
                                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#555] hover:text-white transition-colors">PDF</a>
                                <a href="{{ route('admin.proposals.edit', $proposal) }}"
                                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">Edit</a>
                                <form method="POST" action="{{ route('admin.proposals.destroy', $proposal) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">Delete</button>
                                </form>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-[12px] text-[#444]">No project briefs yet.</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
@endsection
