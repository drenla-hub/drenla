@extends('layouts.admin')

@section('title', 'Conversations')

@section('content')
<div class="space-y-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="border-b border-[#1a1a1a] pb-8">
        <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Delivery</p>
        <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Conversations</h1>
        <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Captured through the site's AI chat widget.</p>
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────────── --}}
    <div class="border border-[#1a1a1a]">
        <table class="min-w-full text-left">

            <thead class="border-b border-[#1a1a1a] bg-[#080808]">
                <tr>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Visitor</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Contact</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Handling</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Messages</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Last active</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-[#0e0e0e]">
                @forelse ($sessions as $session)
                    <tr class="transition-colors hover:bg-[#080808]">

                        <td class="px-5 py-4">
                            <p class="text-[13px] font-medium text-white">{{ $session->visitor_name ?: 'Anonymous visitor' }}</p>
                            @if ($session->title)
                                <p class="mt-0.5 text-[11px] text-[#555]">{{ $session->title }}</p>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-[13px] text-[#777]">
                            {{ $session->visitor_email ?: ($session->visitor_phone ?: '—') }}
                        </td>

                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.15em]" style="color: {{ $session->ai_enabled ? '#77ccff' : '#ccaa77' }}">
                                <span class="h-1.5 w-1.5 rounded-full" style="background: currentColor;"></span>
                                {{ $session->ai_enabled ? 'AI' : 'Human' }}
                            </span>
                        </td>

                        <td class="px-5 py-4 font-mono text-[13px] text-[#777]">{{ $session->messages_count }}</td>

                        <td class="px-5 py-4 text-[11px] text-[#444]">
                            {{ ($session->last_message_at ?? $session->created_at)->format('d M Y · H:i') }}
                        </td>

                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-4">
                                <a href="{{ route('admin.chat.show', $session) }}"
                                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">Open</a>
                                @if (auth()->user()->hasPermission(\App\Support\Permission::MANAGE_CHAT))
                                    <form method="POST" action="{{ route('admin.chat.destroy', $session) }}"
                                          onsubmit="return confirm('Delete this conversation?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-[12px] text-[#444]">No conversations yet.</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    @if ($sessions->hasPages())
        <div class="pt-2">{{ $sessions->links() }}</div>
    @endif

</div>
@endsection
