@extends('layouts.admin')

@section('title', $client->exists ? 'Edit Client' : 'New Client')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Commercial</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">
                {{ $client->exists ? 'Edit client' : 'New client' }}
            </h1>
        </div>
        <a href="{{ route('admin.clients.index') }}"
           class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            ← Back
        </a>
    </div>

    <form method="POST"
          action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}"
          class="space-y-8">
        @csrf
        @if($client->exists) @method('PUT') @endif

        {{-- Core details --}}
        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Details</p>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Name</label>
                    <input type="text" name="name" value="{{ old('name', $client->name) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Company name</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $client->company_name) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Email</label>
                    <input type="email" name="email" value="{{ old('email', $client->email) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $client->phone) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Status</label>
                    <select name="status" class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        <option value="lead"     @selected(old('status', $client->status) === 'lead')>Lead</option>
                        <option value="active"   @selected(old('status', $client->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Notes</label>
                <textarea name="notes" rows="6"
                          class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y">{{ old('notes', $client->notes) }}</textarea>
            </div>
        </div>

        {{-- Portal access --}}
        <div class="border border-[#1a1a1a] p-6 space-y-4">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Portal access</p>

            <label class="flex items-center gap-3 text-[12px] text-[#666] cursor-pointer select-none">
                <input type="checkbox" name="portal_access_enabled" value="1"
                       class="h-4 w-4 border-[#333] bg-black accent-white"
                       @checked(old('portal_access_enabled', $client->portal_access_enabled))>
                Enable client portal access
            </label>

            @if ($client->portal_access_enabled && $client->portal_access_url)
                <div class="border border-[#1f1f1f] bg-[#080808] px-4 py-3 space-y-3">
                    <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-[#444]">Portal link</p>
                    <div class="flex items-center gap-3">
                        <code id="portal-link" class="flex-1 truncate font-mono text-[11px] text-[#888]">{{ $client->portal_access_url }}</code>
                        <button type="button" onclick="copyPortalLink()"
                                class="shrink-0 text-[10px] font-bold uppercase tracking-[0.15em] text-white hover:text-[#aaa] transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 border-t border-[#1a1a1a] pt-6">
            <button type="submit"
                    class="inline-flex items-center border border-white bg-white px-6 py-2.5
                           text-[10px] font-bold uppercase tracking-[0.2em] text-black
                           transition hover:bg-transparent hover:text-white">
                {{ $client->exists ? 'Update client' : 'Create client' }}
            </button>
            @if ($client->exists)
                @if ($client->portal_access_enabled)
                    <form method="POST" action="{{ route('admin.clients.regenerate-token', $client) }}" class="inline"
                          onsubmit="return confirm('This invalidates the current portal link. Continue?');">
                        @csrf
                        <button type="submit" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#555] hover:text-white transition-colors">
                            Regenerate portal link
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">
                        Delete
                    </button>
                </form>
            @endif
        </div>
    </form>

    @if ($client->exists)
        {{-- Contacts --}}
        <div class="border border-[#1a1a1a] p-6 space-y-5">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Contacts</p>

            @if ($client->contacts->isNotEmpty())
                <div class="divide-y divide-[#0e0e0e] border border-[#1a1a1a]">
                    @foreach ($client->contacts as $contact)
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-[13px] font-medium text-white">
                                    {{ $contact->name }}
                                    @if ($contact->is_primary)
                                        <span class="ml-2 text-[9px] font-bold uppercase tracking-[0.15em]" style="color:#4c4">Primary</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-[11px] text-[#666]">
                                    {{ $contact->role }}
                                    @if ($contact->email) · {{ $contact->email }} @endif
                                    @if ($contact->phone) · {{ $contact->phone }} @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('admin.clients.contacts.destroy', [$client, $contact]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">Remove</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.clients.contacts.store', $client) }}" class="grid gap-3 md:grid-cols-2">
                @csrf
                <input type="text" name="name" placeholder="Name" required
                       class="w-full border border-[#1f1f1f] bg-black px-4 py-2.5 text-[13px] text-white outline-none transition focus:border-[#444]">
                <input type="text" name="role" placeholder="Role"
                       class="w-full border border-[#1f1f1f] bg-black px-4 py-2.5 text-[13px] text-white outline-none transition focus:border-[#444]">
                <input type="email" name="email" placeholder="Email"
                       class="w-full border border-[#1f1f1f] bg-black px-4 py-2.5 text-[13px] text-white outline-none transition focus:border-[#444]">
                <input type="text" name="phone" placeholder="Phone"
                       class="w-full border border-[#1f1f1f] bg-black px-4 py-2.5 text-[13px] text-white outline-none transition focus:border-[#444]">
                <label class="flex items-center gap-2 text-[12px] text-[#666] cursor-pointer select-none md:col-span-2">
                    <input type="checkbox" name="is_primary" value="1" class="h-4 w-4 border-[#333] bg-black accent-white">
                    Primary contact
                </label>
                <button type="submit"
                        class="md:col-span-2 inline-flex items-center justify-center border border-white/20 px-5 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white transition hover:border-white hover:bg-white hover:text-black">
                    Add contact
                </button>
            </form>
        </div>

        {{-- Relationship notes --}}
        <div class="border border-[#1a1a1a] p-6 space-y-5">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Relationship notes</p>

            @if ($client->leadNotes->isNotEmpty())
                <div class="space-y-4">
                    @foreach ($client->leadNotes as $note)
                        <div class="border-l-2 border-[#1f1f1f] pl-4">
                            <p class="text-[11px] text-[#555]">
                                <span class="font-semibold text-[#888]">{{ $note->author?->name ?? 'Unknown' }}</span>
                                · {{ $note->created_at->format('d M Y, H:i') }}
                            </p>
                            <p class="mt-1 text-[13px] leading-relaxed text-white/80 whitespace-pre-line">{{ $note->body }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-[12px] text-[#444]">No notes yet.</p>
            @endif

            <form method="POST" action="{{ route('admin.clients.lead-notes.store', $client) }}" class="space-y-3">
                @csrf
                <textarea name="body" rows="3" placeholder="Add a note…" required
                          class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y"></textarea>
                <button type="submit"
                        class="inline-flex items-center border border-white/20 px-5 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-white transition hover:border-white hover:bg-white hover:text-black">
                    Add note
                </button>
            </form>
        </div>
    @endif

</div>

@push('scripts')
<script>
function copyPortalLink() {
    var el = document.getElementById('portal-link');
    if (!el) return;
    navigator.clipboard.writeText(el.textContent.trim());
}
</script>
@endpush
@endsection
