<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Drenla') — Client Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f5f2] text-[#161219] antialiased" style="color-scheme: light;">

<div class="flex min-h-screen w-full flex-col px-6 py-10 md:px-12">

    <header class="flex items-center justify-between border-b border-[#e4dfd6] pb-6">
        <a href="{{ route('portal.dashboard') }}" class="text-[13px] font-semibold uppercase tracking-[0.35em] text-[#161219]">
            DRENLA <span class="ml-2 font-normal text-[#8a7f6c]">Client Portal</span>
        </a>

        @auth('client')
            <div class="flex items-center gap-6">
                <span class="hidden text-[12px] text-[#8a7f6c] md:inline">{{ auth('client')->user()->company_name ?: auth('client')->user()->name }}</span>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" class="text-[11px] font-semibold uppercase tracking-[0.15em] text-[#8a7f6c] transition-colors hover:text-[#161219]">
                        Sign out
                    </button>
                </form>
            </div>
        @endauth
    </header>

    @auth('client')
        @php
            $portalNav = [
                ['label' => 'Overview',  'route' => 'portal.dashboard',      'pattern' => 'portal.dashboard'],
                ['label' => 'Projects',  'route' => 'portal.projects.index', 'pattern' => 'portal.projects.*'],
                ['label' => 'Finance',   'route' => 'portal.finance.index',  'pattern' => 'portal.finance.*'],
                ['label' => 'Proposals', 'route' => 'portal.proposals.index','pattern' => 'portal.proposals.*'],
            ];
        @endphp
        <nav class="flex items-center gap-8 border-b border-[#e4dfd6] pt-6">
            @foreach ($portalNav as $item)
                @php $isActive = request()->routeIs($item['pattern']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="pb-4 text-[11px] font-semibold uppercase tracking-[0.15em] transition-colors {{ $isActive ? 'border-b-2 border-[#161219] text-[#161219]' : 'text-[#8a7f6c] hover:text-[#161219]' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    @endauth

    <main class="flex-1 py-10">
        @if (session('status'))
            <div class="mb-8 border border-[hsl(260_60%_55%)]/30 bg-[hsl(260_60%_97%)] px-5 py-3 text-[13px] text-[#161219]">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-[#e4dfd6] pt-6 text-[11px] uppercase tracking-[0.2em] text-[#a49a86]">
        Drenla &middot; Studio Delivery Portal
    </footer>
</div>

</body>
</html>
