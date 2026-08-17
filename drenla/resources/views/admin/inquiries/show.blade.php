@extends('layouts.admin')

@section('title', $inquiry->name)

@section('content')
<div class="space-y-8">

    {{-- ── Back + header ───────────────────────────────────────────────────── --}}
    <div class="border-b border-[#1a1a1a] pb-8">
        <a href="{{ route('admin.inquiries.index') }}"
           class="inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Inquiries
        </a>
        <h1 class="mt-4 text-[32px] font-light tracking-[-0.025em] text-white">{{ $inquiry->name }}</h1>
        <p class="mt-1 text-[12px] text-[#555]">Received {{ $inquiry->created_at->format('d M Y · H:i') }}</p>
    </div>

    <div class="grid gap-8 xl:grid-cols-[1fr_380px]">

        {{-- ── Inquiry detail ──────────────────────────────────────────────── --}}
        <section class="space-y-6">

            {{-- Meta grid --}}
            <div class="grid grid-cols-2 gap-px border border-[#1a1a1a] bg-[#1a1a1a] md:grid-cols-4">
                @foreach ([
                    'Email'   => $inquiry->email,
                    'Phone'   => $inquiry->phone ?: '—',
                    'Company' => $inquiry->company ?: '—',
                    'Subject' => $inquiry->subject ?: '—',
                ] as $metaLabel => $metaVal)
                    <div class="bg-black px-5 py-4">
                        <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">{{ $metaLabel }}</p>
                        <p class="mt-2 text-[13px] text-white">{{ $metaVal }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Message --}}
            <div class="border border-[#1a1a1a]">
                <div class="border-b border-[#1a1a1a] px-5 py-3 bg-[#080808]">
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Message</p>
                </div>
                <div class="px-6 py-5">
                    <p class="text-[14px] leading-[1.8] text-[#aaa] whitespace-pre-wrap">{{ $inquiry->message }}</p>
                </div>
            </div>

            {{-- Relationship notes (timestamped history, separate from the flat quick-notes field) --}}
            <div class="border border-[#1a1a1a]">
                <div class="border-b border-[#1a1a1a] px-5 py-3 bg-[#080808]">
                    <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Relationship notes</p>
                </div>
                <div class="space-y-4 px-6 py-5">
                    @forelse ($inquiry->leadNotes as $note)
                        <div class="border-l-2 border-[#1f1f1f] pl-4">
                            <p class="text-[11px] text-[#555]">
                                <span class="font-semibold text-[#888]">{{ $note->author?->name ?? 'Unknown' }}</span>
                                · {{ $note->created_at->format('d M Y, H:i') }}
                            </p>
                            <p class="mt-1 text-[13px] leading-relaxed text-white/80 whitespace-pre-line">{{ $note->body }}</p>
                        </div>
                    @empty
                        <p class="text-[12px] text-[#444]">No notes yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('admin.inquiries.lead-notes.store', $inquiry) }}" class="space-y-3 pt-2">
                        @csrf
                        <textarea name="body" rows="3" placeholder="Add a note…" required
                                  class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y"></textarea>
                        <button type="submit"
                                class="inline-flex items-center border border-white/20 px-5 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white transition hover:border-white hover:bg-white hover:text-black">
                            Add note
                        </button>
                    </form>
                </div>
            </div>

        </section>

        {{-- ── Management panel ───────────────────────────────────────────── --}}
        <section class="border border-[#1a1a1a]">
            <div class="border-b border-[#1a1a1a] px-5 py-3 bg-[#080808]">
                <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Manage inquiry</p>
            </div>

            <form method="POST" action="{{ route('admin.inquiries.update', $inquiry) }}" class="space-y-5 p-5">
                @csrf
                @method('PATCH')

                <div>
                    <label class="mb-2 block text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Status</label>
                    <select name="status"
                            class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white focus:border-[#444] focus:outline-none transition-colors">
                        @foreach (['new', 'qualified', 'proposal_sent', 'won', 'lost'] as $status)
                            <option value="{{ $status }}" @selected($inquiry->status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Assignee</label>
                    <select name="assigned_to"
                            class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white focus:border-[#444] focus:outline-none transition-colors">
                        <option value="">Unassigned</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($inquiry->assigned_to === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Notes</label>
                    <textarea name="notes" rows="8"
                              class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white placeholder-[#333] focus:border-[#444] focus:outline-none transition-colors resize-none">{{ old('notes', $inquiry->notes) }}</textarea>
                </div>

                <button type="submit"
                        class="w-full border border-white bg-white px-5 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-black transition hover:bg-transparent hover:text-white">
                    Save changes
                </button>

            </form>
        </section>

    </div>

</div>
@endsection
