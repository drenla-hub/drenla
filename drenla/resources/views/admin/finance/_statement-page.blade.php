{{--
    Statement/aging-table body — DATE/TRANSACTION/AMOUNT/%PAY/BALANCE running
    ledger + CURRENT/1-30/31-60/61-90+/TOTAL aging summary, matching the
    DNR-RCT-KLF 2026 reference. Distinct from _document-page.blade.php's
    line-items table (a statement isn't a priced quotation — it's a transaction
    history), included directly by print.blade.php for $document->type === 'statement'.

    Props:
      $document — FinanceDocument, with `transactions` and `client` loaded
--}}
@php
    $docDate = $document->issue_date?->format('j\t\h F Y') ?? '';
    $docCurrency = strtoupper($document->currency ?? 'KES');
    $docClientName = $document->client?->name ?? $clientNameOverride ?? '';
    $aging = app(\App\Services\FinanceAgingService::class);
    $ledgerRows = $aging->ledgerRows($document);
    $agingSummary = $aging->agingSummary($document);
@endphp

<div class="quot-body">

    {{-- Meta row --}}
    <div class="quot-meta-row">
        <div>
            <div class="quot-meta-label">Statement Number</div>
            <div class="quot-meta-value"># {{ $document->reference_number }}</div>
        </div>
        <div>
            <div class="quot-meta-label">Statement Date</div>
            <div class="quot-meta-value">{{ $docDate }}</div>
        </div>
        <div>
            <div class="quot-meta-label">Client Name</div>
            <div class="quot-meta-value">{{ $docClientName }}</div>
        </div>
    </div>

    {{-- Transaction ledger --}}
    <table class="quot-table">
        <thead>
            <tr>
                <th style="width:22%">Date</th>
                <th style="width:36%">Transaction</th>
                <th class="r" style="width:16%">Amount ({{ $docCurrency }})</th>
                <th class="r" style="width:10%">% Pay</th>
                <th class="r" style="width:16%">Balance ({{ $docCurrency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ledgerRows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['label'] }}</td>
                    <td class="r">{{ $row['amount'] }}</td>
                    <td class="r">{{ $row['percent_paid'] }}</td>
                    <td class="r">{{ $row['balance'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:rgba(15,17,20,0.4);">No transactions recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Aging summary --}}
    <div class="statement-aging-grid">
        <div class="statement-aging-head">Current</div>
        <div class="statement-aging-head">1-30 Days Past Due</div>
        <div class="statement-aging-head">31-60 Days Past Due</div>
        <div class="statement-aging-head">61-90+ Days Past Due</div>
        <div class="statement-aging-head">Total</div>
        <div class="statement-aging-value">{{ $agingSummary['current'] }}</div>
        <div class="statement-aging-value">{{ $agingSummary['days_1_30'] }}</div>
        <div class="statement-aging-value">{{ $agingSummary['days_31_60'] }}</div>
        <div class="statement-aging-value">{{ $agingSummary['days_61_90_plus'] }}</div>
        <div class="statement-aging-value statement-aging-total">{{ $agingSummary['total'] }}</div>
    </div>

    <div class="quot-subtotal" style="display:flex; justify-content:space-between; padding:10px 0; font-weight:700;">
        <span>Total Amount:</span>
        <span>{{ $docCurrency }}, {{ $agingSummary['total'] }}</span>
    </div>

    @if (filled($document->next_due_label) || $document->next_due_amount !== null)
        <div class="statement-next-due">
            <span>{{ $document->next_due_label }}</span>
            @if ($document->next_due_amount !== null)
                <span>Due Amount: {{ number_format((float) $document->next_due_amount, 2) }}</span>
            @endif
        </div>
    @endif

    <div class="quot-sign-area">
        <div class="quot-sign-box">Authorised Sign</div>
    </div>

</div>{{-- /.quot-body --}}
