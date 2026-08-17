@extends('layouts.admin')

@section('title', 'Clients')

@section('content')
<div class="space-y-8">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Commercial</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Clients</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Contacts, active accounts, and portal access records.</p>
        </div>
        <a href="{{ route('admin.clients.create') }}"
           class="inline-flex items-center gap-2 border border-white bg-white px-5 py-2.5
                  text-[10px] font-bold uppercase tracking-[0.2em] text-black
                  transition hover:bg-transparent hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New client
        </a>
    </div>

    {{-- ── Table ──────────────────────────────────────────────────────────── --}}
    <div class="border border-[#1a1a1a]">
        <table class="min-w-full text-left">

            <thead class="border-b border-[#1a1a1a] bg-[#080808]">
                <tr>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Client</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Email</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Status</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Portal</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-[#0e0e0e]">
                @forelse ($clients as $client)
                    <tr class="transition-colors hover:bg-[#080808]">

                        <td class="px-5 py-4">
                            <p class="text-[13px] font-medium text-white">{{ $client->name }}</p>
                            <p class="mt-0.5 text-[11px] text-[#444]">{{ $client->company_name }}</p>
                        </td>

                        <td class="px-5 py-4 text-[13px] text-[#777]">
                            {{ $client->email ?: '—' }}
                        </td>

                        <td class="px-5 py-4">
                            @php
                                $cMap = ['active' => '#4c4', 'inactive' => '#555', 'lead' => '#7af', 'prospect' => '#aaa'];
                                $cColor = $cMap[$client->status] ?? '#555';
                            @endphp
                            <span class="text-[10px] font-bold uppercase tracking-[0.15em]"
                                  style="color: {{ $cColor }}">{{ $client->status }}</span>
                        </td>

                        <td class="px-5 py-4">
                            @if ($client->portal_access_enabled)
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#7c9]">Enabled</span>
                            @else
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#444]">Disabled</span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-5">
                                <a href="{{ route('admin.clients.edit', $client) }}"
                                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">Edit</a>
                                <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">Delete</button>
                                </form>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-[12px] text-[#444]">No clients yet.</td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
@endsection
