@php
    $layout = $group['layout'] ?? 'full';
    $visibleSlotCount = match ($layout) {
        '2col' => 2,
        '3col' => 3,
        default => 1,
    };
    $groupItems = collect($group['items'] ?? [])->pad(3, [
        'media_asset_id' => null,
        'title' => null,
        'alt_text' => null,
        'caption' => null,
        'url' => null,
    ])->take(3);
@endphp

<div data-group-row class="group-card p-4 space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-white" data-group-layout-label>{{ $layout === '3col' ? '3 Grid' : ($layout === '2col' ? '2 Grid' : 'Full') }}</span>
            <select data-name-template="chapters[__CHAPTER__][images][__GROUP__][layout]" data-group-layout-input class="field-input !w-auto !px-3 !py-2 text-[11px]">
                <option value="full" @selected($layout === 'full')>Full</option>
                <option value="2col" @selected($layout === '2col')>2 Grid</option>
                <option value="3col" @selected($layout === '3col')>3 Grid</option>
            </select>
        </div>
        <button type="button" data-remove-group class="mini-link">Remove</button>
    </div>

    <div data-slot-grid class="slot-grid" data-layout="{{ $layout }}">
        @foreach ($groupItems as $slotIndex => $item)
            <div data-item-slot class="space-y-3 @if($slotIndex >= $visibleSlotCount) hidden @endif">
                <input type="hidden" value="{{ $item['media_asset_id'] ?? '' }}" data-name-template="chapters[__CHAPTER__][images][__GROUP__][items][__ITEM__][media_asset_id]">
                <label class="upload-zone">
                    <div class="upload-zone-preview">
                        @if (!empty($item['url']))
                            <img src="{{ $item['url'] }}" alt="{{ $item['alt_text'] ?? '' }}" class="upload-zone-image">
                        @else
                            <div class="upload-zone-empty">
                                <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-white">Upload image</span>
                                <span class="text-[12px] text-[#666]">Slot {{ $slotIndex + 1 }}</span>
                            </div>
                        @endif
                    </div>
                    <input type="file" accept="image/*" data-name-template="chapters[__CHAPTER__][images][__GROUP__][items][__ITEM__][file]" class="hidden">
                </label>
                <input type="text" value="{{ $item['title'] ?? '' }}" placeholder="Image title" data-name-template="chapters[__CHAPTER__][images][__GROUP__][items][__ITEM__][title]" class="field-input">
                <input type="text" value="{{ $item['alt_text'] ?? '' }}" placeholder="Alt text" data-name-template="chapters[__CHAPTER__][images][__GROUP__][items][__ITEM__][alt_text]" class="field-input">
                <input type="text" value="{{ $item['caption'] ?? '' }}" placeholder="Caption" data-name-template="chapters[__CHAPTER__][images][__GROUP__][items][__ITEM__][caption]" class="field-input">
            </div>
        @endforeach
    </div>
</div>
