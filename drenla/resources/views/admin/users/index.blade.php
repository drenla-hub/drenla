@extends('layouts.admin')

@section('title', 'Staff')

@section('content')
<div class="space-y-8">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-[#555]">System</p>
            <h1 class="mt-3 text-4xl font-light tracking-[-0.03em] text-white">Staff and roles</h1>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center border border-white bg-white px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:bg-white/90">
            New staff user
        </a>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($roleOptions as $role => $label)
            <div class="border border-[#1f1f1f] bg-[#050505] p-4">
                <div class="text-[10px] font-black uppercase tracking-[0.24em] text-white">{{ $label }}</div>
                <p class="mt-3 text-[12px] leading-5 text-[#777]">{{ $roleDescriptions[$role] }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden border border-[#1f1f1f] bg-[#050505]">
        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b border-[#1f1f1f] text-left text-[10px] font-black uppercase tracking-[0.22em] text-[#555]">
                    <th class="px-5 py-4">User</th>
                    <th class="px-5 py-4">Role</th>
                    <th class="px-5 py-4">Task Progress</th>
                    <th class="px-5 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $total = (int) $user->total_tasks_count;
                        $done = (int) $user->done_tasks_count;
                        $pct = $total > 0 ? (int) round(($done / $total) * 100) : 0;
                    @endphp
                    <tr class="border-b border-[#111] last:border-0">
                        <td class="px-5 py-4">
                            <div class="text-[13px] font-medium text-white">{{ $user->name }}</div>
                            <div class="mt-1 text-[11px] text-[#555]">{{ $user->email }}</div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="border border-white/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.16em] text-[#d9d9d9]">
                                {{ $roleOptions[$user->role] ?? $user->role }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-px w-24 bg-[#222]">
                                    <div class="h-px bg-white" style="width: {{ $pct }}%;"></div>
                                </div>
                                <span class="font-mono text-[11px] text-[#777]">{{ $done }}/{{ $total }} done</span>
                                <span class="font-mono text-[11px] text-[#444]">{{ (int) $user->active_tasks_count }} active</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-[10px] font-black uppercase tracking-[0.18em] text-[#888] transition hover:text-white">Edit</a>
                            @if (! auth()->user()->is($user))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="ml-4 inline" onsubmit="return confirm('Delete this staff user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[10px] font-black uppercase tracking-[0.18em] text-[#6f3333] transition hover:text-[#c44]">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-[12px] text-[#555]">No staff users yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
