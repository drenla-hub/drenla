@extends('layouts.admin')

@section('title', 'Finance')

@section('content')
<div class="space-y-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Commercial</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Finance</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Quotations, invoices, and receipts. Link documents to project briefs and projects for PDF and commercial tracking.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.finance.create', ['type' => 'quotation']) }}"
               class="inline-flex items-center gap-2 border border-[#333] px-5 py-2.5
                      text-[10px] font-bold uppercase tracking-[0.2em] text-[#999]
                      transition hover:border-white hover:text-white">
                + Quotation
            </a>
            <a href="{{ route('admin.finance.create', ['type' => 'invoice']) }}"
               class="inline-flex items-center gap-2 border border-white bg-white px-5 py-2.5
                      text-[10px] font-bold uppercase tracking-[0.2em] text-black
                      transition hover:bg-transparent hover:text-white">
                + Invoice
            </a>
        </div>
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────────── --}}
    @if ($documents->isEmpty())
        <p class="py-12 text-center text-[12px] text-[#444]">No financial documents yet.</p>
    @else
        <div class="border border-[#1a1a1a]">
            <table class="min-w-full text-left">
                <thead class="border-b border-[#1a1a1a] bg-[#080808]">
                    <tr>
                        <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Reference</th>
                        <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Type</th>
                        <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Client</th>
                        <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Linked project brief</th>
                        <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Total</th>
                        <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Status</th>
                        <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Date</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#0e0e0e]">
                    @foreach ($documents as $doc)
                        @php
                            $sMap = ['draft' => '#555', 'sent' => '#7af', 'accepted' => '#7c9', 'paid' => '#4c4', 'cancelled' => '#c44'];
                            $sColor = $sMap[$doc->status] ?? '#555';
                        @endphp
                        <tr class="transition-colors hover:bg-[#080808]">
                            <td class="px-5 py-4 font-mono text-[12px] text-[#888]">{{ $doc->reference_number }}</td>
                            <td class="px-5 py-4 text-[10px] font-bold uppercase tracking-[0.15em] text-[#666]">{{ ucfirst($doc->type) }}</td>
                            <td class="px-5 py-4 text-[13px] text-[#999]">{{ $doc->client?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-[12px] text-[#666]">
                                @if ($doc->proposal)
                                    <a href="{{ route('admin.proposals.edit', $doc->proposal) }}"
                                       class="hover:text-white transition-colors">{{ $doc->proposal->reference_number ?: $doc->proposal->title }}</a>
                                @else
                                    <span class="text-[#333]">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-[13px] font-medium text-white">
                                {{ $doc->currency }} {{ number_format((float) $doc->total_amount, 0) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em]"
                                      style="color: {{ $sColor }}">{{ ucfirst($doc->status) }}</span>
                            </td>
                            <td class="px-5 py-4 text-[11px] text-[#444]">{{ $doc->issue_date?->format('d M Y') }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.finance.edit', $doc) }}"
                                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
