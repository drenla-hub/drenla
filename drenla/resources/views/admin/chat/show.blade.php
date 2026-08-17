@extends('layouts.admin')

@section('title', $chatSession->visitor_name ?: 'Conversation')

@section('content')
<div class="space-y-8">

    {{-- ── Back + header ───────────────────────────────────────────────────── --}}
    <div class="border-b border-[#1a1a1a] pb-8">
        <a href="{{ route('admin.chat.index') }}"
           class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Conversations
        </a>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <h1 class="text-[32px] font-light tracking-[-0.025em] text-white">{{ $chatSession->visitor_name ?: 'Anonymous visitor' }}</h1>
            <span data-ai-status-badge class="inline-flex items-center gap-2 border px-3 py-1 text-[10px] font-bold uppercase tracking-[0.15em] {{ $chatSession->ai_enabled ? 'border-[#77ccff]/30 text-[#77ccff]' : 'border-[#ccaa77]/30 text-[#ccaa77]' }}">
                <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                <span data-ai-status-label>{{ $chatSession->ai_enabled ? 'AI replying' : 'Human handling' }}</span>
            </span>
        </div>
        @if ($chatSession->title)
            <p class="mt-1 text-[13px] text-[#777]">{{ $chatSession->title }}</p>
        @endif
        <p class="mt-1 text-[12px] text-[#555]">Started {{ $chatSession->created_at->format('d M Y · H:i') }}</p>
    </div>

    <div class="grid gap-8 xl:grid-cols-[1fr_320px]">

        {{-- ── Transcript ──────────────────────────────────────────────────── --}}
        <section class="border border-[#1a1a1a]">
            <div class="flex items-center justify-between border-b border-[#1a1a1a] px-5 py-3 bg-[#080808]">
                <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Transcript</p>
                @if (auth()->user()->hasPermission(\App\Support\Permission::MANAGE_CHAT) && ! $chatSession->ai_enabled)
                    <form method="POST" action="{{ route('admin.chat.resume-ai', $chatSession) }}">
                        @csrf
                        <button type="submit" class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#77ccff] hover:text-white transition-colors">
                            Resume AI
                        </button>
                    </form>
                @endif
            </div>
            <div data-transcript-log class="space-y-5 px-6 py-6" data-after-id="{{ $chatSession->messages->max('id') ?? 0 }}">
                @forelse ($chatSession->messages as $message)
                    @include('admin.chat._message', ['message' => $message])
                @empty
                    <p class="text-[12px] text-[#444]" data-transcript-empty>No messages recorded.</p>
                @endforelse
            </div>

            @if (auth()->user()->hasPermission(\App\Support\Permission::MANAGE_CHAT))
                <form method="POST" action="{{ route('admin.chat.reply', $chatSession) }}" class="border-t border-[#1a1a1a] p-5">
                    @csrf
                    <label class="mb-2 block text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Reply as yourself</label>
                    <textarea name="content" rows="3" required placeholder="Type a reply — this pauses the AI for this conversation until you resume it"
                        class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white placeholder-[#333] focus:border-[#444] focus:outline-none transition-colors resize-none"></textarea>
                    <button type="submit"
                        class="mt-3 inline-flex items-center border border-white bg-white px-5 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-black transition hover:bg-transparent hover:text-white">
                        Send reply
                    </button>
                </form>
            @endif
        </section>

        {{-- ── Visitor panel ──────────────────────────────────────────────── --}}
        <section class="border border-[#1a1a1a]">
            <div class="border-b border-[#1a1a1a] px-5 py-3 bg-[#080808]">
                <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Visitor</p>
            </div>
            <div class="divide-y divide-[#0e0e0e]">
                @foreach ([
                    'Email' => $chatSession->visitor_email,
                    'Phone' => $chatSession->visitor_phone,
                    'IP address' => $chatSession->ip_address,
                    'Location' => trim(collect([$chatSession->city, $chatSession->country])->filter()->implode(', ')),
                    'Last active' => ($chatSession->last_message_at ?? $chatSession->created_at)->format('d M Y · H:i'),
                ] as $label => $value)
                    <div class="px-5 py-4">
                        <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">{{ $label }}</p>
                        <p class="mt-1 text-[13px] text-white">{{ $value ?: '—' }}</p>
                    </div>
                @endforeach
            </div>

            @if (auth()->user()->hasPermission(\App\Support\Permission::MANAGE_CHAT))
                <div class="border-t border-[#1a1a1a] p-5">
                    <form method="POST" action="{{ route('admin.chat.destroy', $chatSession) }}"
                          onsubmit="return confirm('Delete this conversation?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-full border border-red-500/20 px-5 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-red-400/70 transition hover:border-red-500/40 hover:text-red-300">
                            Delete conversation
                        </button>
                    </form>
                </div>
            @endif
        </section>

    </div>

</div>

<div data-chat-poll data-messages-url="{{ route('admin.chat.messages', $chatSession) }}" class="hidden"></div>
@endsection
