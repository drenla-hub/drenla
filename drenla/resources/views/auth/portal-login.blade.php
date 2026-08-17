@extends('layouts.portal')

@section('title', 'Sign In')

@section('content')
<div class="mx-auto max-w-[420px] py-10">
    <p class="text-[10px] font-semibold uppercase tracking-[0.35em] text-[#8a7f6c]">Client Access</p>
    <h1 class="mt-3 text-[28px] font-light tracking-[-0.02em] text-[#161219]">Enter your access link</h1>
    <p class="mt-3 text-[13px] leading-relaxed text-[#5f5648]">
        Paste the access token from the link your Drenla contact sent you. If you have the full link, opening it directly signs you in automatically.
    </p>

    @if ($errors->any())
        <div class="mt-6 border border-red-900/20 bg-red-50 px-4 py-3 text-[12px] text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('portal.login.store') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label class="mb-2 block text-[9px] font-black uppercase tracking-[0.3em] text-[#8a7f6c]">Access token</label>
            <input type="text" name="token" required autofocus
                   class="w-full border border-[#e4dfd6] bg-white px-4 py-3 text-[13px] text-[#161219] placeholder-[#c9c0af] focus:border-[#161219] focus:outline-none">
        </div>

        <button type="submit"
                class="w-full border border-[#161219] bg-[#161219] px-5 py-3 text-[10px] font-bold uppercase tracking-[0.2em] text-white transition hover:bg-transparent hover:text-[#161219]">
            Enter portal
        </button>
    </form>
</div>
@endsection
