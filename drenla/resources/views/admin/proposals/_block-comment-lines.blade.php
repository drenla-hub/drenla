{{--
    Blank ruled lines for client hand-mark-up (ABD-family "Client comments" area).
    Props: $block (ProposalDocumentData::emptyCommentLinesBlock shape)
--}}
@php
    $lineCount = max(1, (int) ($block['line_count'] ?? 4));
@endphp

<div class="comment-lines">
    @if (filled($block['label'] ?? ''))
        <p class="comment-lines-label">{{ $block['label'] }}</p>
    @endif
    @for ($i = 0; $i < $lineCount; $i++)
        <div class="comment-line"></div>
    @endfor
</div>
