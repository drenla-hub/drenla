{{--
    Structured bullet list block — square or dash markers, driven from an items
    array instead of parsed inline text.
    Props: $block (ProposalDocumentData::emptyBulletListBlock shape)
--}}
@php
    $isDash = ($block['bullet_style'] ?? 'square') === 'dash';
    $dot = $isDash ? '–' : '•';
    $dotClass = $isDash ? 'bullet-dot' : 'bullet-dot sq';
@endphp

<div class="block-item">
    @if (filled($block['title'] ?? ''))
        <h3 class="block-item-heading">{{ $block['title'] }}</h3>
    @endif
    @if (filled($block['intro'] ?? ''))
        <p class="body-intro">{{ $block['intro'] }}</p>
    @endif
    @if (! empty($block['items']))
        <div class="bullet-list">
            @foreach ($block['items'] as $item)
                <div class="bullet-item"><span class="{{ $dotClass }}">{{ $dot }}</span><span>{{ $item }}</span></div>
            @endforeach
        </div>
    @endif
</div>
