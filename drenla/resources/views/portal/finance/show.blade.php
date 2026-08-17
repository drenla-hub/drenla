@extends('layouts.portal')

@section('title', $document->reference_number)

@section('content')
<div class="space-y-8">
    <div>
        <a href="{{ route('portal.finance.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8a7f6c] hover:text-[#161219]">&larr; Finance documents</a>

        <p class="mt-5 text-[10px] font-semibold uppercase tracking-[0.35em] text-[#8a7f6c]">{{ ucfirst($document->type) }}</p>
        <h1 class="mt-3 text-[28px] font-light tracking-[-0.02em] text-[#161219]">{{ $document->reference_number }}</h1>

        <div class="mt-4 flex flex-wrap gap-x-10 gap-y-2 text-[12px] text-[#5f5648]">
            <span>Issued {{ $document->issue_date?->format('d M Y') }}</span>
            @if ($document->due_date)
                <span>Due {{ $document->due_date->format('d M Y') }}</span>
            @endif
            <span class="uppercase tracking-[0.15em] text-[#8a7f6c]">{{ $document->status }}</span>
        </div>
    </div>

    <div class="border border-[#e4dfd6]">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#e4dfd6] bg-[#f1ede4]">
                    <th class="px-5 py-3 text-left text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Item</th>
                    <th class="px-5 py-3 text-right text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Qty</th>
                    <th class="px-5 py-3 text-right text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Unit</th>
                    <th class="px-5 py-3 text-right text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#eee9de]">
                @foreach ($document->items as $item)
                    <tr>
                        <td class="px-5 py-4">
                            <p class="text-[13px] font-medium text-[#161219]">{{ $item->title }}</p>
                            @if ($item->description)
                                <p class="mt-1 text-[12px] text-[#8a7f6c]">{{ $item->description }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right text-[13px] text-[#3a3327]">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                        <td class="px-5 py-4 text-right text-[13px] text-[#3a3327]">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="px-5 py-4 text-right text-[13px] text-[#161219]">{{ number_format((float) $item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="space-y-2 border-t border-[#e4dfd6] px-5 py-5">
            <div class="flex justify-between text-[13px] text-[#5f5648]">
                <span>Subtotal</span>
                <span>{{ $document->currency }} {{ number_format((float) $document->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-[13px] text-[#5f5648]">
                <span>Tax</span>
                <span>{{ $document->currency }} {{ number_format((float) $document->tax_amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-[15px] font-medium text-[#161219]">
                <span>Total</span>
                <span>{{ $document->currency }} {{ number_format((float) $document->total_amount, 2) }}</span>
            </div>
            <div class="flex justify-between text-[13px] text-[#5f5648]">
                <span>Paid</span>
                <span>{{ $document->currency }} {{ number_format((float) $document->amount_paid, 2) }}</span>
            </div>
            <div class="flex justify-between border-t border-[#e4dfd6] pt-2 text-[15px] font-medium" style="color: hsl(260 60% 45%)">
                <span>Balance due</span>
                <span>{{ $document->currency }} {{ number_format((float) $document->balance, 2) }}</span>
            </div>
        </div>
    </div>

    @if ($document->notes)
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Notes</p>
            <p class="mt-2 text-[13px] leading-relaxed text-[#3a3327]">{{ $document->notes }}</p>
        </div>
    @endif
</div>
@endsection
