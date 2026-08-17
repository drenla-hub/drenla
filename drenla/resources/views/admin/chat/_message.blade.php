{{--
    A single transcript row — 3-way sender split (visitor / assistant / admin).
    Admin messages are labeled with the actual sending admin's name, not a
    generic label, per the "clear note of who is sending messages" requirement.
    Props: $message (ChatMessage, with `sender` loaded if role === 'admin')
--}}
@php
    $isVisitor = $message->role === 'user';
    $isAdmin = $message->role === 'admin';
    $label = $isVisitor ? 'Visitor' : ($isAdmin ? ($message->sender?->name ?? 'Admin') : 'Assistant');
    $bubbleClass = $isVisitor
        ? 'border-[#1f1f1f] bg-black text-[#ccc]'
        : ($isAdmin ? 'border-white/25 bg-white text-black' : 'border-[#2a2a3a] bg-[#0d0c14] text-white');
@endphp
<div class="flex flex-col {{ $isVisitor ? 'items-start' : 'items-end' }}" data-message-row data-message-id="{{ $message->id }}">
    <p class="mb-1 text-[9px] font-black uppercase tracking-[0.2em] text-[#444]">
        {{ $label }} &middot; {{ $message->created_at->format('H:i') }}
    </p>
    <div class="max-w-[85%] border px-4 py-3 text-[13px] leading-relaxed whitespace-pre-wrap {{ $bubbleClass }}">
        {{ $message->content }}
    </div>
    @if (! empty($message->tool_call))
        <p class="mt-1 font-mono text-[10px] text-[#444]">tool_call: {{ json_encode($message->tool_call) }}</p>
    @endif
</div>
