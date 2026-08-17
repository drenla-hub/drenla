@php
    $previewLayout = collect($chapter['images'] ?? [])->pluck('layout')->first() ?? 'full';
    $chapterOrderLabel = is_numeric($chapterIndex) ? '#'.(((int) $chapterIndex) + 1) : '#__';
@endphp

<section data-chapter-row data-expanded="false" class="chapter">
    <button type="button" data-toggle-chapter class="chapter-head w-full py-6 text-left">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center border border-[#2a2a2a] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white" data-chapter-preview-num>Chapter {{ $chapter['num'] ?? '—' }}</span>
                    <span class="text-[10px] uppercase tracking-[0.18em] text-[#444]" data-chapter-order>{{ $chapterOrderLabel }}</span>
                </div>
                <div>
                    <h3 class="text-[20px] font-medium tracking-[-0.02em] text-white" data-chapter-preview-title>{{ $chapter['title'] ?: 'Untitled chapter' }}</h3>
                    <p class="mt-2 max-w-3xl text-[13px] leading-6 text-[#666]" data-chapter-preview-body">{{ \Illuminate\Support\Str::limit(collect($chapter['paragraphs'] ?? [])->first() ?: 'Add paragraphs and visual groups to shape this chapter.', 180) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="chapter-preview-grid w-[180px]" data-layout="{{ $previewLayout }}">
                    @php
                        $previewItems = collect($chapter['images'] ?? [])->flatMap(fn ($group) => $group['items'] ?? [])->take(3);
                    @endphp
                    @for ($i = 0; $i < 3; $i++)
                        @php $item = $previewItems->get($i); @endphp
                        @if ($item && !empty($item['url']))
                            <img src="{{ $item['url'] }}" alt="">
                        @else
                            <div class="placeholder @if($i > 0 && $previewLayout === 'full') hidden @endif"></div>
                        @endif
                    @endfor
                </div>
                <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-[#777]" data-chapter-toggle-label>Edit</span>
            </div>
        </div>
    </button>

    <div class="flex items-center justify-end gap-3 border-y border-[#151515] py-3">
        <button type="button" data-move-up class="mini-link">Up</button>
        <button type="button" data-move-down class="mini-link">Down</button>
        <button type="button" data-remove-chapter class="mini-link">Remove</button>
    </div>

    <div data-chapter-body class="chapter-body hidden">
        <div class="space-y-10">
            <div class="space-y-8">
                <div class="space-y-5">
                    <input type="text" value="{{ $chapter['title'] ?? '' }}" data-name-template="chapters[__CHAPTER__][title]" data-chapter-title-input class="canvas-title" placeholder="Chapter title">
                    <div class="grid gap-4 md:grid-cols-[140px_minmax(0,1fr)]">
                        <div>
                            <label class="field-label">Chapter number</label>
                            <input type="text" value="{{ $chapter['num'] ?? '' }}" data-name-template="chapters[__CHAPTER__][num]" data-chapter-num-input class="field-input" placeholder="01">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <p class="section-label">Paragraphs</p>
                        <button type="button" data-add-paragraph class="mini-link">Add paragraph</button>
                    </div>
                    <div data-paragraphs-list class="space-y-4">
                        @foreach (($chapter['paragraphs'] ?? ['']) as $paragraphIndex => $paragraph)
                            <div data-paragraph-row class="flex gap-3">
                                <textarea rows="3" data-name-template="chapters[__CHAPTER__][paragraphs][__PARAGRAPH__]" class="canvas-paragraph" placeholder="Write a chapter paragraph...">{{ $paragraph }}</textarea>
                                <button type="button" data-remove-paragraph class="mini-link">Remove</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <section class="space-y-5 border-t border-[#151515] pt-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="section-label">Image groups</p>
                        <p class="mt-2 max-w-2xl text-[12px] leading-6 text-[#666]">Add the visual sequences for this chapter in the same order they should appear on the frontend.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" data-add-group="full" class="action-btn">Add Full</button>
                        <button type="button" data-add-group="2col" class="action-btn">Add 2 Grid</button>
                        <button type="button" data-add-group="3col" class="action-btn">Add 3 Grid</button>
                    </div>
                </div>
                <div data-groups-list class="space-y-4">
                    @foreach (($chapter['images'] ?? []) as $groupIndex => $group)
                        @include('admin.case-studies.partials.image-group-row', ['chapterIndex' => $chapterIndex, 'groupIndex' => $groupIndex, 'group' => $group])
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</section>
