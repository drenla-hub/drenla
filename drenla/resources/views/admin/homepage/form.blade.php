@extends('layouts.admin')

@section('title', $section->exists ? 'Edit Homepage Section' : 'New Homepage Section')

@php
    $highlightsText = old('highlights_text', collect($section->payload['highlights'] ?? [])->implode("\n"));
@endphp

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Content</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">
                {{ $section->exists ? 'Edit homepage section' : 'New homepage section' }}
            </h1>
        </div>
        <a href="{{ route('admin.homepage.index') }}"
           class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            ← Back
        </a>
    </div>

    <form method="POST"
          action="{{ $section->exists ? route('admin.homepage.update', $section) : route('admin.homepage.store') }}"
          enctype="multipart/form-data"
          class="space-y-8">
        @csrf
        @if($section->exists) @method('PUT') @endif

        {{-- Identity --}}
        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Identity</p>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Key</label>
                    <input type="text" name="key" value="{{ old('key', $section->key) }}"
                           placeholder="hero, capabilities, cta…"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                    <p class="mt-1.5 text-[11px] text-[#444]">Stable identifier the frontend matches against — changing it after launch will break existing consumers.</p>
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Sort order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $section->sort_order ?? 0) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Eyebrow</label>
                    <input type="text" name="eyebrow" value="{{ old('eyebrow', $section->eyebrow) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Title</label>
                    <input type="text" name="title" value="{{ old('title', $section->title) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">CTA label</label>
                    <input type="text" name="cta_label" value="{{ old('cta_label', $section->cta_label) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">CTA URL</label>
                    <input type="text" name="cta_url" value="{{ old('cta_url', $section->cta_url) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
            </div>

            <label class="flex items-center gap-3 text-[12px] text-[#666] cursor-pointer select-none">
                <input type="checkbox" name="is_published" value="1"
                       class="h-4 w-4 border-[#333] bg-black accent-white"
                       @checked(old('is_published', $section->is_published ?? true))>
                Published — visible through the public homepage API
            </label>
        </div>

        {{-- Body --}}
        <div class="border border-[#1a1a1a] p-6 space-y-4">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Body content</p>
            <textarea name="body" rows="8"
                      class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y">{{ old('body', $section->body) }}</textarea>
        </div>

        {{-- Highlights --}}
        <div class="border border-[#1a1a1a] p-6 space-y-4">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Highlights</p>
            <p class="text-[12px] text-[#555]">One per line — stored as the section's structured payload.</p>
            <textarea name="highlights_text" rows="4"
                      class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y">{{ $highlightsText }}</textarea>
        </div>

        {{-- Media --}}
        <div class="border border-[#1a1a1a] p-6 space-y-5">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Media</p>

            @if ($section->mediaAsset)
                <div class="overflow-hidden border border-[#1f1f1f] w-64">
                    <img src="{{ $section->mediaAsset->url }}" alt="{{ $section->mediaAsset->alt_text }}" class="h-36 w-full object-cover">
                </div>
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Select existing media</label>
                    <select name="media_asset_id" class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        <option value="">— None —</option>
                        @foreach ($mediaAssets as $asset)
                            <option value="{{ $asset->id }}" @selected(old('media_asset_id', $section->media_asset_id) == $asset->id)>{{ $asset->title }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-[#444]"><a href="{{ route('admin.media.index') }}" class="underline hover:text-[#777]">Browse the media library</a> to upload assets ahead of time.</p>
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Or upload new</label>
                    <input type="file" name="media_upload" accept="image/*"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] file:mr-4 file:border-0 file:bg-white file:px-3 file:py-2 file:text-[11px] file:font-semibold file:uppercase file:tracking-[0.16em] file:text-black">
                    <p class="mt-1.5 text-[11px] text-[#444]">Uploading here overrides the dropdown selection.</p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 border-t border-[#1a1a1a] pt-6">
            <button type="submit"
                    class="inline-flex items-center border border-white bg-white px-6 py-2.5
                           text-[10px] font-bold uppercase tracking-[0.2em] text-black
                           transition hover:bg-transparent hover:text-white">
                {{ $section->exists ? 'Update section' : 'Create section' }}
            </button>
            @if ($section->exists)
                <form method="POST" action="{{ route('admin.homepage.destroy', $section) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">
                        Delete
                    </button>
                </form>
            @endif
        </div>
    </form>

</div>
@endsection
