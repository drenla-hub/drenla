{{--
    Grouped multi-column list (e.g. Ground Floor / First Floor / Guest Wing) —
    plain white columns, small square-bullet heading + rule beneath (no colour
    band, no vertical divider — matches the reference documents exactly).
    Props: $block (ProposalDocumentData::emptyMultiColumnListBlock shape)

    A literal "---" item renders as a blank gap instead of a bullet — the
    convention for a sub-group break within one column (e.g. Guest Wing's rooms
    vs. amenities in the reference). Any other non-empty string is a normal item.
--}}
@php
    $columns = $block['columns'] ?? [];
@endphp

@if (filled($block['title'] ?? ''))
    <h3 class="block-item-heading">{{ $block['title'] }}</h3>
@endif

<div class="body-col-grid" style="grid-template-columns: repeat({{ max(count($columns), 1) }}, 1fr);">
    @foreach ($columns as $column)
        <div class="body-col">
            <div class="body-col-head">
                <span class="body-col-sq">■</span>{{ mb_strtoupper($column['heading'] ?? '') }}
            </div>
            @foreach ($column['items'] ?? [] as $item)
                @if ($item === '---')
                    <div class="body-col-group-gap"></div>
                @else
                    <div class="body-col-item"><span class="bci-dot">■</span><span>{{ $item }}</span></div>
                @endif
            @endforeach
        </div>
    @endforeach
</div>
