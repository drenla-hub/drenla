<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Drenla') — Ops</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body
    class="bg-black text-white antialiased overflow-hidden h-screen"
    data-admin-shell
    data-ai-assistant-endpoint="{{ route('admin.ai-assistant.respond') }}"
>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{--  SHELL                                                          --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="flex h-screen">

    {{-- ════════════════ SIDEBAR ════════════════ --}}
    <aside id="sidebar"
           data-collapsed="false"
           class="group/sidebar relative z-30 flex flex-col shrink-0
                  w-[240px] data-[collapsed=true]:w-[56px]
                  bg-black border-r border-[#1f1f1f]
                  transition-[width] duration-300 ease-in-out
                  overflow-hidden">

        {{-- Logo strip --}}
        <div class="flex items-center h-[80px] shrink-0 px-4 border-b border-[#1f1f1f]">
            @php
                $logoPath = base_path('../frontend/src/assets/drenla-logo-white.png');
                $logoData = file_exists($logoPath)
                    ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
                    : null;
            @endphp

            @if ($logoData)
                <img src="{{ $logoData }}" alt="Drenla"
                     class="h-[17px] w-auto group-data-[collapsed=true]/sidebar:hidden">
                <div class="hidden group-data-[collapsed=true]/sidebar:flex items-center justify-center w-full">
                    <img src="{{ $logoData }}" alt="D"
                         class="h-4 w-auto object-contain object-left" style="max-width:22px;object-position:left center;">
                </div>
            @else
                <span class="text-[15px] font-light tracking-[0.12em] group-data-[collapsed=true]/sidebar:hidden">DRENLA</span>
                <span class="hidden group-data-[collapsed=true]/sidebar:block text-xs font-bold tracking-widest">D</span>
            @endif

            <span class="ml-auto text-[9px] font-black tracking-[0.4em] uppercase text-[#666] group-data-[collapsed=true]/sidebar:hidden">
                OPS
            </span>
        </div>

        {{-- Navigation --}}
        @php
            $currentUser = auth()->user();

            $navGroups = [
                [
                    'label' => 'Overview',
                    'items' => [
                        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin.dashboard'],
                        ['label' => 'Journal',   'route' => 'admin.journal.index', 'pattern' => 'admin.journal.*'],
                    ],
                ],
                [
                    'label' => 'Commercial',
                    'items' => [
                        ['label' => 'Clients',       'route' => 'admin.clients.index',   'pattern' => 'admin.clients.*',   'permissions' => [\App\Support\Permission::VIEW_CLIENTS, \App\Support\Permission::MANAGE_CLIENTS]],
                        ['label' => 'Finance',       'route' => 'admin.finance.index',   'pattern' => 'admin.finance.*',   'permissions' => [\App\Support\Permission::VIEW_FINANCE, \App\Support\Permission::MANAGE_FINANCE]],
                        ['label' => 'Conversations', 'route' => 'admin.chat.index',      'pattern' => 'admin.chat.*',      'permissions' => [\App\Support\Permission::VIEW_CHAT, \App\Support\Permission::MANAGE_CHAT]],
                    ],
                ],
                [
                    'label' => 'Delivery',
                    'items' => [
                        ['label' => 'Project Briefs', 'route' => 'admin.proposals.index',  'pattern' => 'admin.proposals.*', 'permissions' => [\App\Support\Permission::VIEW_PROPOSALS, \App\Support\Permission::MANAGE_PROPOSALS]],
                        ['label' => 'Projects',       'route' => 'admin.projects.index',   'pattern' => 'admin.projects.*',  'permissions' => [\App\Support\Permission::VIEW_PROJECTS, \App\Support\Permission::MANAGE_PROJECTS]],
                        ['label' => 'Inquiries',      'route' => 'admin.inquiries.index',  'pattern' => 'admin.inquiries.*', 'permissions' => [\App\Support\Permission::VIEW_CLIENTS, \App\Support\Permission::MANAGE_CLIENTS]],
                    ],
                ],
                [
                    'label' => 'Content',
                    'items' => [
                        ['label' => 'Homepage',     'route' => 'admin.homepage.index',     'pattern' => 'admin.homepage.*',     'permissions' => [\App\Support\Permission::VIEW_CONTENT, \App\Support\Permission::MANAGE_CONTENT]],
                        ['label' => 'Case Studies', 'route' => 'admin.case-studies.index', 'pattern' => 'admin.case-studies.*', 'permissions' => [\App\Support\Permission::VIEW_CONTENT, \App\Support\Permission::MANAGE_CONTENT]],
                        ['label' => 'Articles',     'route' => 'admin.articles.index',     'pattern' => 'admin.articles.*',     'permissions' => [\App\Support\Permission::VIEW_CONTENT, \App\Support\Permission::MANAGE_CONTENT]],
                        ['label' => 'Focus Areas',  'route' => 'admin.focus-areas.index',  'pattern' => 'admin.focus-areas.*',  'permissions' => [\App\Support\Permission::VIEW_CONTENT, \App\Support\Permission::MANAGE_CONTENT]],
                        ['label' => 'Media',        'route' => 'admin.media.index',        'pattern' => 'admin.media.*',        'permissions' => [\App\Support\Permission::VIEW_MEDIA, \App\Support\Permission::MANAGE_MEDIA]],
                    ],
                ],
                [
                    'label' => 'System',
                    'items' => [
                        ['label' => 'Staff', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'permissions' => [\App\Support\Permission::VIEW_USERS, \App\Support\Permission::MANAGE_USERS]],
                        ['label' => 'Roles', 'route' => 'admin.roles.index', 'pattern' => 'admin.roles.*', 'permissions' => [\App\Support\Permission::VIEW_USERS, \App\Support\Permission::MANAGE_USERS]],
                        ['label' => 'Settings', 'route' => 'admin.settings.edit', 'pattern' => 'admin.settings.*', 'permissions' => [\App\Support\Permission::VIEW_SETTINGS, \App\Support\Permission::MANAGE_SETTINGS]],
                    ],
                ],
            ];

            // Filter groups and items based on permissions
            $navGroups = collect($navGroups)->map(function ($group) use ($currentUser) {
                $group['items'] = collect($group['items'])->filter(function ($item) use ($currentUser) {
                    if (empty($item['permissions'])) {
                        return true;
                    }
                    return $currentUser?->hasAnyPermission(...$item['permissions']) ?? false;
                })->values()->all();

                return $group;
            })->filter(fn ($group) => ! empty($group['items']))->values()->all();

            $icons = [
                'Dashboard'    => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="3" y="15" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/>',
                'Journal'      => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>',
                'Clients'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                'Project Briefs' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
                'Finance'      => '<rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/>',
                'Conversations' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
                'Projects'     => '<rect width="20" height="14" x="2" y="7" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
                'Inquiries'    => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
                'Case Studies' => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
                'Articles'     => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/>',
                'Focus Areas'  => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
                'Homepage'     => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
                'Media'        => '<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/><path d="m14 19 3-3 4 4"/>',
                'Staff'        => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" x2="20" y1="8" y2="14"/><line x1="23" x2="17" y1="11" y2="11"/>',
                'Roles'        => '<path d="M12 2 3 6v6c0 5 4 8.5 9 10 5-1.5 9-5 9-10V6z"/>',
                'Settings'     =>'<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
            ];
        @endphp

        <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-2 space-y-6 scrollbar-none bg-black">
            @foreach ($navGroups as $group)
                <div>
                    {{-- Group label — hidden when collapsed --}}
                    <p class="px-3 pb-1.5 text-[9px] font-black uppercase tracking-[0.35em] text-[#666]
                               group-data-[collapsed=true]/sidebar:hidden">
                        {{ $group['label'] }}
                    </p>

                    @foreach ($group['items'] as $item)
                        @php $active = request()->routeIs($item['pattern']); @endphp
                        <a href="{{ route($item['route']) }}"
                           title="{{ $item['label'] }}"
                           class="relative flex items-center gap-3 px-3 py-2 text-[12px] font-semibold uppercase tracking-[0.15em] transition-all duration-150 cursor-pointer
                                  group-data-[collapsed=true]/sidebar:justify-center group-data-[collapsed=true]/sidebar:px-0
                                  {{ $active
                                      ? 'bg-[#111] text-white'
                                      : 'text-[#888] hover:bg-[#0a0a0a] hover:text-white' }}">

                            {{-- Active left-border indicator --}}
                            @if ($active)
                                <span class="absolute left-0 inset-y-[5px] w-px bg-white
                                             group-data-[collapsed=true]/sidebar:hidden"></span>
                            @endif

                            {{-- Icon --}}
                            <svg class="shrink-0 w-[14px] h-[14px] {{ $active ? 'text-white' : 'text-[#666]' }}"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                {!! $icons[$item['label']] ?? '' !!}
                            </svg>

                            {{-- Label — hidden when collapsed --}}
                            <span class="truncate group-data-[collapsed=true]/sidebar:hidden">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- Sidebar footer: logout + collapse toggle --}}
        <div class="border-t border-[#1f1f1f] shrink-0">

            {{-- Logout --}}
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" title="Sign out"
                        class="flex w-full items-center gap-3 px-5 py-3
                               text-[12px] font-semibold uppercase tracking-[0.15em] text-[#777]
                               hover:bg-[#0a0a0a] hover:text-white transition-colors
                               group-data-[collapsed=true]/sidebar:justify-center group-data-[collapsed=true]/sidebar:px-0 group-data-[collapsed=true]/sidebar:py-3.5">
                    <svg class="shrink-0 w-[14px] h-[14px]" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" x2="9" y1="12" y2="12"/>
                    </svg>
                    <span class="group-data-[collapsed=true]/sidebar:hidden">Sign out</span>
                </button>
            </form>

            {{-- Collapse toggle --}}
            <button onclick="sidebarToggle()" title="Toggle sidebar"
                    class="flex w-full items-center justify-center h-9 border-t border-[#1f1f1f]
                           text-[#555] hover:bg-[#0a0a0a] hover:text-white transition-colors">
                <svg id="sidebar-chevron"
                     class="w-3 h-3 transition-transform duration-300"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
        </div>
    </aside>

    {{-- ════════════════ MAIN ════════════════ --}}
    <div class="flex flex-1 flex-col min-w-0 overflow-hidden">

        {{-- ── Notification bar ──────────────────────────────────────────── --}}
        @if (session('status'))
            <div class="shrink-0 flex items-center gap-3 px-8 py-2.5 border-b border-emerald-900/60 bg-emerald-950/40">
                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                </svg>
                <p class="text-[12px] text-emerald-400/90">{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="shrink-0 flex items-start gap-3 px-8 py-2.5 border-b border-red-900/60 bg-red-950/40">
                <svg class="w-3.5 h-3.5 text-red-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" x2="12" y1="8" y2="12"/>
                    <line x1="12" x2="12.01" y1="16" y2="16"/>
                </svg>
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li class="text-[12px] text-red-400/90">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── Scrollable content ─────────────────────────────────────────── --}}
        <main class="flex-1 overflow-y-auto">
            <div class="px-8 py-8 xl:px-10 xl:py-10">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<div id="oile-root" class="fixed bottom-5 right-5 z-50">
    <button
        id="oile-launcher"
        type="button"
        aria-expanded="false"
        class="flex h-12 w-12 items-center justify-center border border-[#1f1f1f] bg-black text-white shadow-[0_20px_60px_rgba(0,0,0,0.55)] transition hover:border-[#3a3a3a] hover:text-[#d9d9d9]"
    >
        <span class="text-[9px] font-black uppercase tracking-[0.32em]">OI</span>
    </button>

    <div
        id="oile-panel"
        class="hidden absolute bottom-14 right-0 flex h-[min(620px,72vh)] w-[360px] flex-col overflow-hidden border border-[#1f1f1f] bg-black shadow-[0_35px_120px_rgba(0,0,0,0.78)]"
    >
        <div class="flex items-center justify-between border-b border-[#1f1f1f] px-5 py-4">
            <div>
                <div class="text-[9px] font-black uppercase tracking-[0.35em] text-[#3a3a3a]">Admin Assistant</div>
                <div class="mt-1 text-[15px] font-light text-white">Oile</div>
            </div>
            <button id="oile-close" type="button" class="text-[9px] font-black uppercase tracking-[0.28em] text-[#4a4a4a] transition hover:text-[#bdbdbd]">
                Close
            </button>
        </div>

        <div id="oile-log" class="flex-1 space-y-3 overflow-y-auto px-4 py-4"></div>

        <div class="border-t border-[#1f1f1f] px-4 py-3">
            <div class="mb-2 flex items-center justify-between text-[9px] font-black uppercase tracking-[0.28em] text-[#3f3f3f]">
                <span>Assistant</span>
                <span id="oile-status">Ready</span>
            </div>
            <form id="oile-form" class="relative">
                <textarea
                    id="oile-input"
                    rows="1"
                    placeholder="Ask Oile to fill fields, inspect this page, or navigate."
                    class="max-h-[108px] min-h-[44px] w-full resize-none overflow-y-auto border border-[#1f1f1f] bg-[#090909] px-3 py-3 pr-12 text-[12px] leading-5 text-white placeholder:text-[#353535] focus:border-[#3a3a3a] focus:outline-none"
                ></textarea>
                <button
                    type="submit"
                    aria-label="Send prompt"
                    class="absolute bottom-0 right-0 flex h-[44px] w-[44px] items-center justify-center text-[#6a6a6a] transition hover:text-white focus:text-white focus:outline-none"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 2 11 13"/>
                        <path d="M22 2 15 22 11 13 2 9 22 2z"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{--  Sidebar JS — restore state before first paint               --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<script>
    (function () {
        var el = document.getElementById('sidebar');
        var saved = localStorage.getItem('drenla-sidebar');
        if (saved === 'true') {
            el.dataset.collapsed = 'true';
            document.getElementById('sidebar-chevron').style.transform = 'rotate(180deg)';
        }
    })();

    function sidebarToggle() {
        var el = document.getElementById('sidebar');
        var chevron = document.getElementById('sidebar-chevron');
        var isCollapsed = el.dataset.collapsed === 'true';
        el.dataset.collapsed = isCollapsed ? 'false' : 'true';
        chevron.style.transform = isCollapsed ? '' : 'rotate(180deg)';
        localStorage.setItem('drenla-sidebar', isCollapsed ? 'false' : 'true');
    }
</script>

@stack('scripts')
</body>
</html>
