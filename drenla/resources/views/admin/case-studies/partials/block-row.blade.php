@php
    $blockType = $block['type'] ?? 'text';
    $primaryAsset = !empty($block['image_media_asset_id']) ? \App\Models\MediaAsset::find($block['image_media_asset_id']) : null;
    $secondaryAsset = !empty($block['image_secondary_media_asset_id']) ? \App\Models\MediaAsset::find($block['image_secondary_media_asset_id']) : null;
@endphp

<div data-row data-block-type="{{ $blockType }}" data-expanded="false" class="builder-row">
    <button type="button" data-toggle-editor class="builder-row-head w-full p-5 text-left">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="builder-chip" data-block-label>
                        @if ($blockType === 'image')
                            Full Image
                        @elseif ($blockType === 'image-pair')
                            2 Grid
                        @else
                            Text
                        @endif
                    </span>
                    <span data-row-position class="text-[10px] uppercase tracking-[0.16em] text-[#3f3f3f]"></span>
                </div>
                <div class="space-y-2">
                    <h3 data-preview-title class="text-[18px] font-medium tracking-[-0.02em] text-white">
                        {{ $block['title'] ?: ($blockType === 'text' ? 'Untitled text block' : 'Untitled visual block') }}
                    </h3>
                    <p data-preview-body class="max-w-3xl text-[13px] leading-6 text-[#666]">
                        {{ \Illuminate\Support\Str::limit($block['body'] ?: ($blockType === 'image-pair' ? 'Two-image grid block. Click to edit the copy and both image slots.' : ($blockType === 'image' ? 'Single full-width image block. Click to edit the image and supporting copy.' : 'Narrative block. Click to edit styled copy.')), 180) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="builder-mini-preview" data-preview-visual>
                    @if ($blockType === 'image-pair')
                        <span></span><span></span>
                    @elseif ($blockType === 'image')
                        <span class="is-full"></span>
                    @else
                        <span class="is-text"></span>
                    @endif
                </div>
                <span data-toggle-label class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#777]">Edit</span>
            </div>
        </div>
    </button>

    <div class="flex items-center justify-end gap-3 border-y border-[#151515] px-5 py-3">
        <button type="button" data-move-up class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#666] transition hover:text-white">Up</button>
        <button type="button" data-move-down class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#666] transition hover:text-white">Down</button>
        <button type="button" data-remove-row class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#666] transition hover:text-red-400">Remove</button>
    </div>

    <input type="hidden" value="{{ $blockType }}" data-name-template="blocks[__INDEX__][type]" data-block-type-input>

    <div data-editor-panel class="hidden space-y-5 p-5">
        <div class="px-1 py-2 md:px-2">
            <div class="mx-auto max-w-4xl space-y-6">
                <input
                    type="text"
                    value="{{ $block['title'] ?? '' }}"
                    data-name-template="blocks[__INDEX__][title]"
                    data-preview-source="title"
                    placeholder="{{ $blockType === 'text' ? 'Add section title' : 'Add visual section title' }}"
                    class="canvas-title-input"
                >
                <textarea
                    rows="8"
                    data-name-template="blocks[__INDEX__][body]"
                    data-preview-source="body"
                    placeholder="{{ $blockType === 'text' ? 'Write the narrative here. This should feel like composing the story, not filling a form.' : 'Add supporting context, rationale, or caption-like narrative for this visual block.' }}"
                    class="canvas-body-input resize-y"
                >{{ $block['body'] ?? '' }}</textarea>
            </div>
        </div>

        <div data-image-area class="@if($blockType === 'text') hidden @endif space-y-5">
            <div class="grid gap-5 @if($blockType === 'image-pair') lg:grid-cols-2 @endif">
                <div class="space-y-4 border-t border-[#141414] pt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#777]">Primary image</p>
                        <span class="text-[10px] uppercase tracking-[0.16em] text-[#444]">
                            @if ($blockType === 'image-pair') slot 1 @else full width @endif
                        </span>
                    </div>

                    <input type="hidden" value="{{ $block['image_media_asset_id'] ?? '' }}" data-name-template="blocks[__INDEX__][image_media_asset_id]">

                    @if ($primaryAsset)
                        <img src="{{ $primaryAsset->url }}" alt="{{ $primaryAsset->alt_text }}" class="h-48 w-full object-cover">
                    @else
                        <div class="flex h-48 items-center justify-center border border-dashed border-[#2a2a2a] bg-black text-[12px] uppercase tracking-[0.16em] text-[#444]">
                            No image selected
                        </div>
                    @endif

                    <label class="upload-dropzone">
                        <span class="upload-title">Drop image here or click to upload</span>
                        <span class="upload-copy">Best for the first slot in this layout.</span>
                        <input type="file" accept="image/*" data-name-template="blocks[__INDEX__][image_file]" class="hidden">
                    </label>

                    <div class="grid gap-4">
                        <input type="text" value="{{ $block['image_title'] ?? '' }}" placeholder="Image title" data-name-template="blocks[__INDEX__][image_title]" class="field-input">
                        <input type="text" value="{{ $block['image_alt_text'] ?? '' }}" placeholder="Alt text" data-name-template="blocks[__INDEX__][image_alt_text]" class="field-input">
                        <input type="text" value="{{ $block['image_caption'] ?? '' }}" placeholder="Caption" data-name-template="blocks[__INDEX__][image_caption]" class="field-input">
                    </div>
                </div>

                <div data-secondary-image class="@if($blockType !== 'image-pair') hidden @endif space-y-4 border-t border-[#141414] pt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#777]">Secondary image</p>
                        <span class="text-[10px] uppercase tracking-[0.16em] text-[#444]">slot 2</span>
                    </div>

                    <input type="hidden" value="{{ $block['image_secondary_media_asset_id'] ?? '' }}" data-name-template="blocks[__INDEX__][image_secondary_media_asset_id]">

                    @if ($secondaryAsset)
                        <img src="{{ $secondaryAsset->url }}" alt="{{ $secondaryAsset->alt_text }}" class="h-48 w-full object-cover">
                    @else
                        <div class="flex h-48 items-center justify-center border border-dashed border-[#2a2a2a] bg-black text-[12px] uppercase tracking-[0.16em] text-[#444]">
                            No image selected
                        </div>
                    @endif

                    <label class="upload-dropzone">
                        <span class="upload-title">Drop image here or click to upload</span>
                        <span class="upload-copy">Second slot for the 2-grid layout.</span>
                        <input type="file" accept="image/*" data-name-template="blocks[__INDEX__][image_secondary_file]" class="hidden">
                    </label>

                    <div class="grid gap-4">
                        <input type="text" value="{{ $block['image_secondary_title'] ?? '' }}" placeholder="Image title" data-name-template="blocks[__INDEX__][image_secondary_title]" class="field-input">
                        <input type="text" value="{{ $block['image_secondary_alt_text'] ?? '' }}" placeholder="Alt text" data-name-template="blocks[__INDEX__][image_secondary_alt_text]" class="field-input">
                        <input type="text" value="{{ $block['image_secondary_caption'] ?? '' }}" placeholder="Caption" data-name-template="blocks[__INDEX__][image_secondary_caption]" class="field-input">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
