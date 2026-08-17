@extends('layouts.admin')

@php
    $document = old('document', $document);
    $sections = collect($document['sections'] ?? [])->values()->all();
    $currentTemplateKey = old('template_key', $proposal->template_key ?: \App\Support\ProposalDocumentData::TEMPLATE_KEY);
    $appearance = array_merge([
        'cover_tone' => 'plum',
        'hero_surface' => 'mist',
        'section_strip' => 'stone',
        'content_density' => 'comfortable',
        'section_banner_style' => 'plain',
        'masthead_tagline' => false,
    ], old('document.appearance', $document['appearance'] ?? []));
    $acceptance = old('document.acceptance', $document['acceptance'] ?? ['enabled' => false, 'intro_text' => '', 'date_label' => '', 'signers' => []]);

    // Mirrors of ParsesProposalBlockInput's mini-syntax, in reverse — turns a saved
    // block's structured arrays back into the textarea text an admin edits, so
    // round-tripping an existing bullet_list/multi_column_list/stage_grid/
    // signature_block through the form doesn't lose its shape.
    $serializeItems = fn (array $items) => implode("\n", $items);
    $serializeColumns = fn (array $columns) => collect($columns)
        ->map(fn ($c) => trim($c['heading'] ?? '').': '.implode(', ', $c['items'] ?? []))
        ->implode("\n");
    $serializeStages = fn (array $stages) => collect($stages)
        ->map(function ($s) {
            $head = trim(trim($s['label'] ?? '').' | '.($s['duration'] ?? ''), ' |');
            $items = collect($s['items'] ?? [])->map(fn ($i) => '- '.$i)->implode("\n");

            return trim($head."\n".$items);
        })
        ->implode("\n\n");
    $serializeSigners = fn (array $signers) => collect($signers)
        ->map(fn ($s) => implode(' | ', [$s['role'] ?? '', $s['name'] ?? '', $s['subtitle'] ?? '', $s['phone'] ?? '']))
        ->implode("\n");
@endphp

