{{--
    Reusable financial-document body (quotation / invoice / receipt) — shared by the
    proposal PDF's embedded quotation page (admin/proposals/print.blade.php) and the
    standalone finance document PDF (admin/finance/print.blade.php). Relies on the
    .quot-* CSS defined in both parent views' <style> blocks.

    Props:
      $document           — FinanceDocument, with `items` and `client` loaded
      $clientNameOverride — optional fallback client name (used when embedding inside
                             a proposal whose quotation predates having its own client)
--}}
@php
    $docDate = $document->issue_date?->format('j\t\h F Y') ?? '';
    $docCurrency = strtoupper($document->currency ?? 'KES');
    $docClientName = $document->client?->name ?? $clientNameOverride ?? '';
    $docLabel = ucfirst($document->type);
@endphp

<div class="quot-body">

    {{-- Meta row --}}
    <div class="quot-meta-row">
        <div>
            <div class="quot-meta-label">{{ $docLabel }} Number</div>
            <div class="quot-meta-value"># {{ $document->reference_number }}</div>
        </div>
        <div>
            <div class="quot-meta-label">{{ $docLabel }} Date</div>
            <div class="quot-meta-value">{{ $docDate }}</div>
        </div>
        <div>
            <div class="quot-meta-label">Client Name</div>
            <div class="quot-meta-value">{{ $docClientName }}</div>
        </div>
    </div>

    {{-- Line items table --}}
    <table class="quot-table">
        <thead>
            <tr>
                <th style="width:52%">Item &amp; Description</th>
                <th class="r" style="width:18%">Unit Cost</th>
                <th class="r" style="width:8%">Qty</th>
                <th class="r" style="width:18%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->items as $item)
                @php
                    // A zero-price, zero-qty row is a descriptive sub-item nested under
                    // the priced line above it (e.g. "Exterior Facade & Landscaping"
                    // detailing what "Commercial 3D Visualization Package" covers) — the
                    // reference documents render these without the mark or price columns
                    // filled, not as a literal "0.00" line.
                    $isDescriptive = (float) $item->unit_price === 0.0 && (float) $item->quantity === 0.0;
                @endphp
                <tr>
                    <td>
                        <div class="quot-item-row">
                            <span class="quot-check" style="{{ $isDescriptive ? 'visibility:hidden' : '' }}"></span>
                            <div>
                                <div class="quot-item-title">{{ $item->title }}</div>
                                @if (filled($item->description))
                                    <div class="quot-item-desc">{{ $item->description }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="r">{{ $isDescriptive ? '' : number_format((float) $item->unit_price, 2) }}</td>
                    <td class="r">{{ $isDescriptive ? '' : rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="r">{{ $isDescriptive ? '' : number_format((float) $item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="quot-subtotal">
                <td colspan="3">Sub Total:</td>
                <td class="r">{{ $docCurrency }} {{ number_format((float) $document->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3">VAT [16%]:</td>
                <td class="r">{{ $document->tax_amount > 0 ? $docCurrency.' '.number_format((float) $document->tax_amount, 2) : 'N/A' }}</td>
            </tr>
            <tr class="quot-total">
                <td colspan="3">Total Amount:</td>
                <td class="r">{{ $docCurrency }} {{ number_format((float) $document->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="quot-vat-note">KINDLY NOTE that Fee provided is VAT Exclusive</div>

    @if (filled($document->payment_terms))
        <div class="quot-section-head">Payment Terms:</div>
        <div class="quot-section-body">{{ $document->payment_terms }}</div>
    @endif

    @if (filled($document->payment_info))
        <div class="quot-section-head">Payment Info:</div>
        <div class="quot-section-body">{{ $document->payment_info }}</div>
    @endif

    <div class="quot-sign-area">
        <div class="quot-sign-box">Authorised Sign</div>
    </div>

</div>{{-- /.quot-body --}}
