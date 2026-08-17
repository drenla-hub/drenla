@extends('layouts.admin')

@section('title', $role->exists ? 'Edit Role' : 'New Role')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">System</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">
                {{ $role->exists ? 'Edit role' : 'New role' }}
            </h1>
            @if ($role->exists && $role->is_system)
                <p class="mt-2 text-[13px] leading-relaxed text-[#555]">A shipped system role — the label, description, and permissions can be changed, but it can't be deleted.</p>
            @endif
        </div>
        <a href="{{ route('admin.roles.index') }}"
           class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            ← Back
        </a>
    </div>

    <form method="POST"
          action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
          class="space-y-8">
        @csrf
        @if ($role->exists) @method('PUT') @endif

        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Details</p>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Label</label>
                    <input type="text" name="label" value="{{ old('label', $role->label) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Sort order</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $role->sort_order ?? 0) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
            </div>

            @if ($role->exists)
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Slug</label>
                    <p class="font-mono text-[12px] text-[#666]">{{ $role->slug }}</p>
                    <p class="mt-1 text-[11px] text-[#444]">Fixed after creation — staff user records reference it.</p>
                </div>
            @endif

            <div>
                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Description</label>
                <textarea name="description" rows="2"
                          class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y">{{ old('description', $role->description) }}</textarea>
            </div>
        </div>

        <div class="border border-[#1a1a1a] p-6 space-y-4">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Permissions</p>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($permissionCatalog as $key => $label)
                    <label class="flex items-center gap-3 text-[12px] text-[#666] cursor-pointer select-none">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}"
                               class="h-4 w-4 border-[#333] bg-black accent-white"
                               @checked(in_array($key, old('permissions', $role->permissions ?? []), true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 border-t border-[#1a1a1a] pt-6">
            <button type="submit"
                    class="inline-flex items-center border border-white bg-white px-6 py-2.5
                           text-[10px] font-bold uppercase tracking-[0.2em] text-black
                           transition hover:bg-transparent hover:text-white">
                {{ $role->exists ? 'Update role' : 'Create role' }}
            </button>
            @if ($role->exists && ! $role->is_system)
                @if ($role->users()->exists())
                    <span class="text-[11px] text-[#444]">Reassign staff off this role before it can be deleted.</span>
                @else
                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline"
                          onsubmit="return confirm('Delete this role?');">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">
                            Delete
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </form>

</div>
@endsection
