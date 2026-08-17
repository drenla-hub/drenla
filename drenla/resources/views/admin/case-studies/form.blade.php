@extends('layouts.admin')

@section('title', $caseStudy->exists ? 'Edit Case Study' : 'New Case Study')

@php
    $coverAsset = $caseStudy->mediaAsset;
    $chapters = old('chapters', collect($caseStudy->content_blocks ?? [])->map(function ($chapter) {
        return [
            'num' => $chapter['num'] ?? null,
            'title' => $chapter['title'] ?? null,
            'paragraphs' => $chapter['paragraphs'] ?? [''],
            'images' => collect($chapter['images'] ?? [])->map(function ($group) {
                $count = match ($group['layout'] ?? 'full') {
                    '2col' => 2,
                    '3col' => 3,
                    default => 1,
                };
                return [
                    'layout' => $group['layout'] ?? 'full',
                    'items' => collect(range(0, $count - 1))->map(function ($index) use ($group) {
                        $item = $group['items'][$index] ?? [];
                        $asset = !empty($item['media_asset_id']) ? \App\Models\MediaAsset::find($item['media_asset_id']) : null;
                        return [
                            'media_asset_id' => $asset?->id,
                            'title' => $asset?->title,
                            'alt_text' => $asset?->alt_text,
                            'caption' => $item['caption'] ?? null,
                            'url' => $asset?->url,
                        ];
                    })->all(),
                ];
            })->all(),
        ];
    })->all());
@endphp

@section('content')
<div class="space-y-8">
    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Content</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">{{ $caseStudy->exists ? 'Edit case study' : 'New case study' }}</h1>
            <p class="mt-3 max-w-3xl text-[13px] leading-6 text-[#666]">Edit this in the same structure the frontend actually renders: chapters, paragraphs, and fixed image groups.</p>
        </div>
        <a href="{{ route('admin.case-studies.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#555] transition-colors hover:text-white">← Back</a>
    </div>

    <form method="POST" action="{{ $caseStudy->exists ? route('admin.case-studies.update', $caseStudy) : route('admin.case-studies.store') }}" enctype="multipart/form-data" class="space-y-10">
        @csrf
        @if($caseStudy->exists) @method('PUT') @endif

        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="section-label">Identity</p>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="field-label">Title</label><input type="text" name="title" value="{{ old('title', $caseStudy->title) }}" class="field-input"></div>
                <div><label class="field-label">Slug</label><input type="text" name="slug" value="{{ old('slug', $caseStudy->slug) }}" class="field-input"></div>
                <div><label class="field-label">Client name</label><input type="text" name="client_name" value="{{ old('client_name', $caseStudy->client_name) }}" class="field-input"></div>
                <div><label class="field-label">Industry</label><input type="text" name="industry" value="{{ old('industry', $caseStudy->industry) }}" class="field-input"></div>
                <div><label class="field-label">Summary</label><input type="text" name="summary" value="{{ old('summary', $caseStudy->summary) }}" class="field-input"></div>
                <div><label class="field-label">Sort order</label><input type="number" name="sort_order" value="{{ old('sort_order', $caseStudy->sort_order ?? 0) }}" class="field-input"></div>
                <div><label class="field-label">Status</label><select name="status" class="field-input"><option value="draft" @selected(old('status', $caseStudy->status) === 'draft')>Draft</option><option value="published" @selected(old('status', $caseStudy->status) === 'published')>Published</option></select></div>
                <div><label class="field-label">Published at</label><input type="date" name="published_at" value="{{ old('published_at', optional($caseStudy->published_at)->toDateString()) }}" class="field-input"></div>
            </div>
            <label class="flex items-center gap-3 text-[12px] text-[#666]"><input type="checkbox" name="featured" value="1" class="h-4 w-4 border-[#333] bg-black accent-white" @checked(old('featured', $caseStudy->featured))>Featured</label>
        </div>

        <div class="border border-[#1a1a1a] p-6 space-y-5">
            <p class="section-label">Cover image</p>
            @if ($coverAsset)
                <img src="{{ $coverAsset->url }}" alt="{{ $coverAsset->alt_text }}" class="h-56 w-full object-cover">
            @endif
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="field-label">Upload cover image</label><input type="file" name="cover_image" accept="image/*" class="field-input file:mr-4 file:border-0 file:bg-white file:px-3 file:py-2 file:text-[11px] file:font-semibold file:uppercase file:tracking-[0.16em] file:text-black"></div>
                <div><label class="field-label">Image title</label><input type="text" name="cover_image_title" value="{{ old('cover_image_title', $coverAsset?->title) }}" class="field-input"></div>
                <div class="md:col-span-2"><label class="field-label">Alt text</label><input type="text" name="cover_image_alt_text" value="{{ old('cover_image_alt_text', $coverAsset?->alt_text) }}" class="field-input"></div>
            </div>
        </div>

        <div class="space-y-6" data-chapters-root>
            <div class="flex flex-col gap-4 border-b border-[#151515] pb-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="section-label">Chapters</p>
                    <p class="mt-2 max-w-3xl text-[12px] leading-6 text-[#666]">Use the same chapter-and-image-group model as the frontend. `2 grid` previews as two columns, `3 grid` as three.</p>
                </div>
                <button type="button" data-add-chapter class="action-btn">Add Chapter</button>
            </div>
            <div class="space-y-0" data-chapters-list>
                @foreach ($chapters as $chapterIndex => $chapter)
                    @include('admin.case-studies.partials.chapter-row', ['chapterIndex' => $chapterIndex, 'chapter' => $chapter])
                @endforeach
            </div>
        </div>

        <div class="border border-[#1a1a1a] p-6 space-y-5">
            <p class="section-label">SEO</p>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="field-label">Meta title</label><input type="text" name="meta_title" value="{{ old('meta_title', $caseStudy->meta_title) }}" class="field-input"></div>
                <div><label class="field-label">Meta description</label><textarea name="meta_description" rows="3" class="field-input">{{ old('meta_description', $caseStudy->meta_description) }}</textarea></div>
            </div>
        </div>

        <div class="flex items-center gap-4 border-t border-[#1a1a1a] pt-6">
            <button type="submit" class="inline-flex items-center border border-white bg-white px-6 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-black transition hover:bg-transparent hover:text-white">{{ $caseStudy->exists ? 'Update case study' : 'Create case study' }}</button>
            @if ($caseStudy->exists)
                <button type="submit" form="delete-case-study" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] transition-colors hover:text-red-400">Delete</button>
            @endif
        </div>
    </form>

    @if ($caseStudy->exists)
        <form id="delete-case-study" method="POST" action="{{ route('admin.case-studies.destroy', $caseStudy) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>

