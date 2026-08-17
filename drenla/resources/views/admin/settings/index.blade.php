@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div class="space-y-8">

    <div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
        <div>
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">System</p>
            <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Settings</h1>
            <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Site-wide values served through the public API and frontend.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
        @csrf
        @method('PUT')

        {{-- Site identity --}}
        <div class="border border-[#1a1a1a] p-6 space-y-6">
            <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Site identity</p>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Site name</label>
                    <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Contact email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Primary phone</label>
                    <input type="text" name="primary_phone" value="{{ old('primary_phone', $settings['primary_phone']) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
                <div>
                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Office location</label>
                    <input type="text" name="office_location" value="{{ old('office_location', $settings['office_location']) }}"
                           class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444]">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-[#555]">Footer text</label>
                <textarea name="footer_text" rows="4"
                          class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white outline-none transition focus:border-[#444] resize-y">{{ old('footer_text', $settings['footer_text']) }}</textarea>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center border-t border-[#1a1a1a] pt-6">
            <button type="submit"
                    class="inline-flex items-center border border-white bg-white px-6 py-2.5
                           text-[10px] font-bold uppercase tracking-[0.2em] text-black
                           transition hover:bg-transparent hover:text-white">
                Save settings
            </button>
        </div>
    </form>

</div>
@endsection