@section('content')
<style>
    textarea.auto-resize { resize: none; overflow: hidden; min-height: 4rem; }

    /* Unsaved badge */
    #unsaved-badge { display: none; }
    #unsaved-badge.visible { display: flex; }

    /* Animations */
    @keyframes sectionIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    @keyframes blockIn   { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
    @keyframes saveFlash {
        0%   { background:#000; color:#fff; }
        50%  { background:#d1fae5; color:#065f46; }
        100% { background:#000; color:#fff; }
    }
    .section-in { animation: sectionIn 0.22s ease forwards; }
    .block-in   { animation: blockIn   0.18s ease forwards; }
    .save-flash { animation: saveFlash 0.6s ease forwards; }

    /* Section collapse */
    .section-blocks-wrap {
        overflow: hidden;
        transition: max-height 0.28s ease, opacity 0.22s ease;
    }
    .section-blocks-wrap.is-collapsed {
        max-height: 0 !important;
        opacity: 0;
        pointer-events: none;
    }

    /* Layout tab pill */
    .layout-tab { cursor: pointer; transition: all 0.15s; }
    .layout-tab.active { background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.25); }

    /* Column pill */
    .col-pill { cursor: pointer; transition: all 0.15s; }
    .col-pill.active { background: rgba(255,255,255,0.08); color: #fff; border-color: rgba(255,255,255,0.25); }

    /* Block card */
    .block-card { border: 1px solid rgba(255,255,255,0.07); background: rgba(255,255,255,0.02); }
    .block-card:focus-within { border-color: rgba(255,255,255,0.14); }

    /* Confirm overlay */
    #drenla-confirm button:hover { opacity: 0.85; }

    .editor-section {
        border: 1px solid rgba(255,255,255,0.10);
        background: rgba(0,0,0,0.90);
    }
    .editor-section-head {
        width: 100%;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.5rem;
        text-align: left;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .editor-section-head:hover { background: rgba(255,255,255,0.02); }
    .editor-section-meta {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }
    .editor-section-kicker {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.25);
    }
    .editor-section-toggle {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,0.10);
        color: rgba(255,255,255,0.55);
        transition: transform 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .editor-section.is-collapsed .editor-section-toggle {
        transform: rotate(-90deg);
        color: rgba(255,255,255,0.32);
    }
    .editor-section-body {
        padding: 0 1.5rem 1.5rem;
        border-top: 1px solid rgba(255,255,255,0.08);
    }
    .editor-section.is-collapsed .editor-section-body { display: none; }
    .appearance-card {
        border: 1px solid rgba(255,255,255,0.08);
        background: rgba(255,255,255,0.02);
        padding: 1rem;
    }
    .appearance-swatch {
        width: 100%;
        height: 56px;
        border: 1px solid rgba(255,255,255,0.08);
        margin-bottom: 0.85rem;
    }
</style>

<div class="space-y-8">

    {{-- Page header --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div class="max-w-3xl">
            <p class="text-eyebrow">Commercial Documents</p>
            <h2 class="mt-3 text-headline">{{ $proposal->exists ? 'Project Brief workspace' : 'New project brief' }}</h2>
            <p class="mt-4 text-body-large">Structured authoring for Drenla client briefs. Build pages from independent content blocks — each block becomes a distinct element in the PDF.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.proposals.index') }}" class="inline-flex items-center border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">← Back</a>
            @if ($proposal->exists)
                <a href="{{ route('admin.proposals.preview', $proposal) }}" target="_blank" class="inline-flex items-center border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">Preview ↗</a>
                @if ($proposal->is_client_visible)
                    <form method="POST" action="{{ route('admin.proposals.send', $proposal) }}" class="inline" onsubmit="return confirm('Email this project brief to {{ $proposal->client?->email ?? 'the client' }}?');">
                        @csrf
                        <button type="submit" class="inline-flex items-center border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">Send to client ✉</button>
                    </form>
                @endif
                <a href="{{ route('admin.proposals.export', $proposal) }}" class="inline-flex items-center border border-white bg-white px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:bg-white/90">Export PDF ↓</a>
            @endif
        </div>
    </div>

    {{-- Jump nav --}}
    <div class="flex flex-wrap items-center gap-x-2 gap-y-2 border-b border-white/10 pb-4">
        <span class="mr-1 text-[10px] font-bold uppercase tracking-[0.28em] text-white/25">Jump to</span>
        <a href="#section-identity" class="border border-white/10 px-3 py-1.5 text-[11px] uppercase tracking-[0.18em] text-white/40 transition hover:border-white/25 hover:text-white">Identity</a>
        <a href="#section-header"   class="border border-white/10 px-3 py-1.5 text-[11px] uppercase tracking-[0.18em] text-white/40 transition hover:border-white/25 hover:text-white">Header rail</a>
        <a href="#section-intro"    class="border border-white/10 px-3 py-1.5 text-[11px] uppercase tracking-[0.18em] text-white/40 transition hover:border-white/25 hover:text-white">First page</a>
        <a href="#section-appearance" class="border border-white/10 px-3 py-1.5 text-[11px] uppercase tracking-[0.18em] text-white/40 transition hover:border-white/25 hover:text-white">Appearance</a>
        <a href="#section-pages"    class="border border-white/10 px-3 py-1.5 text-[11px] uppercase tracking-[0.18em] text-white/40 transition hover:border-white/25 hover:text-white">Pages</a>
        <span class="ml-auto hidden text-[10px] text-white/25 sm:block">
            <kbd class="border border-white/10 px-1.5 py-0.5 font-mono">Ctrl</kbd> + <kbd class="border border-white/10 px-1.5 py-0.5 font-mono">S</kbd> to save
        </span>
    </div>

    {{-- Form --}}
    <form id="proposal-form" method="POST"
        action="{{ $proposal->exists ? route('admin.proposals.update', $proposal) : route('admin.proposals.store') }}"
        class="space-y-8">
        @csrf
        @if ($proposal->exists) @method('PUT') @endif

        <div class="grid gap-8 2xl:grid-cols-[1.2fr_0.8fr]">
            <div class="space-y-8">

                {{-- ══════════════ IDENTITY ══════════════════════════════ --}}
                <section id="section-identity" class="editor-section scroll-mt-6" data-editor-section>
                    <button type="button" class="editor-section-head" data-section-toggle aria-expanded="true">
                        <div>
                            <p class="text-eyebrow">Overview</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Proposal identity</h3>
                            <p class="mt-3 text-sm text-white/45">Core commercial and client-facing metadata for this project brief.</p>
                        </div>
                        <div class="editor-section-meta">
                            <span class="editor-section-kicker">{{ $templateOptions[$currentTemplateKey]['version'] ?? 'v1' }}</span>
                            <span class="editor-section-toggle">⌄</span>
                        </div>
                    </button>
                    <div class="editor-section-body" data-section-body>
                    <div class="mt-8 grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Proposal title</label>
                            <input id="field-title" type="text" name="title" value="{{ old('title', $proposal->title) }}"
                                placeholder="e.g. Kilifi Coastal Residence Visualization"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Client</label>
                            <select name="client_id" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                <option value="">Select client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}" @selected((string)old('client_id', $proposal->client_id) === (string)$client->id)>{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Commercial status</label>
                            <select name="status" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $proposal->status ?: 'draft') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Document status</label>
                            <select name="document_status" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                @foreach ($documentStatusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('document_status', $proposal->document_status ?: 'draft') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Reference number</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number', $proposal->reference_number) }}"
                                placeholder="Auto-generated on save"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Issue date</label>
                            <input type="date" name="issue_date" value="{{ old('issue_date', optional($proposal->issue_date)->toDateString()) }}"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Linked quotation / finance document</label>
                            <select name="finance_document_id" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                <option value="">No financial document linked</option>
                                @foreach ($financeDocuments as $documentOption)
                                    <option value="{{ $documentOption->id }}" @selected((string) $linkedFinanceDocumentId === (string) $documentOption->id)>
                                        [{{ ucfirst($documentOption->type) }}] {{ $documentOption->reference_number ?: 'Draft' }} — {{ $documentOption->client?->name ?: 'No client' }} ({{ number_format((float) $documentOption->total_amount, 2) }} {{ $documentOption->currency }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($proposal->exists)
                                <div class="mt-3 flex flex-wrap gap-3 text-[11px] font-semibold uppercase tracking-[0.12em]">
                                    <a href="{{ route('admin.finance.create', ['type' => 'quotation', 'proposal_id' => $proposal->id]) }}"
                                        class="text-white/45 transition hover:text-white">+ Create quotation</a>
                                    <a href="{{ route('admin.finance.create', ['type' => 'invoice', 'proposal_id' => $proposal->id]) }}"
                                        class="text-white/45 transition hover:text-white">+ Create invoice</a>
                                </div>
                                <p class="mt-2 text-xs text-white/35">Commercial value is pulled from the linked quotation/invoice total. Linking a quotation embeds it in the exported PDF.</p>
                            @else
                                <p class="mt-2 text-xs text-white/35">You can link an existing quotation/invoice now, or create a new quotation after saving this project brief.</p>
                            @endif
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Slug</label>
                            <input id="field-slug" type="text" name="slug" value="{{ old('slug', $proposal->slug) }}"
                                placeholder="auto-generated from title"
                                class="w-full border border-white/12 bg-black px-4 py-3 font-mono text-sm text-white/70 outline-none transition focus:border-white/40">
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Project type / template</label>
                            <select id="field-template-key" name="template_key"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                @foreach ($templateOptions as $value => $tmpl)
                                    <option value="{{ $value }}" data-description="{{ $tmpl['description'] }}"
                                        @selected($currentTemplateKey === $value)>{{ $tmpl['label'] }}</option>
                                @endforeach
                            </select>
                            <p id="template-desc" class="mt-2 text-xs text-white/35"></p>
                            <button type="button" id="load-template-btn"
                                class="mt-3 hidden w-full border border-white/10 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/50 transition hover:border-white/25 hover:text-white">
                                Load <span id="load-template-label"></span> defaults →
                            </button>
                        </div>
                        <div class="space-y-4 border border-white/10 bg-white/[0.02] p-4">
                            <label class="flex cursor-pointer items-center gap-3 text-sm text-white/60">
                                <input type="checkbox" name="is_client_visible" value="1"
                                    class="h-4 w-4 border-white/15 bg-black"
                                    @checked(old('is_client_visible', $proposal->is_client_visible))>
                                Make document visible to client
                            </label>
                            @if ($proposal->access_token)
                                <div class="text-xs text-white/40">
                                    <p class="mb-1 uppercase tracking-[0.18em]">Access token</p>
                                    <div class="flex items-center gap-2">
                                        <code class="flex-1 truncate font-mono text-white/60">{{ $proposal->access_token }}</code>
                                        <button type="button" data-copy="{{ $proposal->access_token }}"
                                            class="token-copy-btn border border-white/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-white/40 transition hover:text-white">Copy</button>
                                    </div>
                                </div>
                            @endif
                            @if ($proposal->last_exported_at)
                                <p class="text-xs text-white/35">Last exported {{ $proposal->last_exported_at->format('d M Y, H:i') }}</p>
                            @endif
                        </div>
                    </div>
                    </div>
                </section>

                {{-- ══════════════ HEADER RAIL ═══════════════════════════ --}}
                <section id="section-header" class="editor-section scroll-mt-6 is-collapsed" data-editor-section>
                    <button type="button" class="editor-section-head" data-section-toggle aria-expanded="false">
                        <div>
                            <p class="text-eyebrow">Document Setup</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Header metadata rail</h3>
                            <p class="mt-3 text-sm text-white/45">Labels and scope lines that feed the cover header band.</p>
                        </div>
                        <div class="editor-section-meta">
                            <span class="editor-section-kicker">Cover labels</span>
                            <span class="editor-section-toggle">⌄</span>
                        </div>
                    </button>
                    <div class="editor-section-body" data-section-body>
                    <div class="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-5">
                        @foreach (['document_label' => 'Document label', 'client_label' => 'Client label', 'scope_label' => 'Scope label', 'reference_label' => 'Reference label', 'date_label' => 'Date label'] as $field => $label)
                            <div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">{{ $label }}</label>
                                <input type="text" name="document[header][{{ $field }}]"
                                    value="{{ old("document.header.$field", $document['header'][$field]) }}"
                                    class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6 grid gap-6 md:grid-cols-[1fr_1.2fr]">
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Client display name</label>
                            <input type="text" name="document[header][client_name]"
                                value="{{ old('document.header.client_name', $document['header']['client_name']) }}"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Scope lines</label>
                                <span id="scope-counter" class="text-[11px] text-white/25">0 lines</span>
                            </div>
                            <textarea id="scope-textarea" name="document[header][scope]" rows="4"
                                class="auto-resize w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">{{ old('document.header.scope', $document['header']['scope']) }}</textarea>
                            <p class="mt-1 text-xs text-white/30">Each line = one row in the PDF header band. Keep to 3–5 lines.</p>
                        </div>
                    </div>
                    </div>
                </section>

                {{-- ══════════════ INTRO / FIRST PAGE ═══════════════════ --}}
                <section id="section-intro" class="editor-section scroll-mt-6 is-collapsed" data-editor-section>
                    <button type="button" class="editor-section-head" data-section-toggle aria-expanded="false">
                        <div>
                            <p class="text-eyebrow">First Page</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Hero and opening spread</h3>
                            <p class="mt-3 text-sm text-white/45">Cover title, subtitle, and the opening narrative spread.</p>
                        </div>
                        <div class="editor-section-meta">
                            <span class="editor-section-kicker">Opening page</span>
                            <span class="editor-section-toggle">⌄</span>
                        </div>
                    </button>
                    <div class="editor-section-body" data-section-body>
                    <div class="mt-8 space-y-6">
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Hero title</label>
                                <span id="hero-counter" class="text-[11px] text-white/25">0 chars</span>
                            </div>
                            <textarea id="hero-title" name="document[hero][title]" rows="3"
                                class="auto-resize w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">{{ old('document.hero.title', $document['hero']['title']) }}</textarea>
                            <p class="mt-1 text-xs text-white/30">Use manual line breaks to control the large title band. 2–3 lines ideal.</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Hero subtitle</label>
                            <input type="text" name="document[hero][subtitle]"
                                value="{{ old('document.hero.subtitle', $document['hero']['subtitle']) }}"
                                placeholder="Optional subtitle line below the title"
                                class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                        </div>
                    </div>

                    <div class="mt-8 border-t border-white/10 pt-6">
                        <p class="mb-4 text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Project Description spread</p>
                        <div class="grid gap-6 xl:grid-cols-[0.35fr_0.65fr]">
                            <div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Section strip label</label>
                                <input type="text" name="document[intro][section_label]"
                                    value="{{ old('document.intro.section_label', $document['intro']['section_label']) }}"
                                    class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Left heading</label>
                                    <input type="text" name="document[intro][left_heading]"
                                        value="{{ old('document.intro.left_heading', $document['intro']['left_heading']) }}"
                                        class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                </div>
                                <div>
                                    <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Right heading</label>
                                    <input type="text" name="document[intro][right_heading]"
                                        value="{{ old('document.intro.right_heading', $document['intro']['right_heading']) }}"
                                        class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Left body — Brief</label>
                                <textarea name="document[intro][left_body]" rows="6"
                                    class="auto-resize w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">{{ old('document.intro.left_body', $document['intro']['left_body']) }}</textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Right body — Key Objective</label>
                                <textarea name="document[intro][right_body]" rows="6"
                                    class="auto-resize w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">{{ old('document.intro.right_body', $document['intro']['right_body']) }}</textarea>
                            </div>
                        </div>
                    </div>
                    </div>
                </section>

                <section id="section-appearance" class="editor-section scroll-mt-6" data-editor-section>
                    <button type="button" class="editor-section-head" data-section-toggle aria-expanded="true">
                        <div>
                            <p class="text-eyebrow">Customization</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Appearance and tone</h3>
                            <p class="mt-3 text-sm text-white/45">Small, controlled style presets so clients can feel variety without turning the editor into another long form.</p>
                        </div>
                        <div class="editor-section-meta">
                            <span class="editor-section-kicker">6 controls</span>
                            <span class="editor-section-toggle">⌄</span>
                        </div>
                    </button>
                    <div class="editor-section-body" data-section-body>
                        <div class="mt-8 grid gap-6 xl:grid-cols-2">
                            <div class="appearance-card">
                                <div class="appearance-swatch" style="background:linear-gradient(135deg,#130e24,#2a1d45);"></div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Cover tone</label>
                                <select name="document[appearance][cover_tone]" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                    <option value="plum" @selected(($appearance['cover_tone'] ?? 'plum') === 'plum')>Midnight Plum</option>
                                    <option value="graphite" @selected(($appearance['cover_tone'] ?? '') === 'graphite')>Graphite</option>
                                    <option value="forest" @selected(($appearance['cover_tone'] ?? '') === 'forest')>Deep Forest</option>
                                </select>
                                <p class="mt-2 text-xs text-white/30">Changes the dark cover/header band mood.</p>
                            </div>
                            <div class="appearance-card">
                                <div class="appearance-swatch" style="background:repeating-linear-gradient(90deg,#dfe1e4 0 40px,#fff 40px 44px);"></div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Section banner style</label>
                                <select name="document[appearance][section_banner_style]" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                    <option value="plain" @selected(($appearance['section_banner_style'] ?? 'plain') === 'plain')>Plain bar</option>
                                    <option value="clipped" @selected(($appearance['section_banner_style'] ?? '') === 'clipped')>Clipped corner</option>
                                </select>
                                <p class="mt-2 text-xs text-white/30">The grey band above each section heading — plain (Abidjan-family) or a diagonal-cut corner (Kilifi-family).</p>
                            </div>
                            <div class="appearance-card">
                                <div class="appearance-swatch" style="background:#fff;display:flex;align-items:center;justify-content:flex-end;padding:6px;">
                                    <span style="font-size:8px;letter-spacing:0.1em;color:#999;">Unlocking Great Ideas</span>
                                </div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Masthead tagline</label>
                                <select name="document[appearance][masthead_tagline]" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                    <option value="0" @selected(! ($appearance['masthead_tagline'] ?? false))>Off</option>
                                    <option value="1" @selected((bool) ($appearance['masthead_tagline'] ?? false))>"Unlocking Great Ideas"</option>
                                </select>
                                <p class="mt-2 text-xs text-white/30">Shown faintly beside the watermark logo on every page after the cover.</p>
                            </div>
                            <div class="appearance-card">
                                <div class="appearance-swatch" style="background:linear-gradient(180deg,#f7f7f7,#ece8df);"></div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Hero surface</label>
                                <select name="document[appearance][hero_surface]" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                    <option value="mist" @selected(($appearance['hero_surface'] ?? 'mist') === 'mist')>Soft Mist</option>
                                    <option value="white" @selected(($appearance['hero_surface'] ?? '') === 'white')>Clean White</option>
                                    <option value="warm" @selected(($appearance['hero_surface'] ?? '') === 'warm')>Warm Paper</option>
                                </select>
                                <p class="mt-2 text-xs text-white/30">Controls the large title band background.</p>
                            </div>
                            <div class="appearance-card">
                                <div class="appearance-swatch" style="background:linear-gradient(90deg,#dfe1e4,#e6ddcf);"></div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Section strip tone</label>
                                <select name="document[appearance][section_strip]" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                    <option value="stone" @selected(($appearance['section_strip'] ?? 'stone') === 'stone')>Stone</option>
                                    <option value="sand" @selected(($appearance['section_strip'] ?? '') === 'sand')>Sand</option>
                                    <option value="slate" @selected(($appearance['section_strip'] ?? '') === 'slate')>Slate</option>
                                </select>
                                <p class="mt-2 text-xs text-white/30">Adjusts the section label band tone across the document.</p>
                            </div>
                            <div class="appearance-card">
                                <div class="appearance-swatch" style="background:linear-gradient(180deg,#161616,#0d0d0d);"></div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Content density</label>
                                <select name="document[appearance][content_density]" class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                                    <option value="comfortable" @selected(($appearance['content_density'] ?? 'comfortable') === 'comfortable')>Comfortable spacing</option>
                                    <option value="compact" @selected(($appearance['content_density'] ?? '') === 'compact')>Compact spacing</option>
                                </select>
                                <p class="mt-2 text-xs text-white/30">Useful when the client wants a tighter document without rewriting content.</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ══════════════ PAGES (EXTENDED SECTIONS) ════════════ --}}
                <section id="section-pages" class="editor-section scroll-mt-6" data-editor-section>
                    <button type="button" class="editor-section-head" data-section-toggle aria-expanded="true">
                        <div>
                            <p class="text-eyebrow">Document Pages</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Content pages</h3>
                            <p class="mt-3 text-sm text-white/45">Each page becomes a PDF section. Collapse pages you are not actively editing.</p>
                        </div>
                        <div class="editor-section-meta">
                            <span class="editor-section-kicker">Main builder</span>
                            <span class="editor-section-toggle">⌄</span>
                        </div>
                    </button>
                    <div class="editor-section-body" data-section-body>
                    <div class="mt-8 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div class="max-w-2xl text-sm text-white/45">
                            Add as many content blocks per page as needed. Use page collapse for long proposals so the editor stays manageable.
                        </div>
                        <button type="button" id="add-page-btn"
                            class="inline-flex items-center gap-2 border border-white/10 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70 transition hover:border-white/25 hover:text-white">
                            + Add page
                        </button>
                    </div>

                    {{-- Pages list --}}
                    <div id="pages-root" class="mt-8 space-y-6">
                        @foreach ($sections as $si => $section)
                            @php $blocks = $section['blocks'] ?? []; $layout = $section['layout'] ?? 'single-column'; @endphp
                            <div class="border border-white/10 section-in" data-page>

                                {{-- Page header bar --}}
                                <div class="flex items-center gap-3 border-b border-white/10 bg-white/[0.02] px-4 py-3">
                                    <span class="drag-handle cursor-grab select-none text-lg text-white/20 hover:text-white/50">⠿</span>
                                    <span class="page-num text-[10px] font-bold uppercase tracking-[0.28em] text-white/25">Page {{ $si + 1 }}</span>
                                    <div class="flex flex-1 gap-3 overflow-hidden">
                                        <input type="text" data-role="page-label" name="document[sections][{{ $si }}][label]"
                                            value="{{ $section['label'] }}" placeholder="Section label (e.g. Section A)"
                                            class="w-32 flex-shrink-0 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none transition focus:border-white/25 focus:text-white">
                                        <input type="text" data-role="page-title" name="document[sections][{{ $si }}][page_title]"
                                            value="{{ $section['page_title'] }}" placeholder="Page title"
                                            class="min-w-0 flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none transition focus:border-white/25 focus:text-white">
                                    </div>
                                    {{-- Layout selector --}}
                                    <div class="flex gap-1" data-layout-tabs>
                                        <button type="button" data-layout-val="single-column"
                                            class="layout-tab border border-white/10 px-2 py-1 text-[10px] uppercase tracking-[0.12em] text-white/35 {{ $layout === 'single-column' ? 'active' : '' }}">
                                            Single
                                        </button>
                                        <button type="button" data-layout-val="two-column"
                                            class="layout-tab border border-white/10 px-2 py-1 text-[10px] uppercase tracking-[0.12em] text-white/35 {{ $layout === 'two-column' ? 'active' : '' }}">
                                            Two col
                                        </button>
                                    </div>
                                    <input type="hidden" name="document[sections][{{ $si }}][layout]" value="{{ $layout }}" data-layout-input>
                                    <button type="button" data-collapse-page
                                        class="border border-white/10 px-2 py-1 text-[11px] text-white/35 hover:text-white transition">−</button>
                                    <button type="button" data-remove-page
                                        class="text-white/25 transition hover:text-red-400 text-lg leading-none">×</button>
                                </div>

                                {{-- Blocks area --}}
                                <div class="section-blocks-wrap" data-blocks-wrap>
                                    <div class="p-4 space-y-3" data-blocks-list>
                                        @foreach ($blocks as $bi => $block)
                                            @php $bType = $block['type'] ?? 'narrative'; @endphp
                                            <div class="block-card block-in" data-block data-block-type="{{ $bType }}">
                                                {{-- Block meta row --}}
                                                <div class="flex items-center gap-2 border-b border-white/8 px-3 py-2">
                                                    <select name="document[sections][{{ $si }}][blocks][{{ $bi }}][type]" data-field="type" data-type-select
                                                        class="w-[104px] flex-shrink-0 border border-white/10 bg-black px-1 py-1 text-[10px] uppercase tracking-[0.08em] text-white/50 outline-none focus:border-white/25">
                                                        @foreach (['narrative' => 'Narrative', 'bullet_list' => 'Bullet list', 'multi_column_list' => 'Multi-column', 'stage_grid' => 'Stage grid', 'comment_lines' => 'Comment lines', 'signature_block' => 'Signature'] as $val => $lbl)
                                                            <option value="{{ $val }}" @selected($bType === $val)>{{ $lbl }}</option>
                                                        @endforeach
                                                    </select>
                                                    <div data-type-group="narrative,bullet_list,multi_column_list" class="{{ in_array($bType, ['narrative', 'bullet_list', 'multi_column_list']) ? '' : 'hidden' }}">
                                                        <input type="text" name="document[sections][{{ $si }}][blocks][{{ $bi }}][title]" data-field="title"
                                                            value="{{ $block['title'] ?? '' }}" placeholder="Block title (optional)"
                                                            class="w-48 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25 focus:text-white">
                                                    </div>
                                                    <div data-type-group="narrative" class="flex items-center gap-2 {{ $bType === 'narrative' ? '' : 'hidden' }}">
                                                        <input type="text" name="document[sections][{{ $si }}][blocks][{{ $bi }}][number]" data-field="number"
                                                            value="{{ $block['number'] ?? '' }}" placeholder="#"
                                                            class="w-10 border border-white/10 bg-transparent px-2 py-1 text-center text-[11px] text-white/50 outline-none focus:border-white/25">
                                                        <input type="text" name="document[sections][{{ $si }}][blocks][{{ $bi }}][subtitle]" data-field="subtitle"
                                                            value="{{ $block['subtitle'] ?? '' }}" placeholder="Duration / subtitle"
                                                            class="w-28 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/40 outline-none focus:border-white/25">
                                                    </div>
                                                    <div class="flex-1"></div>
                                                    {{-- Column pills (narrative + bullet_list only, shown for two-column pages) --}}
                                                    <div data-type-group="narrative,bullet_list" class="col-pills flex gap-1 {{ ($layout === 'single-column' || ! in_array($bType, ['narrative', 'bullet_list'])) ? 'hidden' : '' }}" data-col-pills>
                                                        @foreach (['left' => 'L', 'right' => 'R', 'full' => '↔'] as $col => $lbl)
                                                            <button type="button" data-col="{{ $col }}"
                                                                class="col-pill border border-white/10 px-2 py-0.5 text-[10px] text-white/30 {{ ($block['column'] ?? 'full') === $col ? 'active' : '' }}">
                                                                {{ $lbl }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                    <input type="hidden" name="document[sections][{{ $si }}][blocks][{{ $bi }}][column]" data-field="column"
                                                        value="{{ $block['column'] ?? 'full' }}" data-col-input>
                                                    <button type="button" data-remove-block class="text-white/20 hover:text-red-400 transition text-base leading-none ml-1">×</button>
                                                </div>

                                                {{-- Narrative body --}}
                                                <div data-type-group="narrative" class="px-3 py-2 {{ $bType === 'narrative' ? '' : 'hidden' }}">
                                                    <textarea name="document[sections][{{ $si }}][blocks][{{ $bi }}][body]" data-field="body"
                                                        placeholder="Block content — use • for bullets, - for dash items, plain text for paragraphs"
                                                        class="auto-resize w-full bg-transparent text-sm text-white/70 outline-none placeholder:text-white/20">{{ $block['body'] ?? '' }}</textarea>
                                                </div>

                                                {{-- Bullet list --}}
                                                <div data-type-group="bullet_list" class="px-3 py-2 space-y-2 {{ $bType === 'bullet_list' ? '' : 'hidden' }}">
                                                    <div class="flex gap-2">
                                                        <input type="text" name="document[sections][{{ $si }}][blocks][{{ $bi }}][bl_intro]" data-field="bl_intro"
                                                            value="{{ $bType === 'bullet_list' ? ($block['intro'] ?? '') : '' }}" placeholder="Intro line (optional)"
                                                            class="flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                                        <select name="document[sections][{{ $si }}][blocks][{{ $bi }}][bl_bullet_style]" data-field="bl_bullet_style"
                                                            class="border border-white/10 bg-black px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                                            <option value="square" @selected(($bType === 'bullet_list' ? ($block['bullet_style'] ?? 'square') : 'square') === 'square')>Square •</option>
                                                            <option value="dash" @selected($bType === 'bullet_list' && ($block['bullet_style'] ?? '') === 'dash')>Dash –</option>
                                                        </select>
                                                    </div>
                                                    <textarea name="document[sections][{{ $si }}][blocks][{{ $bi }}][bl_items_text]" data-field="bl_items_text"
                                                        placeholder="One item per line" rows="4"
                                                        class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20">{{ $bType === 'bullet_list' ? $serializeItems($block['items'] ?? []) : '' }}</textarea>
                                                </div>

                                                {{-- Multi-column list --}}
                                                <div data-type-group="multi_column_list" class="px-3 py-2 {{ $bType === 'multi_column_list' ? '' : 'hidden' }}">
                                                    <textarea name="document[sections][{{ $si }}][blocks][{{ $bi }}][mc_columns_text]" data-field="mc_columns_text"
                                                        placeholder="One column per line: Heading: item, item, item" rows="4"
                                                        class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20">{{ $bType === 'multi_column_list' ? $serializeColumns($block['columns'] ?? []) : '' }}</textarea>
                                                </div>

                                                {{-- Stage grid --}}
                                                <div data-type-group="stage_grid" class="px-3 py-2 space-y-2 {{ $bType === 'stage_grid' ? '' : 'hidden' }}">
                                                    <select name="document[sections][{{ $si }}][blocks][{{ $bi }}][sg_columns]" data-field="sg_columns"
                                                        class="border border-white/10 bg-black px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                                        <option value="2" @selected(($bType === 'stage_grid' ? ($block['columns'] ?? 2) : 2) == 2)>2 columns</option>
                                                        <option value="1" @selected($bType === 'stage_grid' && ($block['columns'] ?? 2) == 1)>1 column</option>
                                                    </select>
                                                    <textarea name="document[sections][{{ $si }}][blocks][{{ $bi }}][sg_stages_text]" data-field="sg_stages_text"
                                                        placeholder="Stage 1 | 2 Weeks&#10;- Review project brief&#10;- Concept scope&#10;&#10;Stage 2 | 3 Weeks&#10;- Full 3D massing" rows="6"
                                                        class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20">{{ $bType === 'stage_grid' ? $serializeStages($block['stages'] ?? []) : '' }}</textarea>
                                                </div>

                                                {{-- Comment lines --}}
                                                <div data-type-group="comment_lines" class="px-3 py-2 flex gap-2 {{ $bType === 'comment_lines' ? '' : 'hidden' }}">
                                                    <input type="text" name="document[sections][{{ $si }}][blocks][{{ $bi }}][cl_label]" data-field="cl_label"
                                                        value="{{ $bType === 'comment_lines' ? ($block['label'] ?? '') : '' }}" placeholder="Label (e.g. Client Additional Comments)"
                                                        class="flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                                    <input type="number" name="document[sections][{{ $si }}][blocks][{{ $bi }}][cl_line_count]" data-field="cl_line_count"
                                                        value="{{ $bType === 'comment_lines' ? ($block['line_count'] ?? 4) : 4 }}" min="1" max="12" placeholder="Lines"
                                                        class="w-20 border border-white/10 bg-transparent px-2 py-1 text-center text-[11px] text-white/60 outline-none focus:border-white/25">
                                                </div>

                                                {{-- Signature block --}}
                                                <div data-type-group="signature_block" class="px-3 py-2 space-y-2 {{ $bType === 'signature_block' ? '' : 'hidden' }}">
                                                    <div class="flex gap-2">
                                                        <input type="text" name="document[sections][{{ $si }}][blocks][{{ $bi }}][sb_intro]" data-field="sb_intro"
                                                            value="{{ $bType === 'signature_block' ? ($block['intro'] ?? '') : '' }}" placeholder="Callout text (optional)"
                                                            class="flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                                        <input type="text" name="document[sections][{{ $si }}][blocks][{{ $bi }}][sb_date_label]" data-field="sb_date_label"
                                                            value="{{ $bType === 'signature_block' ? ($block['date_label'] ?? '') : '' }}" placeholder="Date (optional)"
                                                            class="w-32 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                                    </div>
                                                    <textarea name="document[sections][{{ $si }}][blocks][{{ $bi }}][sb_signers_text]" data-field="sb_signers_text"
                                                        placeholder="Role | Name | Subtitle | Phone — one signer per line. Leave Name/Subtitle/Phone blank for a compact &quot;Sign Here&quot; line." rows="3"
                                                        class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20">{{ $bType === 'signature_block' ? $serializeSigners($block['signers'] ?? []) : '' }}</textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    {{-- Add block --}}
                                    <div class="border-t border-white/8 px-4 py-2">
                                        <button type="button" data-add-block
                                            class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/30 transition hover:text-white">
                                            + Add block
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Page template --}}
                    <template id="page-template">
                        <div class="border border-white/10" data-page>
                            <div class="flex items-center gap-3 border-b border-white/10 bg-white/[0.02] px-4 py-3">
                                <span class="drag-handle cursor-grab select-none text-lg text-white/20 hover:text-white/50">⠿</span>
                                <span class="page-num text-[10px] font-bold uppercase tracking-[0.28em] text-white/25">Page</span>
                                <div class="flex flex-1 gap-3 overflow-hidden">
                                    <input type="text" data-role="page-label" placeholder="Section label (e.g. Section A)"
                                        class="w-32 flex-shrink-0 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none transition focus:border-white/25 focus:text-white">
                                    <input type="text" data-role="page-title" placeholder="Page title"
                                        class="min-w-0 flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none transition focus:border-white/25 focus:text-white">
                                </div>
                                <div class="flex gap-1" data-layout-tabs>
                                    <button type="button" data-layout-val="single-column" class="layout-tab active border border-white/10 px-2 py-1 text-[10px] uppercase tracking-[0.12em] text-white/35">Single</button>
                                    <button type="button" data-layout-val="two-column"   class="layout-tab border border-white/10 px-2 py-1 text-[10px] uppercase tracking-[0.12em] text-white/35">Two col</button>
                                </div>
                                <input type="hidden" value="single-column" data-layout-input>
                                <button type="button" data-collapse-page class="border border-white/10 px-2 py-1 text-[11px] text-white/35 hover:text-white transition">−</button>
                                <button type="button" data-remove-page class="text-white/25 transition hover:text-red-400 text-lg leading-none">×</button>
                            </div>
                            <div class="section-blocks-wrap" data-blocks-wrap>
                                <div class="p-4 space-y-3" data-blocks-list></div>
                                <div class="border-t border-white/8 px-4 py-2">
                                    <button type="button" data-add-block class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/30 transition hover:text-white">+ Add block</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Block template --}}
                    <template id="block-template">
                        <div class="block-card block-in" data-block data-block-type="narrative">
                            <div class="flex items-center gap-2 border-b border-white/8 px-3 py-2">
                                <select data-field="type" data-type-select
                                    class="w-[104px] flex-shrink-0 border border-white/10 bg-black px-1 py-1 text-[10px] uppercase tracking-[0.08em] text-white/50 outline-none focus:border-white/25">
                                    <option value="narrative" selected>Narrative</option>
                                    <option value="bullet_list">Bullet list</option>
                                    <option value="multi_column_list">Multi-column</option>
                                    <option value="stage_grid">Stage grid</option>
                                    <option value="comment_lines">Comment lines</option>
                                    <option value="signature_block">Signature</option>
                                </select>
                                <div data-type-group="narrative,bullet_list,multi_column_list">
                                    <input type="text" placeholder="Block title (optional)" data-field="title"
                                        class="w-48 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25 focus:text-white">
                                </div>
                                <div data-type-group="narrative" class="flex items-center gap-2">
                                    <input type="text" placeholder="#" data-field="number"
                                        class="w-10 border border-white/10 bg-transparent px-2 py-1 text-center text-[11px] text-white/50 outline-none focus:border-white/25">
                                    <input type="text" placeholder="Duration / subtitle" data-field="subtitle"
                                        class="w-28 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/40 outline-none focus:border-white/25">
                                </div>
                                <div class="flex-1"></div>
                                <div data-type-group="narrative,bullet_list" class="col-pills flex gap-1 hidden" data-col-pills>
                                    <button type="button" data-col="left"  class="col-pill border border-white/10 px-2 py-0.5 text-[10px] text-white/30">L</button>
                                    <button type="button" data-col="right" class="col-pill border border-white/10 px-2 py-0.5 text-[10px] text-white/30">R</button>
                                    <button type="button" data-col="full"  class="col-pill active border border-white/10 px-2 py-0.5 text-[10px] text-white/30">↔</button>
                                </div>
                                <input type="hidden" value="full" data-col-input data-field="column">
                                <button type="button" data-remove-block class="text-white/20 hover:text-red-400 transition text-base leading-none ml-1">×</button>
                            </div>

                            <div data-type-group="narrative" class="px-3 py-2">
                                <textarea placeholder="Block content — use • for bullets, - for dash items, plain text for paragraphs" data-field="body"
                                    class="auto-resize w-full bg-transparent text-sm text-white/70 outline-none placeholder:text-white/20"></textarea>
                            </div>

                            <div data-type-group="bullet_list" class="px-3 py-2 space-y-2 hidden">
                                <div class="flex gap-2">
                                    <input type="text" placeholder="Intro line (optional)" data-field="bl_intro"
                                        class="flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                    <select data-field="bl_bullet_style" class="border border-white/10 bg-black px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                        <option value="square" selected>Square •</option>
                                        <option value="dash">Dash –</option>
                                    </select>
                                </div>
                                <textarea placeholder="One item per line" data-field="bl_items_text" rows="4"
                                    class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20"></textarea>
                            </div>

                            <div data-type-group="multi_column_list" class="px-3 py-2 hidden">
                                <textarea placeholder="One column per line: Heading: item, item, item" data-field="mc_columns_text" rows="4"
                                    class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20"></textarea>
                            </div>

                            <div data-type-group="stage_grid" class="px-3 py-2 space-y-2 hidden">
                                <select data-field="sg_columns" class="border border-white/10 bg-black px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                    <option value="2" selected>2 columns</option>
                                    <option value="1">1 column</option>
                                </select>
                                <textarea placeholder="Stage 1 | 2 Weeks&#10;- Review project brief&#10;- Concept scope&#10;&#10;Stage 2 | 3 Weeks&#10;- Full 3D massing" data-field="sg_stages_text" rows="6"
                                    class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20"></textarea>
                            </div>

                            <div data-type-group="comment_lines" class="px-3 py-2 flex gap-2 hidden">
                                <input type="text" placeholder="Label (e.g. Client Additional Comments)" data-field="cl_label"
                                    class="flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                <input type="number" value="4" min="1" max="12" placeholder="Lines" data-field="cl_line_count"
                                    class="w-20 border border-white/10 bg-transparent px-2 py-1 text-center text-[11px] text-white/60 outline-none focus:border-white/25">
                            </div>

                            <div data-type-group="signature_block" class="px-3 py-2 space-y-2 hidden">
                                <div class="flex gap-2">
                                    <input type="text" placeholder="Callout text (optional)" data-field="sb_intro"
                                        class="flex-1 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                    <input type="text" placeholder="Date (optional)" data-field="sb_date_label"
                                        class="w-32 border border-white/10 bg-transparent px-2 py-1 text-[11px] text-white/60 outline-none focus:border-white/25">
                                </div>
                                <textarea placeholder="Role | Name | Subtitle | Phone — one signer per line. Leave Name/Subtitle/Phone blank for a compact &quot;Sign Here&quot; line." data-field="sb_signers_text" rows="3"
                                    class="auto-resize w-full border border-white/10 bg-transparent px-2 py-1.5 text-sm text-white/70 outline-none placeholder:text-white/20"></textarea>
                            </div>
                        </div>
                    </template>
                    </div>
                </section>

                {{-- ══════════════ ACCEPTANCE FORM (document-level, optional) ═══ --}}
                <section id="section-acceptance" class="editor-section scroll-mt-6" data-editor-section>
                    <button type="button" class="editor-section-head" data-section-toggle aria-expanded="true">
                        <div>
                            <p class="text-eyebrow">Signature Page</p>
                            <h3 class="mt-3 text-2xl font-light tracking-[-0.03em] text-white">Acceptance form</h3>
                            <p class="mt-3 text-sm text-white/45">An optional standalone signature page appended after the quotation, if one is linked.</p>
                        </div>
                        <div class="editor-section-meta">
                            <span class="editor-section-kicker">{{ ($acceptance['enabled'] ?? false) ? 'Enabled' : 'Off' }}</span>
                            <span class="editor-section-toggle">⌄</span>
                        </div>
                    </button>
                    <div class="editor-section-body" data-section-body>
                        <div class="mt-8 space-y-5 max-w-2xl">
                            <label class="flex items-center gap-3 text-sm text-white/70 cursor-pointer select-none">
                                <input type="checkbox" name="document[acceptance][enabled]" value="1" @checked($acceptance['enabled'] ?? false)
                                    class="h-4 w-4 border-white/20 bg-black accent-white">
                                Include an acceptance/signature page in this document
                            </label>
                            <div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Intro text</label>
                                <input type="text" name="document[acceptance][intro_text]" value="{{ $acceptance['intro_text'] ?? '' }}"
                                    placeholder="Please read and understand all terms listed before signing below"
                                    class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                            </div>
                            <div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Date label</label>
                                <input type="text" name="document[acceptance][date_label]" value="{{ $acceptance['date_label'] ?? '' }}"
                                    placeholder="e.g. July, 2026"
                                    class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">
                            </div>
                            <div>
                                <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.22em] text-white/35">Signers</label>
                                <textarea name="document[acceptance][signers_text]" rows="3"
                                    placeholder="Role | Name | Subtitle | Phone — one signer per line, e.g.&#10;Client Contact | Arch. Victor | [Design Infinity Architects] | +254 722 172 037&#10;Drenla Ventures | Mr Mbuya Adrian | [Head of Design] | +254 759 947 183"
                                    class="w-full border border-white/12 bg-black px-4 py-3 text-white outline-none transition focus:border-white/40">{{ $serializeSigners($acceptance['signers'] ?? []) }}</textarea>
                            </div>
                        </div>
                    </div>
                </section>

            </div>{{-- /left --}}

            {{-- Sidebar --}}
            <aside class="space-y-8">
                <section class="border border-white/10 bg-black/90 p-6">
                    <p class="text-eyebrow">PDF Output Notes</p>
                    <h3 class="mt-3 text-xl font-light tracking-[-0.03em] text-white">How blocks render</h3>
                    <div class="mt-5 space-y-4 text-sm leading-7 text-white/50">
                        <p>Each <strong class="text-white/70">page</strong> maps to one PDF section with a grey header band.</p>
                        <p>Each <strong class="text-white/70">block</strong> within a page renders as a content unit. A # number makes it a numbered heading. A title without a number is a sub-heading.</p>
                        <p>For <strong class="text-white/70">two-column pages</strong>, assign each block to Left, Right, or Full-width using the L / R / ↔ pills.</p>
                        <p>Lines starting with <code class="text-white/60">•</code> or <code class="text-white/60">-</code> become styled bullet rows in the PDF.</p>
                    </div>
                </section>

                <div class="sticky top-6 border border-white/10 bg-white px-5 py-5 text-black">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-black/55">Actions</p>
                        <span id="unsaved-badge" class="items-center gap-1.5 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-600">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Unsaved
                        </span>
                    </div>
                    <div class="mt-4 flex flex-col gap-3">
                        <button id="save-btn" class="inline-flex items-center justify-center border border-black bg-black px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-black/90">
                            Save project brief
                        </button>
                        @if ($proposal->exists)
                            <a href="{{ route('admin.proposals.preview', $proposal) }}" target="_blank"
                                class="inline-flex items-center justify-center border border-black/15 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:border-black/35">
                                Open preview ↗
                            </a>
                            <a href="{{ route('admin.proposals.export', $proposal) }}"
                                class="inline-flex items-center justify-center border border-black/15 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:border-black/35">
                                Download PDF ↓
                            </a>
                            @if ($proposal->is_client_visible)
                                <form method="POST" action="{{ route('admin.proposals.send', $proposal) }}" onsubmit="return confirm('Email this project brief to {{ $proposal->client?->email ?? 'the client' }}?');">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center border border-black/15 px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:border-black/35">
                                        Send to client ✉
                                    </button>
                                </form>
                            @endif
                        @else
                            <p class="text-xs leading-6 text-black/50">Save first to unlock preview and export.</p>
                        @endif
                    </div>
                    @if ($proposal->exists && $proposal->updated_at)
                        <p class="mt-4 border-t border-black/10 pt-3 text-[10px] text-black/30">Saved {{ $proposal->updated_at->diffForHumans() }}</p>
                    @endif
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form       = document.getElementById('proposal-form');
    const pagesRoot  = document.getElementById('pages-root');
    const addPageBtn = document.getElementById('add-page-btn');
    const pageTmpl   = document.getElementById('page-template');
    const blockTmpl  = document.getElementById('block-template');

    const initEditorSections = () => {
        document.querySelectorAll('[data-editor-section]').forEach(section => {
            if (section.dataset.sectionBound) return;
            section.dataset.sectionBound = '1';

            const toggle = section.querySelector('[data-section-toggle]');
            const body = section.querySelector('[data-section-body]');
            if (!toggle || !body) return;

            const sync = (collapsed) => {
                section.classList.toggle('is-collapsed', collapsed);
                toggle.setAttribute('aria-expanded', String(!collapsed));
            };

            sync(section.classList.contains('is-collapsed'));

            toggle.addEventListener('click', () => {
                sync(!section.classList.contains('is-collapsed'));
            });
        });
    };

    // ── Unsaved changes ───────────────────────────────────────────────── //
    const badge   = document.getElementById('unsaved-badge');
    const saveBtn = document.getElementById('save-btn');
    let isDirty   = false;
    let saving    = false;
    const markDirty = () => { isDirty = true; badge?.classList.add('visible'); };
    const markClean = () => { isDirty = false; badge?.classList.remove('visible'); };
    form?.addEventListener('input',  () => markDirty(), { passive: true });
    form?.addEventListener('change', () => markDirty(), { passive: true });
    window.addEventListener('beforeunload', e => { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });
    form?.addEventListener('submit', () => { saving = true; markClean(); });

    // ── Keyboard save ─────────────────────────────────────────────────── //
    document.addEventListener('keydown', e => {
        const hot = (e.metaKey || e.ctrlKey) && e.key === 's';
        if (!hot) return;
        e.preventDefault();
        if (form) { saving = true; markClean(); saveBtn?.classList.add('save-flash'); setTimeout(() => saveBtn?.classList.remove('save-flash'), 700); form.submit(); }
    });

    // ── Auto-resize textareas ─────────────────────────────────────────── //
    const autoResize = (el) => { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; };
    const initAutoResize = (root = document) => {
        root.querySelectorAll('textarea.auto-resize').forEach(ta => {
            ta.addEventListener('input', () => autoResize(ta));
            autoResize(ta);
        });
    };
    initAutoResize();

    // ── Scope counter ─────────────────────────────────────────────────── //
    const scopeTA = document.getElementById('scope-textarea');
    const scopeCounter = document.getElementById('scope-counter');
    const heroTA  = document.getElementById('hero-title');
    const heroCounter = document.getElementById('hero-counter');
    const updateScopeCounter = () => {
        if (!scopeTA || !scopeCounter) return;
        const lines = scopeTA.value.split('\n').length;
        scopeCounter.textContent = `${lines} line${lines === 1 ? '' : 's'}`;
        scopeCounter.style.color = lines > 5 ? 'rgba(255,200,100,0.8)' : '';
    };
    const updateHeroCounter = () => {
        if (!heroTA || !heroCounter) return;
        const len = heroTA.value.length;
        heroCounter.textContent = `${len} chars`;
        heroCounter.style.color = len > 120 ? 'rgba(255,200,100,0.8)' : '';
    };
    scopeTA?.addEventListener('input', updateScopeCounter); updateScopeCounter();
    heroTA?.addEventListener('input',  updateHeroCounter);  updateHeroCounter();

    // ── Auto-slug ─────────────────────────────────────────────────────── //
    const titleField = document.getElementById('field-title');
    const slugField  = document.getElementById('field-slug');
    const slugify = s => s.toLowerCase().replace(/[^\w\s-]/g,'').replace(/[\s_]+/g,'-').replace(/^-+|-+$/g,'');
    let slugTimer;
    titleField?.addEventListener('input', () => {
        if (slugField?.value.trim()) return;
        clearTimeout(slugTimer);
        slugTimer = setTimeout(() => { if (slugField) slugField.value = slugify(titleField.value); }, 400);
    });

    // ── Copy token ────────────────────────────────────────────────────── //
    document.querySelectorAll('[data-copy]').forEach(btn => {
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(btn.dataset.copy).then(() => {
                const orig = btn.textContent; btn.textContent = 'Copied!';
                setTimeout(() => btn.textContent = orig, 2000);
            });
        });
    });

    // ════════════════════════════════════════════════════════════════════ //
    // PAGE / BLOCK MANAGEMENT
    // ════════════════════════════════════════════════════════════════════ //

    // Renumber all pages and set all named fields
    const refreshPages = () => {
        pagesRoot.querySelectorAll('[data-page]').forEach((page, pi) => {
            const numEl = page.querySelector('.page-num');
            if (numEl) numEl.textContent = `Page ${pi + 1}`;

            const labelInput = page.querySelector('[data-role="page-label"]');
            const titleInput = page.querySelector('[data-role="page-title"]');
            const layoutInput = page.querySelector('[data-layout-input]');
            if (labelInput) labelInput.name  = `document[sections][${pi}][label]`;
            if (titleInput) titleInput.name  = `document[sections][${pi}][page_title]`;
            if (layoutInput) layoutInput.name = `document[sections][${pi}][layout]`;

            refreshBlocks(page, pi);
        });
    };

    const refreshBlocks = (page, pi) => {
        page.querySelectorAll('[data-block]').forEach((block, bi) => {
            const prefix = `document[sections][${pi}][blocks][${bi}]`;
            block.querySelectorAll('[name],[data-field]').forEach(el => {
                const field = el.getAttribute('data-field');
                if (field) el.setAttribute('name', `${prefix}[${field}]`);
            });
        });
    };

    // Show only the field group(s) matching the selected block type — every
    // group stays in the DOM (just hidden) so refreshBlocks()'s data-field
    // naming keeps working regardless of which type is active. Column pills are
    // deliberately left to the layout-tab handler below (it already re-checks
    // every block on the page each time the layout changes) — this function may
    // run on a still-detached clone (buildBlock) with no page ancestor yet, so it
    // can't reliably know the current layout here.
    const applyBlockType = (block, type) => {
        block.dataset.blockType = type;
        block.querySelectorAll('[data-type-group]').forEach(group => {
            const types = group.dataset.typeGroup.split(',');
            group.classList.toggle('hidden', !types.includes(type));
        });
    };

    // Build a block DOM element from data object
    const buildBlock = (data = {}) => {
        const clone = blockTmpl.content.cloneNode(true);
        const el    = clone.querySelector('[data-block]');

        const setVal = (field, val) => {
            const input = el.querySelector(`[data-field="${field}"]`);
            if (input) input.value = val ?? '';
        };
        const type = data.type || 'narrative';
        const typeSelect = el.querySelector('[data-type-select]');
        if (typeSelect) typeSelect.value = type;

        setVal('number',   data.number   ?? '');
        setVal('title',    data.title    ?? '');
        setVal('subtitle', data.subtitle ?? '');
        setVal('body',     data.body     ?? '');
        setVal('column',   data.column   ?? 'full');

        if (type === 'bullet_list') {
            setVal('bl_intro', data.intro ?? '');
            const styleSel = el.querySelector('[data-field="bl_bullet_style"]');
            if (styleSel) styleSel.value = data.bullet_style ?? 'square';
            setVal('bl_items_text', (data.items || []).join('\n'));
        } else if (type === 'multi_column_list') {
            setVal('mc_columns_text', (data.columns || []).map(c => `${c.heading || ''}: ${(c.items || []).join(', ')}`).join('\n'));
        } else if (type === 'stage_grid') {
            const colSel = el.querySelector('[data-field="sg_columns"]');
            if (colSel) colSel.value = String(data.columns ?? 2);
            setVal('sg_stages_text', (data.stages || []).map(s => {
                const head = [s.label, s.duration].filter(Boolean).join(' | ');
                const items = (s.items || []).map(i => '- ' + i).join('\n');
                return [head, items].filter(Boolean).join('\n');
            }).join('\n\n'));
        } else if (type === 'comment_lines') {
            setVal('cl_label', data.label ?? '');
            setVal('cl_line_count', data.line_count ?? 4);
        } else if (type === 'signature_block') {
            setVal('sb_intro', data.intro ?? '');
            setVal('sb_date_label', data.date_label ?? '');
            setVal('sb_signers_text', (data.signers || []).map(s => [s.role, s.name, s.subtitle, s.phone].join(' | ')).join('\n'));
        }

        // Set column pill active state
        const col = data.column ?? 'full';
        el.querySelectorAll('[data-col]').forEach(pill => {
            pill.classList.toggle('active', pill.dataset.col === col);
        });

        applyBlockType(el, type);

        return el;
    };

    // Init all interactive behaviours on a page element
    const initPage = (page) => {
        const blocksWrap = page.querySelector('[data-blocks-wrap]');
        const blocksList = page.querySelector('[data-blocks-list]');
        const layoutTabs = page.querySelector('[data-layout-tabs]');
        const layoutInput = page.querySelector('[data-layout-input]');

        // Collapse toggle
        page.querySelector('[data-collapse-page]')?.addEventListener('click', (e) => {
            if (!blocksWrap) return;
            const collapsed = blocksWrap.classList.contains('is-collapsed');
            if (collapsed) {
                blocksWrap.style.maxHeight = blocksWrap.scrollHeight + 'px';
                blocksWrap.classList.remove('is-collapsed');
                e.target.textContent = '−';
            } else {
                blocksWrap.style.maxHeight = blocksWrap.scrollHeight + 'px';
                requestAnimationFrame(() => {
                    blocksWrap.classList.add('is-collapsed');
                    e.target.textContent = '+';
                });
            }
        });

        // Remove page
        page.querySelector('[data-remove-page]')?.addEventListener('click', async () => {
            const ok = await confirm('Remove this page and all its blocks?');
            if (!ok) return;
            page.style.opacity = '0'; page.style.transition = 'opacity 0.2s';
            setTimeout(() => { page.remove(); refreshPages(); }, 200);
            markDirty();
        });

        // Add block
        page.querySelector('[data-add-block]')?.addEventListener('click', () => {
            const el = buildBlock();
            initBlock(el, page);
            blocksList.appendChild(el);
            initAutoResize(el);
            refreshPages();
            markDirty();
        });

        // Layout tabs
        layoutTabs?.querySelectorAll('[data-layout-val]').forEach(tab => {
            tab.addEventListener('click', () => {
                const val = tab.dataset.layoutVal;
                layoutTabs.querySelectorAll('[data-layout-val]').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                if (layoutInput) layoutInput.value = val;
                // Show/hide column pills on all blocks — only narrative/bullet_list
                // blocks support column placement, other types ignore it entirely.
                const isTwoCol = val === 'two-column';
                page.querySelectorAll('[data-block]').forEach(block => {
                    const pills = block.querySelector('[data-col-pills]');
                    if (!pills) return;
                    const supportsColumns = ['narrative', 'bullet_list'].includes(block.dataset.blockType || 'narrative');
                    pills.classList.toggle('hidden', !isTwoCol || !supportsColumns);
                });
                markDirty();
            });
        });

        // Init existing blocks
        page.querySelectorAll('[data-block]').forEach(block => initBlock(block, page));
    };

    const initBlock = (block, page) => {
        if (block.dataset.blockBound) return;
        block.dataset.blockBound = '1';

        // Remove block
        block.querySelector('[data-remove-block]')?.addEventListener('click', () => {
            block.style.opacity = '0'; block.style.transition = 'opacity 0.15s';
            setTimeout(() => { block.remove(); refreshPages(); }, 160);
            markDirty();
        });

        // Column pills
        const colInput = block.querySelector('[data-col-input]');
        block.querySelectorAll('[data-col]').forEach(pill => {
            pill.addEventListener('click', () => {
                block.querySelectorAll('[data-col]').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                if (colInput) colInput.value = pill.dataset.col;
                markDirty();
            });
        });

        // Block type switcher
        const typeSelect = block.querySelector('[data-type-select]');
        typeSelect?.addEventListener('change', () => {
            applyBlockType(block, typeSelect.value);
            initAutoResize(block);
            markDirty();
        });
        // Server-rendered blocks already have the right hidden/visible classes
        // baked in from PHP, but stamp blockType so the layout-tab handler and
        // future applyBlockType() calls agree with what's actually selected.
        if (!block.dataset.blockType && typeSelect) block.dataset.blockType = typeSelect.value;
    };

    // Init all existing pages on load
    pagesRoot.querySelectorAll('[data-page]').forEach(page => initPage(page));

    // Add new page
    addPageBtn?.addEventListener('click', () => {
        const clone = pageTmpl.content.cloneNode(true);
        const el    = clone.querySelector('[data-page]');
        el.classList.add('section-in');
        pagesRoot.appendChild(el);
        initPage(el);
        refreshPages();
        markDirty();
    });

    // ════════════════════════════════════════════════════════════════════ //
    // TEMPLATE SWITCHER
    // ════════════════════════════════════════════════════════════════════ //
    const templateSelect  = document.getElementById('field-template-key');
    const templateDesc    = document.getElementById('template-desc');
    const loadTemplateBtn = document.getElementById('load-template-btn');
    const loadTemplateLbl = document.getElementById('load-template-label');
    const DEFAULTS_URL    = @json($templateDefaultsUrl);

    const updateTemplateUI = () => {
        if (!templateSelect) return;
        const opt = templateSelect.options[templateSelect.selectedIndex];
        if (templateDesc) templateDesc.textContent = opt.dataset.description || '';
        if (loadTemplateLbl) loadTemplateLbl.textContent = opt.text.trim();
        if (loadTemplateBtn) loadTemplateBtn.classList.remove('hidden');
    };

    const buildPageFromData = (data) => {
        const clone = pageTmpl.content.cloneNode(true);
        const el    = clone.querySelector('[data-page]');

        const labelInput  = el.querySelector('[data-role="page-label"]');
        const titleInput  = el.querySelector('[data-role="page-title"]');
        const layoutInput = el.querySelector('[data-layout-input]');
        const blocksList  = el.querySelector('[data-blocks-list]');

        if (labelInput) labelInput.value  = data.label      || '';
        if (titleInput) titleInput.value  = data.page_title || '';

        const layout = data.layout || 'single-column';
        if (layoutInput) layoutInput.value = layout;
        el.querySelectorAll('[data-layout-val]').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.layoutVal === layout);
        });

        (data.blocks || []).forEach(blockData => {
            const blockEl = buildBlock(blockData);
            const isTwoCol = layout === 'two-column';
            blockEl.querySelectorAll('[data-col-pills]').forEach(p => p.classList.toggle('hidden', !isTwoCol));
            if (blocksList) blocksList.appendChild(blockEl);
        });

        el.classList.add('section-in');
        return el;
    };

    const loadTemplateDefaults = async (key, label) => {
        const ok = await showConfirm(
            `Load "${label}" defaults?`,
            'This will replace the scope line and all content pages with the preset for this project type. You can edit everything afterwards.',
            'Load defaults', 'Keep current'
        );
        if (!ok) return;

        try {
            loadTemplateBtn.textContent = 'Loading…';
            loadTemplateBtn.disabled = true;

            const res  = await fetch(`${DEFAULTS_URL}?key=${encodeURIComponent(key)}`);
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Failed');

            // Update scope
            const scopeField = document.querySelector('textarea[name="document[header][scope]"]');
            if (scopeField && data.scope) { scopeField.value = data.scope; autoResize(scopeField); updateScopeCounter(); }

            // Rebuild pages
            if (pagesRoot && Array.isArray(data.sections)) {
                pagesRoot.innerHTML = '';
                data.sections.forEach(sectionData => {
                    const pageEl = buildPageFromData(sectionData);
                    pagesRoot.appendChild(pageEl);
                    initPage(pageEl);
                    initAutoResize(pageEl);
                });
                refreshPages();
            }

            markDirty();
        } catch (err) {
            alert('Could not load defaults. Please try again.');
        } finally {
            loadTemplateBtn.disabled = false;
            updateTemplateUI();
        }
    };

    updateTemplateUI();
    let lastTemplateKey = templateSelect?.value;
    templateSelect?.addEventListener('change', () => {
        const key   = templateSelect.value;
        const label = templateSelect.options[templateSelect.selectedIndex].text.trim();
        updateTemplateUI();
        if (key !== lastTemplateKey) { lastTemplateKey = key; loadTemplateDefaults(key, label); }
    });
    loadTemplateBtn?.addEventListener('click', () => {
        const key   = templateSelect.value;
        const label = templateSelect.options[templateSelect.selectedIndex].text.trim();
        loadTemplateDefaults(key, label);
    });

    // ── Confirm dialog ────────────────────────────────────────────────── //
    const showConfirm = (title, msg, okLabel = 'Confirm', cancelLabel = 'Cancel') => {
        return new Promise(resolve => {
            document.getElementById('drenla-confirm')?.remove();
            const ov = document.createElement('div');
            ov.id = 'drenla-confirm';
            ov.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.8);backdrop-filter:blur(4px)';
            ov.innerHTML = `<div style="width:420px;max-width:90vw;border:1px solid rgba(255,255,255,0.12);background:#0a0a0a;padding:32px;font-family:inherit;">
                <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:0.22em;text-transform:uppercase;color:rgba(255,255,255,0.3);">Confirm</p>
                <h3 style="margin:10px 0 0;font-size:18px;font-weight:300;letter-spacing:-0.03em;color:#fff;">${title}</h3>
                <p style="margin:10px 0 0;font-size:13px;line-height:1.6;color:rgba(255,255,255,0.5);">${msg}</p>
                <div style="margin-top:24px;display:flex;gap:10px;">
                    <button id="c-ok" style="flex:1;border:1px solid #fff;background:#fff;color:#000;padding:10px;font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;cursor:pointer;">${okLabel}</button>
                    <button id="c-no" style="flex:1;border:1px solid rgba(255,255,255,0.12);background:transparent;color:rgba(255,255,255,0.6);padding:10px;font-size:11px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;cursor:pointer;">${cancelLabel}</button>
                </div></div>`;
            document.body.appendChild(ov);
            ov.querySelector('#c-ok').onclick  = () => { ov.remove(); resolve(true);  };
            ov.querySelector('#c-no').onclick  = () => { ov.remove(); resolve(false); };
            ov.onclick = e => { if (e.target === ov) { ov.remove(); resolve(false); } };
        });
    };

    // init
    initEditorSections();
    refreshPages();
});
</script>
@endsection
