@extends('layouts.admin')

@section('title', 'Media Library')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Content</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Media Library</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Upload assets here to reuse them across homepage sections, focus areas, case studies, and articles.</p>
        </div>
    </div>

    {{-- Upload --}}
    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data"
          class="border border-[#1a1a1a] p-6 space-y-5">
        @csrf
        <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Upload new asset</p>
        <div class="grid gap-5 md:grid-cols-3">
            <div class="md:col-span-1">
                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">File</label>
                <input type="file" name="file" accept="image/*" required
                       class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] file:mr-4 file:border-0 file:bg-white file:px-3 file:py-2 file:text-[11px] file:font-semibold file:uppercase file:tracking-[0.16em] file:text-black">
            </div>
            <div>
                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Title</label>
                <input type="text" name="title" placeholder="defaults to the filename"
                       class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
            </div>
            <div>
                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Alt text</label>
                <input type="text" name="alt_text"
                       class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
            </div>
        </div>
        <button type="submit"
                class="inline-flex items-center border border-white bg-white px-6 py-2.5
                       text-[10px] font-bold uppercase tracking-[0.2em] text-black
                       transition hover:bg-transparent hover:text-white">
            Upload
        </button>
    </form>

    {{-- Grid --}}
    <div class="grid grid-cols-2 gap-px border border-[#1a1a1a] bg-[#1a1a1a] md:grid-cols-4 xl:grid-cols-6">
        @forelse ($mediaAssets as $asset)
            <div class="bg-black p-3 space-y-3">
                <div class="aspect-square overflow-hidden border border-[#1a1a1a] bg-[#080808]">
                    <img src="{{ $asset->url }}" alt="{{ $asset->alt_text }}" class="h-full w-full object-cover">
                </div>
                <div>
                    <p class="truncate text-[12px] font-medium text-white" title="{{ $asset->title }}">{{ $asset->title }}</p>
                    <p class="mt-0.5 text-[11px] text-[#444]">{{ $asset->uploader?->name ?? 'System' }}</p>
                </div>
                <form method="POST" action="{{ route('admin.media.destroy', $asset) }}"
                      onsubmit="return confirm('Delete this asset? Content referencing it will lose the image.');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-[10px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">Delete</button>
                </form>
            </div>
        @empty
            <div class="col-span-full bg-black px-5 py-12 text-center text-[12px] text-[#444]">No media assets yet.</div>
        @endforelse
    </div>

</div>
@endsection
