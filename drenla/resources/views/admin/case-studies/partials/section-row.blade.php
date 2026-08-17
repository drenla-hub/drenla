@php
    $asset = !empty($section['media_asset_id']) ? \App\Models\MediaAsset::find($section['media_asset_id']) : null;
@endphp

<div data-row class="space-y-5 border border-[#1f1f1f] bg-[#050505] p-5">
    <div class="flex items-center justify-between gap-4 border-b border-[#151515] pb-4">
        <div class="flex items-center gap-3">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[#555]">Story section</p>
            <span data-row-position class="text-[10px] uppercase tracking-[0.16em] text-[#3f3f3f]"></span>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" data-move-up class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#666] transition hover:text-white">
                Up
            </button>
            <button type="button" data-move-down class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#666] transition hover:text-white">
                Down
            </button>
            <button type="button" data-remove-row class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#666] transition hover:text-red-400">
                Remove
            </button>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="field-label">Section title</label>
            <input type="text"
                   value="{{ $section['title'] ?? '' }}"
                   data-name-template="sections[__INDEX__][title]"
                   class="field-input">
        </div>
        <div>
            <label class="field-label">Layout</label>
            <select data-name-template="sections[__INDEX__][layout]" class="field-input">
                <option value="text" @selected(($section['layout'] ?? 'text') === 'text')>Text only</option>
                <option value="image" @selected(($section['layout'] ?? null) === 'image')>Text + image stack</option>
                <option value="image-left" @selected(($section['layout'] ?? null) === 'image-left')>Image left</option>
                <option value="image-right" @selected(($section['layout'] ?? null) === 'image-right')>Image right</option>
                <option value="full-image" @selected(($section['layout'] ?? null) === 'full-image')>Full-width image</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="field-label">Body</label>
            <textarea rows="6" data-name-template="sections[__INDEX__][body]" class="field-input resize-y">{{ $section['body'] ?? '' }}</textarea>
        </div>
    </div>

    <input type="hidden" value="{{ $section['media_asset_id'] ?? '' }}" data-name-template="sections[__INDEX__][media_asset_id]">

    @if ($asset)
        <div class="overflow-hidden border border-[#1f1f1f]">
            <img src="{{ $asset->url }}" alt="{{ $asset->alt_text }}" class="h-48 w-full object-cover">
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="field-label">Section image</label>
            <input type="file"
                   accept="image/*"
                   data-name-template="sections[__INDEX__][image]"
                   class="field-input file:mr-4 file:border-0 file:bg-white file:px-3 file:py-2 file:text-[11px] file:font-semibold file:uppercase file:tracking-[0.16em] file:text-black">
        </div>
        <div>
            <label class="field-label">Image title</label>
            <input type="text"
                   value="{{ $section['media_title'] ?? '' }}"
                   data-name-template="sections[__INDEX__][media_title]"
                   class="field-input">
        </div>
        <div>
            <label class="field-label">Alt text</label>
            <input type="text"
                   value="{{ $section['media_alt_text'] ?? '' }}"
                   data-name-template="sections[__INDEX__][media_alt_text]"
                   class="field-input">
        </div>
        <div>
            <label class="field-label">Caption</label>
            <input type="text"
                   value="{{ $section['media_caption'] ?? '' }}"
                   data-name-template="sections[__INDEX__][media_caption]"
                   class="field-input">
        </div>
    </div>
</div>
