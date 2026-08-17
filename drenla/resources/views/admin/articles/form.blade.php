@extends('layouts.admin')

@section('content')
<div class="max-w-5xl">
    <p class="text-eyebrow">Content</p>
    <h2 class="mt-3 text-headline">{{ $article->exists ? 'Edit article' : 'New article' }}</h2>

    <form method="POST"
          action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
          class="mt-8 space-y-6 border border-white/10 bg-black/90 p-6 backdrop-blur">
        @csrf
        @if($article->exists) @method('PUT') @endif

        {{-- ── Row 1: title / slug ─────────────────────────────────────── --}}
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label class="field-label">Title</label>
                <input type="text" name="title" value="{{ old('title', $article->title) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Slug</label>
                <input type="text" name="slug" value="{{ old('slug', $article->slug) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Type</label>
                <select name="type" class="field-input">
                    <option value="insight"  @selected(old('type', $article->type) === 'insight')>Insight</option>
                    <option value="resource" @selected(old('type', $article->type) === 'resource')>Resource</option>
                </select>
            </div>
            <div>
                <label class="field-label">Published at</label>
                <input type="date" name="published_at"
                       value="{{ old('published_at', optional($article->published_at)->toDateString()) }}"
                       class="field-input">
            </div>
            <div>
                <label class="field-label">Excerpt</label>
                <input type="text" name="excerpt" value="{{ old('excerpt', $article->excerpt) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Status</label>
                <select name="status" class="field-input">
                    <option value="draft"     @selected(old('status', $article->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $article->status) === 'published')>Published</option>
                </select>
            </div>
        </div>

        {{-- ── WYSIWYG body ─────────────────────────────────────────────── --}}
        <div>
            <label class="field-label">Body</label>

            <div data-wysiwyg class="border border-white/12 focus-within:border-white/30 transition-colors">

                {{-- Toolbar --}}
                <div class="flex flex-wrap items-center gap-0.5 border-b border-white/10 bg-white/[0.03] px-2 py-1.5">

                    {{-- Text style --}}
                    <button type="button" data-action="bold"
                            class="wysiwyg-btn font-bold" title="Bold (Ctrl+B)">B</button>
                    <button type="button" data-action="italic"
                            class="wysiwyg-btn italic" title="Italic (Ctrl+I)">I</button>
                    <button type="button" data-action="underline"
                            class="wysiwyg-btn underline" title="Underline (Ctrl+U)">U</button>
                    <button type="button" data-action="strike"
                            class="wysiwyg-btn line-through" title="Strikethrough">S</button>

                    <div class="wysiwyg-sep"></div>

                    {{-- Headings --}}
                    <button type="button" data-action="heading" data-level="2"
                            class="wysiwyg-btn" title="Heading 2">H2</button>
                    <button type="button" data-action="heading" data-level="3"
                            class="wysiwyg-btn" title="Heading 3">H3</button>
                    <button type="button" data-action="heading" data-level="4"
                            class="wysiwyg-btn" title="Heading 4">H4</button>

                    <div class="wysiwyg-sep"></div>

                    {{-- Lists --}}
                    <button type="button" data-action="bullet"
                            class="wysiwyg-btn" title="Bullet list">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor">
                            <circle cx="2" cy="4" r="1.5"/><rect x="5" y="3" width="10" height="2" rx="1"/>
                            <circle cx="2" cy="9" r="1.5"/><rect x="5" y="8" width="10" height="2" rx="1"/>
                            <circle cx="2" cy="14" r="1.5"/><rect x="5" y="13" width="7" height="2" rx="1"/>
                        </svg>
                    </button>
                    <button type="button" data-action="ordered"
                            class="wysiwyg-btn" title="Numbered list">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor">
                            <text x="0" y="5" font-size="5" font-family="monospace">1.</text>
                            <rect x="5" y="3" width="10" height="2" rx="1"/>
                            <text x="0" y="10" font-size="5" font-family="monospace">2.</text>
                            <rect x="5" y="8" width="10" height="2" rx="1"/>
                            <text x="0" y="15" font-size="5" font-family="monospace">3.</text>
                            <rect x="5" y="13" width="7" height="2" rx="1"/>
                        </svg>
                    </button>

                    <div class="wysiwyg-sep"></div>

                    {{-- Block --}}
                    <button type="button" data-action="blockquote"
                            class="wysiwyg-btn" title="Blockquote">&ldquo;&rdquo;</button>
                    <button type="button" data-action="code"
                            class="wysiwyg-btn font-mono text-[10px]" title="Inline code">`c`</button>
                    <button type="button" data-action="codeblock"
                            class="wysiwyg-btn font-mono text-[10px]" title="Code block">{ }</button>
                    <button type="button" data-action="hr"
                            class="wysiwyg-btn" title="Horizontal rule">—</button>

                    <div class="wysiwyg-sep"></div>

                    {{-- Link --}}
                    <button type="button" data-action="link"
                            class="wysiwyg-btn" title="Add / edit link">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M6.5 9.5a3.5 3.5 0 005 0l2-2a3.5 3.5 0 00-5-5L7 4"/>
                            <path d="M9.5 6.5a3.5 3.5 0 00-5 0l-2 2a3.5 3.5 0 005 5L9 12"/>
                        </svg>
                    </button>
                    <button type="button" data-action="unlink"
                            class="wysiwyg-btn" title="Remove link">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M6.5 9.5a3.5 3.5 0 005 0l2-2a3.5 3.5 0 00-5-5L7 4"/>
                            <path d="M9.5 6.5a3.5 3.5 0 00-5 0l-2 2a3.5 3.5 0 005 5L9 12"/>
                            <line x1="2" y1="2" x2="14" y2="14"/>
                        </svg>
                    </button>

                    <div class="wysiwyg-sep"></div>

                    {{-- History --}}
                    <button type="button" data-action="undo"
                            class="wysiwyg-btn" title="Undo (Ctrl+Z)">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 7a5 5 0 110 5" stroke-linecap="round"/>
                            <polyline points="3,4 3,7 6,7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button type="button" data-action="redo"
                            class="wysiwyg-btn" title="Redo (Ctrl+Y)">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M13 7a5 5 0 110 5" stroke-linecap="round"/>
                            <polyline points="13,4 13,7 10,7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>

                    <div class="ml-auto"></div>

                    <button type="button" data-action="clear"
                            class="wysiwyg-btn text-white/30 hover:text-red-400" title="Clear all content">
                        Clear
                    </button>
                </div>

                {{-- Editor canvas --}}
                <div data-editor class="wysiwyg-editor"></div>

                {{-- Hidden textarea — receives HTML on every update --}}
                <textarea data-body name="body" class="hidden">{{ old('body', $article->body) }}</textarea>
            </div>
        </div>

        {{-- ── SEO ──────────────────────────────────────────────────────── --}}
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label class="field-label">Meta title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $article->meta_title) }}" class="field-input">
            </div>
            <div>
                <label class="field-label">Meta description</label>
                <textarea name="meta_description" rows="3" class="field-input">{{ old('meta_description', $article->meta_description) }}</textarea>
            </div>
        </div>

        <label class="flex items-center gap-3 text-sm text-white/60">
            <input type="checkbox" name="featured" value="1"
                   class="h-4 w-4 border-white/15 bg-black text-white"
                   @checked(old('featured', $article->featured))>
            Featured
        </label>

        <button class="inline-flex items-center border border-white bg-white px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-black transition hover:bg-white/90">
            Save article
        </button>
    </form>
