{{--
    Dual/multi signer block — shared shape for an inline "sign here" pair on an
    inner page and the standalone acceptance-form page (see acceptance page
    below in print.blade.php, which includes this same partial).
    Props: $block (ProposalDocumentData::emptySignatureBlock / normalizeAcceptance shape)

    A signer with only `role` set (no name/phone/subtitle — e.g. role: "Sign Here")
    renders as a compact inline "Label ________" line instead of the full rich
    block with name/phone/pale-SIGNATURE-label. Matches the lightweight sign-off
    pairs seen at the bottom of inner content pages in the reference documents,
    distinct from the full Acceptance Form page's rich signer blocks.
--}}
@php
    $signers = $block['signers'] ?? [];
    $isCompact = fn (array $s) => filled($s['role'] ?? '') && blank($s['name'] ?? '') && blank($s['phone'] ?? '') && blank($s['subtitle'] ?? '');
    $compactSigners = array_values(array_filter($signers, $isCompact));
    $richSigners = array_values(array_filter($signers, fn ($s) => ! $isCompact($s)));
@endphp

@if (filled($block['intro'] ?? ''))
    <div class="sig-block-callout">{{ $block['intro'] }}</div>
@endif

@foreach ($richSigners as $signer)
    <div class="sig-block">
        @if (filled($signer['role'] ?? ''))
            <p class="sig-block-role">{{ mb_strtoupper($signer['role']) }}:</p>
        @endif
        <div class="sig-block-row">
            <div>
                @if (filled($signer['name'] ?? ''))
                    <p class="sig-block-name">{{ mb_strtoupper($signer['name']) }}</p>
                @endif
                @if (filled($signer['subtitle'] ?? ''))
                    <p class="sig-block-subtitle">{{ $signer['subtitle'] }}</p>
                @endif
            </div>
            <p class="sig-block-phone">{{ $signer['phone'] ?? '' }}</p>
            <div>
                <span class="sig-block-signature-label">SIGNATURE</span>
                <div class="sig-block-signature-line"></div>
            </div>
        </div>
    </div>
@endforeach

@if (count($compactSigners) > 0)
    <div class="sig-block-simple-row">
        @foreach ($compactSigners as $signer)
            <div class="sig-block-simple">
                <span>{{ $signer['role'] }}</span>
                <span class="sig-block-simple-line"></span>
            </div>
        @endforeach
    </div>
@endif

@if (filled($block['date_label'] ?? ''))
    <p class="sig-block-date">{{ $block['date_label'] }}</p>
@endif
