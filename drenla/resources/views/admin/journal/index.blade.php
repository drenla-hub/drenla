@extends('layouts.admin')

@section('title', 'Journal')

@section('content')
<div data-journal-shell class="flex min-h-[420px] gap-10">

    {{-- ── Conversation list ────────────────────────────────────────────── --}}
    <aside class="w-[260px] shrink-0 self-start overflow-y-auto border-r border-[#1a1a1a] pr-10">
        <div class="flex items-center justify-between pb-3">
            <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Conversations</p>
            <a href="{{ route('admin.journal.index') }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">
                + New
            </a>
        </div>
        <div class="space-y-0.5">
            @forelse ($conversations as $item)
                @php $active = $conversation && $conversation->id === $item->id; @endphp
                <div class="group relative flex items-center {{ $active ? 'bg-[#0d0d0d]' : 'hover:bg-[#0a0a0a]' }}">
                    @if ($active)
                        <span class="absolute left-0 inset-y-[8px] w-px bg-white"></span>
                    @endif
                    <a href="{{ route('admin.journal.show', $item) }}" class="min-w-0 flex-1 px-4 py-2.5">
                        <p class="truncate text-[13px] {{ $active ? 'text-white' : 'text-[#999]' }}">{{ $item->title ?: 'New conversation' }}</p>
                        <p class="mt-0.5 text-[10px] text-[#444]">{{ ($item->last_message_at ?? $item->created_at)->diffForHumans() }}</p>
                    </a>
                    <form method="POST" action="{{ route('admin.journal.destroy', $item) }}"
                          onsubmit="return confirm('Delete this conversation? This cannot be undone.')"
                          class="pr-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-[#444] hover:text-red-400 transition-colors" title="Delete">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14Z"/></svg>
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-4 py-6 text-[12px] text-[#444]">No conversations yet — start one above.</p>
            @endforelse
        </div>
    </aside>

    {{-- ── Transcript + composer ────────────────────────────────────────── --}}
    <section class="flex min-h-0 flex-1 flex-col">
        @if ($conversation)
            <form method="POST" action="{{ route('admin.journal.update', $conversation) }}"
                class="mb-3 border-b border-[#1a1a1a] pb-3" data-rename-form>
                @csrf @method('PATCH')
                <p class="mb-1 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Title</p>
                <input type="text" name="title" value="{{ $conversation->title }}" placeholder="Untitled conversation"
                    data-rename-input
                    class="w-full max-w-sm bg-transparent text-[13px] font-medium text-white outline-none placeholder-[#444]">
            </form>
        @endif

        <div data-journal-log data-conversation-id="{{ $conversation->id ?? '' }}" class="min-h-0 flex-1 space-y-4 overflow-y-auto">
            @forelse ($conversation->messages ?? [] as $message)
                @if ($message->role === 'user')
                    <div class="flex flex-col items-end gap-1.5">
                        @if ($message->attachments->isNotEmpty())
                            <div class="flex max-w-[75%] flex-wrap justify-end gap-1.5">
                                @foreach ($message->attachments as $attachment)
                                    @if ($attachment->isImage())
                                        <a href="{{ route('admin.journal.attachments.show', $attachment) }}" target="_blank">
                                            <img src="{{ route('admin.journal.attachments.show', $attachment) }}" alt="{{ $attachment->original_name }}" class="h-24 w-24 rounded-md object-cover">
                                        </a>
                                    @else
                                        <a href="{{ route('admin.journal.attachments.show', $attachment) }}" target="_blank"
                                            class="flex items-center gap-2 rounded-md bg-[#242424] px-3 py-2 text-[12px] text-[#ccc] transition-colors hover:text-white">
                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                                            <span class="max-w-[160px] truncate">{{ $attachment->original_name }}</span>
                                            <span class="shrink-0 text-[#666]">{{ $attachment->humanSize() }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if ($message->content !== '')
                            <div class="w-fit max-w-[75%] rounded-md bg-[#242424] px-3.5 py-2 text-[14px] leading-relaxed whitespace-pre-wrap text-white">{{ $message->content }}</div>
                        @endif
                    </div>
                @else
                    <div class="group max-w-[85%]">
                        <div class="journal-markdown text-[14px] leading-relaxed text-[#e5e5e5]">
                            {!! $message->renderedContent() !!}
                        </div>
                        <div class="mt-1 flex items-center opacity-0 transition-opacity group-hover:opacity-100">
                            <button type="button" data-copy-btn title="Copy" class="text-[#555] transition-colors hover:text-white">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="13" height="13" x="9" y="9" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                            <textarea data-copy-source class="hidden">{{ $message->content }}</textarea>
                        </div>
                    </div>
                @endif
            @empty
                <div class="flex h-full items-center justify-center">
                    <p class="text-[13px] text-[#555]" data-journal-empty>Say something to get started.</p>
                </div>
            @endforelse
        </div>

        <form data-journal-form
            action="{{ $conversation ? route('admin.journal.messages.store', $conversation) : route('admin.journal.quick-start') }}"
            class="pt-3">
            @csrf
            <div class="border border-[#1f1f1f] bg-black transition-colors focus-within:border-[#444]">
                <div data-journal-file-previews class="flex flex-wrap gap-1.5 px-3 pt-3" style="display:none"></div>
                <div class="flex items-end gap-2 p-2">
                    <input type="file" data-journal-file-input multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.md,.csv,.json" class="hidden">
                    <button type="button" data-journal-attach title="Attach files"
                        class="flex h-9 w-9 shrink-0 items-center justify-center text-[#888] transition-colors hover:text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    </button>
                    <textarea data-journal-input rows="1" placeholder="Think out loud…"
                        class="max-h-[130px] flex-1 overflow-y-auto bg-transparent px-1.5 py-2.5 text-[13px] text-white placeholder-[#333] outline-none resize-none"></textarea>
                    <button type="submit" data-journal-submit
                        class="inline-flex items-center border border-white bg-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-black transition hover:bg-transparent hover:text-white">
                        Send
                    </button>
                </div>
            </div>
            <p data-journal-status class="mt-1.5 text-[11px] text-[#444]"></p>
        </form>
    </section>

</div>
@endsection
