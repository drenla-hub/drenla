@extends('layouts.admin')

@section('title', $focusArea->exists ? 'Edit Focus Area' : 'New Focus Area')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Content</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">
                {{ $focusArea->exists ? 'Edit focus area' : 'New focus area' }}
            </h1>
        </div>
        <a href="{{ route('admin.focus-areas.index') }}"
           class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            ← Back
        </a>
    </div>

    <form method="POST"
          action="{{ $focusArea->exists ? route('admin.focus-areas.update', $focusArea) : route('admin.focus-areas.store') }}"
          class="space-y-8">
        @csrf
        @if($focusArea->exists) @method('PUT') @endif

        {{-- Identity --}}
        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Identity</p>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Title</label>
                    <input type="text" name="title" value="{{ old('title', $focusArea->title) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $focusArea->slug) }}"
                           placeholder="auto-generated from title"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Summary</label>
                    <input type="text" name="summary" value="{{ old('summary', $focusArea->summary) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Sort order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $focusArea->sort_order ?? 0) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Status</label>
                    <select name="status" class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        <option value="draft"     @selected(old('status', $focusArea->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $focusArea->status) === 'published')>Published</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Published at</label>
                    <input type="date" name="published_at"
                           value="{{ old('published_at', optional($focusArea->published_at)->toDateString()) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
            </div>

            <label class="flex items-center gap-3 text-[12px] text-[#666] cursor-pointer select-none">
                <input type="checkbox" name="featured" value="1"
                       class="h-4 w-4 border-[#333] bg-black accent-white"
                       @checked(old('featured', $focusArea->featured))>
                Featured — show prominently on the website
            </label>
        </div>

        {{-- Body --}}
        <div class="border border-[#1a1a1a] p-6 space-y-4">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Body content</p>
            <textarea name="body" rows="12"
                      class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y">{{ old('body', $focusArea->body) }}</textarea>
        </div>

        {{-- SEO --}}
        <div class="border border-[#1a1a1a] p-6 space-y-5">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">SEO</p>
            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Meta title</label>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $focusArea->meta_title) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Meta description</label>
                    <textarea name="meta_description" rows="3"
                              class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">{{ old('meta_description', $focusArea->meta_description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 border-t border-[#1a1a1a] pt-6">
            <button type="submit"
                    class="inline-flex items-center border border-white bg-white px-6 py-2.5
                           text-[10px] font-bold uppercase tracking-[0.2em] text-black
                           transition hover:bg-transparent hover:text-white">
                {{ $focusArea->exists ? 'Update focus area' : 'Create focus area' }}
            </button>
            @if ($focusArea->exists)
                <form method="POST" action="{{ route('admin.focus-areas.destroy', $focusArea) }}" class="inline">
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