</div>

<style>
/* ── Field helpers ────────────────────────────────────────────────────── */
.field-label {
    display: block; margin-bottom: 8px;
    font-size: 11px; font-weight: 700;
    letter-spacing: .22em; text-transform: uppercase; color: rgba(255,255,255,.35);
}
.field-input {
    width: 100%; border: 1px solid rgba(255,255,255,.12);
    background: #000; padding: 12px 16px; color: #fff;
    outline: none; transition: border-color .15s;
}
.field-input:focus { border-color: rgba(255,255,255,.4); }

/* ── Toolbar buttons ──────────────────────────────────────────────────── */
.wysiwyg-btn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 28px; padding: 0 6px;
    font-size: 11px; font-weight: 700; letter-spacing: .05em;
    color: rgba(255,255,255,.45); background: transparent;
    border: 1px solid transparent; border-radius: 3px;
    cursor: pointer; transition: color .12s, background .12s, border-color .12s;
    user-select: none;
}
.wysiwyg-btn:hover { color: #fff; background: rgba(255,255,255,.07); }
.wysiwyg-btn.is-active {
    color: #fff; background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.18);
}
.wysiwyg-sep {
    width: 1px; align-self: stretch; background: rgba(255,255,255,.1); margin: 2px 4px;
}

/* ── Editor canvas ────────────────────────────────────────────────────── */
.wysiwyg-editor {
    min-height: 340px; padding: 20px 24px;
    background: #000; color: #e8e8ec; font-size: 14px; line-height: 1.7;
    cursor: text;
}
/* ProseMirror internals */
.wysiwyg-editor .ProseMirror { outline: none; min-height: 300px; }
.wysiwyg-editor .ProseMirror > * + * { margin-top: .6em; }

.wysiwyg-editor .ProseMirror p   { margin: 0 0 .5em; }
.wysiwyg-editor .ProseMirror h2  { font-size: 1.45em; font-weight: 700; letter-spacing: -.02em; color: #fff; margin: 1.2em 0 .4em; }
.wysiwyg-editor .ProseMirror h3  { font-size: 1.2em;  font-weight: 700; letter-spacing: -.01em; color: #fff; margin: 1em 0 .3em; }
.wysiwyg-editor .ProseMirror h4  { font-size: 1em;    font-weight: 700; letter-spacing: .02em; text-transform: uppercase; color: rgba(255,255,255,.7); margin: .9em 0 .3em; }

.wysiwyg-editor .ProseMirror ul  { list-style: disc;    padding-left: 1.5em; }
.wysiwyg-editor .ProseMirror ol  { list-style: decimal; padding-left: 1.5em; }
.wysiwyg-editor .ProseMirror li  { margin-bottom: .2em; }

.wysiwyg-editor .ProseMirror blockquote {
    border-left: 3px solid rgba(255,255,255,.25);
    margin: .8em 0; padding: .4em 1em;
    color: rgba(255,255,255,.55); font-style: italic;
}
.wysiwyg-editor .ProseMirror code {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: .88em; background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.1);
    padding: .1em .35em; border-radius: 3px; color: #c9b3ff;
}
.wysiwyg-editor .ProseMirror pre {
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 4px; padding: 1em 1.2em; overflow-x: auto;
}
.wysiwyg-editor .ProseMirror pre code {
    background: none; border: none; padding: 0; color: #b8e4b8;
}
.wysiwyg-editor .ProseMirror a {
    color: #a78bfa; text-decoration: underline; text-underline-offset: 2px;
}
.wysiwyg-editor .ProseMirror a:hover { color: #c4b5fd; }
.wysiwyg-editor .ProseMirror hr {
    border: none; border-top: 1px solid rgba(255,255,255,.15); margin: 1.2em 0;
}
/* Placeholder */
.wysiwyg-editor .ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    color: rgba(255,255,255,.2); pointer-events: none; float: left; height: 0;
}
/* Selection highlight */
.wysiwyg-editor .ProseMirror ::selection { background: rgba(139,92,246,.35); }
</style>
@endsection
