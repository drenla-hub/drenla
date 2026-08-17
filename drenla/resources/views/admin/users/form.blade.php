@extends('layouts.admin')

@section('title', $staffUser->exists ? 'Edit Staff User' : 'New Staff User')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">System</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">
                {{ $staffUser->exists ? 'Edit staff user' : 'New staff user' }}
            </h1>
        </div>
        <a href="{{ route('admin.users.index') }}"
           class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#555] hover:text-white transition-colors">
            ← Back
        </a>
    </div>

    <form method="POST"
          action="{{ $staffUser->exists ? route('admin.users.update', $staffUser) : route('admin.users.store') }}"
          class="space-y-8">
        @csrf
        @if ($staffUser->exists) @method('PUT') @endif

        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Details</p>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Name</label>
                    <input type="text" name="name" value="{{ old('name', $staffUser->name) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Email</label>
                    <input type="email" name="email" value="{{ old('email', $staffUser->email) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Role</label>
                    <select name="role" class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                        @foreach ($roleOptions as $role => $label)
                            <option value="{{ $role }}" @selected(old('role', $staffUser->role) === $role)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-[#444]">
                        <a href="{{ route('admin.roles.index') }}" class="underline hover:text-[#777]">Manage roles and permissions</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">
                {{ $staffUser->exists ? 'Change password' : 'Password' }}
            </p>
            @if ($staffUser->exists)
                <p class="text-[12px] text-[#555]">Leave blank to keep the current password.</p>
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Password</label>
                    <input type="password" name="password" autocomplete="new-password"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Confirm password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-4 border-t border-[#1a1a1a] pt-6">
            <button type="submit"
                    class="inline-flex items-center border border-white bg-white px-6 py-2.5
                           text-[10px] font-bold uppercase tracking-[0.2em] text-black
                           transition hover:bg-transparent hover:text-white">
                {{ $staffUser->exists ? 'Update staff user' : 'Create staff user' }}
            </button>
            @if ($staffUser->exists && ! auth()->user()->is($staffUser))
                <form method="POST" action="{{ route('admin.users.destroy', $staffUser) }}" class="inline"
                      onsubmit="return confirm('Delete this staff user?');">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">
                        Delete
                    </button>
                </form>
            @endif
        </div>
    </form>

</div>
@endsection
