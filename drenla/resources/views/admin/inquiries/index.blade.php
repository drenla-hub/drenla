@extends('layouts.admin')

@section('title', 'Inquiries')

@section('content')
<div class="space-y-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="border-b border-[#1a1a1a] pb-8">
        <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Delivery</p>
        <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Inquiries</h1>
        <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Captured through the public contact endpoint.</p>
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────────── --}}
    <div class="border border-[#1a1a1a]">
        <table class="min-w-full text-left">

            <thead class="border-b border-[#1a1a1a] bg-[#080808]">
                <tr>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Contact</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Company</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Subject</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Status</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Assignee</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Date</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-[#0e0e0e]">
                @forelse ($inquiries as $inquiry)
                    <tr class="transition-colors hover:bg-[#080808]">

                        <td class="px-5 py-4">
                            <p class="text-[13px] font-medium text-white">{{ $inquiry->name }}</p>
                            <p class="mt-0.5 text-[11px] text-[#444]">{{ $inquiry->email }}</p>
                        </td>

                        <td class="px-5 py-4 text-[13px] text-[#777]">{{ $inquiry->company ?: '—' }}</td>

                        <td class="px-5 py-4 text-[13px] text-[#777] max-w-[200px] truncate">{{ $inquiry->subject ?: '—' }}</td>

                        <td class="px-5 py-4">
                            @php
                                $iMap = ['new' => '#777', 'qualified' => '#7c9', 'proposal_sent' => '#99f', 'won' => '#4c4', 'lost' => '#c44'];
                                $iColor = $iMap[$inquiry->status] ?? '#555';
                            @endphp
                            <span class="text-[10px] font-bold uppercase tracking-[0.15em]"
                                  style="color: {{ $iColor }}">{{ $inquiry->status }}</span>
                        </td>

                        <td class="px-5 py-4 text-[13px] text-[#777]">{{ $inquiry->assignee?->name ?: '—' }}</td>

                        <td class="px-5 py-4 text-[11px] text-[#444]">{{ $inquiry->created_at->format('d M Y') }}</td>

                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.inquiries.show', $inquiry) }}"
                               class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">Open</a>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-[12px] text-[#444]">No inquiries yet.</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
@endsection