<template id="chapter-template">
    @include('admin.case-studies.partials.chapter-row', ['chapterIndex' => '__CHAPTER__', 'chapter' => ['num' => null, 'title' => null, 'paragraphs' => [''], 'images' => []]])
</template>

<template id="paragraph-template">
    <div data-paragraph-row class="flex gap-3">
        <textarea rows="3" data-name-template="chapters[__CHAPTER__][paragraphs][__PARAGRAPH__]" class="canvas-paragraph" placeholder="Write a chapter paragraph..."></textarea>
        <button type="button" data-remove-paragraph class="mini-link">Remove</button>
    </div>
</template>

<template id="image-group-template">
    @include('admin.case-studies.partials.image-group-row', ['chapterIndex' => '__CHAPTER__', 'groupIndex' => '__GROUP__', 'group' => ['layout' => '__LAYOUT__', 'items' => []]])
</template>

<style>
.section-label,.field-label{display:block;color:#555;text-transform:uppercase}
.section-label{font-size:9px;font-weight:900;letter-spacing:.35em}
.field-label{margin-bottom:8px;font-size:11px;font-weight:700;letter-spacing:.22em}
.field-input{width:100%;border:1px solid #1f1f1f;background:#000;padding:12px 16px;color:#fff;outline:none;transition:border-color .15s}
.field-input:focus{border-color:#444}
.action-btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid #2a2a2a;background:#0a0a0a;padding:11px 16px;font-size:10px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#fff;transition:border-color .15s,background .15s,color .15s}
.action-btn:hover{border-color:#fff;background:#fff;color:#000}
.mini-link{font-size:10px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#666;transition:color .15s}
.mini-link:hover{color:#fff}
.chapter{border-top:1px solid #171717}
.chapter:last-child{border-bottom:1px solid #171717}
.chapter-head:hover{background:rgba(255,255,255,.01)}
.chapter-preview-grid{display:grid;gap:6px}
.chapter-preview-grid[data-layout="full"]{grid-template-columns:1fr}
.chapter-preview-grid[data-layout="2col"]{grid-template-columns:repeat(2,minmax(0,1fr))}
.chapter-preview-grid[data-layout="3col"]{grid-template-columns:repeat(3,minmax(0,1fr))}
.chapter-preview-grid img,.chapter-preview-grid .placeholder{width:100%;height:78px;object-fit:cover;background:#0b0b0b;border:1px solid #232323}
.canvas-paragraph{width:100%;min-height:120px;border:0;border-bottom:1px solid #171717;background:transparent;padding:0 0 14px;color:#d1d1d1;font-size:15px;line-height:1.8;outline:none}
.canvas-title{width:100%;border:0;border-bottom:1px solid #1c1c1c;background:transparent;padding:0 0 16px;color:#fff;font-size:28px;font-weight:300;letter-spacing:-.03em;outline:none}
.canvas-title::placeholder,.canvas-paragraph::placeholder{color:#555}
.group-card{border:1px solid #171717;background:#050505}
.slot-grid[data-layout="full"]{grid-template-columns:1fr}
.slot-grid[data-layout="2col"]{grid-template-columns:repeat(2,minmax(0,1fr))}
.slot-grid[data-layout="3col"]{grid-template-columns:repeat(3,minmax(0,1fr))}
.slot-grid{display:grid;gap:8px}
.upload-zone{display:block;border:1px dashed #262626;background:#050505;cursor:pointer;overflow:hidden}
.upload-zone:hover{border-color:#4a4a4a;background:#080808}
.upload-zone-preview{width:100%;height:220px;background:#090909}
.upload-zone-image{width:100%;height:100%;object-fit:cover;display:block}
.upload-zone-empty{display:flex;height:100%;flex-direction:column;justify-content:center;gap:6px;padding:16px}
.chapter-body{padding:24px 0 30px}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-chapters-root]')
    if (!root) return
    const chaptersList = root.querySelector('[data-chapters-list]')
    const chapterTemplate = document.getElementById('chapter-template')
    const paragraphTemplate = document.getElementById('paragraph-template')
    const groupTemplate = document.getElementById('image-group-template')
    const groupSlotCount = layout => layout === '3col' ? 3 : layout === '2col' ? 2 : 1
    const toggleChapter = (chapter, expanded) => {
        chapter.dataset.expanded = expanded ? 'true' : 'false'
        chapter.querySelector('[data-chapter-body]')?.classList.toggle('hidden', !expanded)
        const label = chapter.querySelector('[data-chapter-toggle-label]')
        if (label) label.textContent = expanded ? 'Close' : 'Edit'
    }
    const syncChapterPreview = chapter => {
        const title = (chapter.querySelector('[data-chapter-title-input]')?.value || '').trim()
        const num = (chapter.querySelector('[data-chapter-num-input]')?.value || '').trim()
        const paragraphs = [...chapter.querySelectorAll('[data-paragraph-row] textarea')].map(el => el.value.trim()).filter(Boolean)
        chapter.querySelector('[data-chapter-preview-title]').textContent = title || 'Untitled chapter'
        chapter.querySelector('[data-chapter-preview-num]').textContent = `Chapter ${num || '—'}`
        chapter.querySelector('[data-chapter-preview-body]').textContent = (paragraphs[0] || 'Add paragraphs and visual groups to shape this chapter.').slice(0, 180)
    }
    const syncGroupPreview = group => {
        const layout = group.querySelector('[data-group-layout-input]').value || 'full'
        group.querySelectorAll('[data-item-slot]').forEach((slot, index) => slot.classList.toggle('hidden', index >= groupSlotCount(layout)))
        group.querySelector('[data-slot-grid]').setAttribute('data-layout', layout)
        group.querySelector('[data-group-layout-label]').textContent = layout === '3col' ? '3 Grid' : layout === '2col' ? '2 Grid' : 'Full'
    }
    const bindParagraphRow = (row, chapter) => {
        row.querySelector('[data-remove-paragraph]').addEventListener('click', () => { row.remove(); reindex(); syncChapterPreview(chapter) })
        row.querySelector('textarea').addEventListener('input', () => syncChapterPreview(chapter))
    }
    const bindGroup = (group) => {
        group.querySelector('[data-group-layout-input]').addEventListener('change', () => { syncGroupPreview(group); reindex() })
        group.querySelector('[data-remove-group]').addEventListener('click', () => { group.remove(); reindex() })
        group.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', event => {
                const file = event.target.files?.[0]
                if (!file) return
                const zone = input.closest('.upload-zone')
                const preview = zone?.querySelector('.upload-zone-preview')
                if (!preview) return
                const url = URL.createObjectURL(file)
                preview.innerHTML = `<img src="${url}" alt="" class="upload-zone-image">`
            })
        })
        syncGroupPreview(group)
    }
    const bindChapter = chapter => {
        chapter.querySelector('[data-toggle-chapter]').addEventListener('click', () => toggleChapter(chapter, chapter.dataset.expanded !== 'true'))
        chapter.querySelector('[data-remove-chapter]').addEventListener('click', () => { chapter.remove(); reindex() })
        chapter.querySelector('[data-move-up]').addEventListener('click', () => { const prev = chapter.previousElementSibling; if (prev) { chaptersList.insertBefore(chapter, prev); reindex() } })
        chapter.querySelector('[data-move-down]').addEventListener('click', () => { const next = chapter.nextElementSibling; if (next) { chaptersList.insertBefore(next, chapter); reindex() } })
        chapter.querySelector('[data-add-paragraph]').addEventListener('click', () => {
            const wrapper = document.createElement('div')
            wrapper.innerHTML = paragraphTemplate.innerHTML.trim()
            const row = wrapper.firstElementChild
            chapter.querySelector('[data-paragraphs-list]').appendChild(row)
            bindParagraphRow(row, chapter)
            reindex()
        })
        chapter.querySelectorAll('[data-add-group]').forEach(button => {
            button.addEventListener('click', () => {
                const wrapper = document.createElement('div')
                wrapper.innerHTML = groupTemplate.innerHTML.replaceAll('__LAYOUT__', button.dataset.addGroup).trim()
                const group = wrapper.firstElementChild
                chapter.querySelector('[data-groups-list]').appendChild(group)
                bindGroup(group)
                reindex()
            })
        })
        chapter.querySelectorAll('[data-paragraph-row]').forEach(row => bindParagraphRow(row, chapter))
        chapter.querySelectorAll('[data-group-row]').forEach(bindGroup)
        chapter.querySelectorAll('[data-chapter-title-input],[data-chapter-num-input]').forEach(field => field.addEventListener('input', () => syncChapterPreview(chapter)))
        syncChapterPreview(chapter)
        toggleChapter(chapter, false)
    }
    const reindex = () => {
        chaptersList.querySelectorAll('[data-chapter-row]').forEach((chapter, chapterIndex) => {
            chapter.querySelectorAll('[data-name-template]').forEach(field => {
                let name = field.dataset.nameTemplate.replaceAll('__CHAPTER__', chapterIndex)
                const groupRow = field.closest('[data-group-row]')
                const paragraphRow = field.closest('[data-paragraph-row]')
                if (groupRow) {
                    name = name.replaceAll('__GROUP__', [...chapter.querySelectorAll('[data-group-row]')].indexOf(groupRow))
                    const slot = field.closest('[data-item-slot]')
                    if (slot) name = name.replaceAll('__ITEM__', [...groupRow.querySelectorAll('[data-item-slot]')].indexOf(slot))
                }
                if (paragraphRow) name = name.replaceAll('__PARAGRAPH__', [...chapter.querySelectorAll('[data-paragraph-row]')].indexOf(paragraphRow))
                field.name = name
            })
            chapter.querySelector('[data-chapter-order]').textContent = `#${chapterIndex + 1}`
            syncChapterPreview(chapter)
            chapter.querySelectorAll('[data-group-row]').forEach(syncGroupPreview)
        })
    }
    root.querySelector('[data-add-chapter]').addEventListener('click', () => {
        const wrapper = document.createElement('div')
        wrapper.innerHTML = chapterTemplate.innerHTML.trim()
        const chapter = wrapper.firstElementChild
        chaptersList.appendChild(chapter)
        bindChapter(chapter)
        toggleChapter(chapter, true)
        reindex()
    })
    chaptersList.querySelectorAll('[data-chapter-row]').forEach(bindChapter)
    reindex()
})
</script>
@endsection
