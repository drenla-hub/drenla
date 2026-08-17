@extends('layouts.admin')

@section('title', 'Roles')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">System</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Roles</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-[#555]">
                Staff roles and what each one is allowed to do. <a href="{{ route('admin.users.index') }}" class="underline hover:text-white">Staff users</a> pick from these.
            </p>
        </div>
        <a href="{{ route('admin.roles.create') }}"
           class="inline-flex items-center gap-2 border border-white bg-white px-5 py-2.5
                  text-[10px] font-bold uppercase tracking-[0.2em] text-black
                  transition hover:bg-transparent hover:text-white">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New role
        </a>
    </div>

    <div class="border border-[#1a1a1a]">
        <table class="min-w-full text-left">
            <thead class="border-b border-[#1a1a1a] bg-[#080808]">
                <tr>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Role</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Permissions</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Staff</th>
                    <th class="px-5 py-3 text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Type</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#0e0e0e]">
                @forelse ($roles as $role)
                    <tr class="transition-colors hover:bg-[#080808]">
                        <td class="px-5 py-4">
                            <p class="text-[13px] font-medium text-white">{{ $role->label }}</p>
                            <p class="mt-0.5 text-[11px] text-[#444]">{{ $role->slug }}</p>
                            @if ($role->description)
                                <p class="mt-1 text-[11px] text-[#555]">{{ $role->description }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @forelse ($role->permissions ?? [] as $permission)
                                <span class="mr-1.5 mb-1.5 inline-block border border-[#1f1f1f] px-2 py-1 text-[9px] font-bold uppercase tracking-[0.12em] text-[#888]">
                                    {{ $permissionCatalog[$permission] ?? $permission }}
                                </span>
                            @empty
                                <span class="text-[#333]">—</span>
                            @endforelse
                        </td>
                        <td class="px-5 py-4 text-[12px] text-[#666]">{{ $role->users_count }}</td>
                        <td class="px-5 py-4">
                            @if ($role->is_system)
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-[#777]">System</span>
                            @else
                                <span class="text-[10px] font-bold uppercase tracking-[0.15em]" style="color:#7c9">Custom</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-5">
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                   class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">Edit</a>
                                @if (! $role->is_system && $role->users_count === 0)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline"
                                          onsubmit="return confirm('Delete this role?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-[12px] text-[#444]">No roles yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
