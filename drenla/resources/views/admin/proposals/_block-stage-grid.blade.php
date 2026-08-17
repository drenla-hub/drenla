{{--
    Stage/timeline grid block — data-driven version of the Work Process pages
    (2 or 1 columns, duration emphasized, row separators between pairs).
    Props: $block (ProposalDocumentData::emptyStageGridBlock shape)
--}}
@php
    $columns = (int) ($block['columns'] ?? 2);
    $stages = $block['stages'] ?? [];
@endphp

<div class="stage-grid" style="grid-template-columns: repeat({{ $columns }}, 1fr);">
    @foreach ($stages as $si => $stage)
        @if ($columns > 1 && $si > 0 && $si % $columns === 0)
            <div class="stage-grid-sep"></div>
        @endif

        <div class="stage-block">
            @if (filled($stage['label'] ?? ''))
                <div class="stage-block-head">{{ mb_strtoupper($stage['label']) }}</div>
            @endif

            @if (! empty($stage['items']))
                <div class="bullet-list">
                    @foreach ($stage['items'] as $item)
                        <div class="bullet-item"><span class="bullet-dot">–</span><span>{{ $item }}</span></div>
                    @endforeach
                </div>
            @endif

            @if (filled($stage['note'] ?? ''))
                <p class="body-p">{{ $stage['note'] }}</p>
            @endif

            @if (filled($stage['duration'] ?? ''))
                <div class="stage-block-dur">{{ mb_strtoupper($stage['duration']) }}</div>
            @endif
        </div>
    @endforeach
</div>
