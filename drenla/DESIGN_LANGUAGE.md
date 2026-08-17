# Drenla Design Language

Extracted from the main frontend at `/frontend/src/index.css` and applied to the admin OPS panel.
Every rule here must be followed when building or modifying any admin view.

---

## 1. Color Palette

| Token | Value | Usage |
|-------|-------|-------|
| Background | `#000000` / `bg-black` | Page background, sidebar, all panel backgrounds |
| Surface | `#080808` | Table header rows, section header strips |
| Surface 2 | `#111111` | Subtle card backgrounds on hover |
| Border | `#1a1a1a` / `border-[#1a1a1a]` | All borders, dividers, table separators |
| Border strong | `#1f1f1f` / `border-[#1f1f1f]` | Sidebar right border |
| Text primary | `#ffffff` / `text-white` | Headlines, active nav items, important values |
| Text secondary | `#777777` / `text-[#777]` | Table cell values, body text |
| Text muted | `#555555` / `text-[#555]` | Descriptions, secondary labels |
| Text dim | `#444444` / `text-[#444]` | Eyebrows, group labels, inactive icons |
| Text ghost | `#333333` / `text-[#333]` | Collapse toggle, least important UI |
| Status: active/won/published | `#44cc44` | Green status dots |
| Status: new/planning | `#777777` | Neutral grey |
| Status: qualified/in_progress | `#77ccff` | Blue tint |
| Status: proposal_sent | `#9999ff` | Purple tint |
| Status: warning/on_hold | `#ccaa77` | Amber tint |
| Status: lost/cancelled | `#cc4444` | Red tint |
| Brand purple | `hsl(260 60% 55%)` | Proposal cover headers only |

**Rule:** Never use stone-*, amber-*, rounded-*, or any non-black background for admin panels.

---

## 2. Typography

| Role | Classes |
|------|---------|
| Page eyebrow | `text-[9px] font-black uppercase tracking-[0.35em] text-[#444]` |
| Page title / H1 | `text-[32px] font-light tracking-[-0.025em] text-white` |
| Section heading | `text-[18px] font-light tracking-[-0.01em] text-white` |
| Table header | `text-[9px] font-black uppercase tracking-[0.3em] text-[#444]` |
| Table cell primary | `text-[13px] font-medium text-white` |
| Table cell secondary | `text-[13px] text-[#777]` |
| Table meta / subtext | `text-[11px] text-[#444]` |
| Description | `text-[13px] leading-relaxed text-[#555]` |
| Status badge | `text-[10px] font-bold uppercase tracking-[0.15em]` + status color |
| Nav item | `text-[12px] font-semibold uppercase tracking-[0.15em]` |
| Field label | `text-[9px] font-black uppercase tracking-[0.3em] text-[#444]` |
| Field value | `text-[13px] text-white` |
| Action link | `text-[11px] font-semibold uppercase tracking-[0.12em]` |

Font family: system default (no Mulish in admin — admin uses browser default sans-serif or whatever Tailwind base applies).

---

## 3. Spacing & Layout

- Page padding: `px-8 py-8 xl:px-12 xl:py-10`
- Max content width: `max-w-[1400px]`
- Section gap: `space-y-8` or `space-y-10`
- Page header always ends with `border-b border-[#1a1a1a] pb-8`
- Card/panel internal padding: `p-5` or `p-6`
- Table cell padding: `px-5 py-4`
- Table header padding: `px-5 py-3`

---

## 4. Borders & Shapes

- **Zero border radius** — never use `rounded-*` on any admin component
- All panels: `border border-[#1a1a1a]`
- All dividers: `divide-y divide-[#0e0e0e]` or `divide-[#111]`
- Sidebar right edge: `border-r border-[#1f1f1f]`
- Section header strip: `border-b border-[#1a1a1a] bg-[#080808]`

---

## 5. Interactive Elements

### Buttons — Primary (white)
```html
<button class="border border-white bg-white px-5 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-black transition hover:bg-transparent hover:text-white">
    Action
</button>
```

### Buttons — Ghost / Destructive
```html
<!-- Destructive -->
<button class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#444] hover:text-red-400 transition-colors">Delete</button>

<!-- Secondary action link -->
<a class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white hover:text-[#aaa] transition-colors">Edit</a>

<!-- Subdued action -->
<a class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#555] hover:text-white transition-colors">Preview</a>
```

### Form Inputs
```html
<label class="mb-2 block text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Field label</label>
<input class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white placeholder-[#333] focus:border-[#444] focus:outline-none transition-colors">
<select class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white focus:border-[#444] focus:outline-none transition-colors">
<textarea class="w-full border border-[#1f1f1f] bg-black px-4 py-3 text-[13px] text-white placeholder-[#333] focus:border-[#444] focus:outline-none transition-colors resize-none">
```

### Table rows
```html
<tr class="transition-colors hover:bg-[#080808]">
```

---

## 6. KPI / Stat Cards

```html
<div class="grid grid-cols-2 gap-px border border-[#1a1a1a] bg-[#1a1a1a] md:grid-cols-4">
    <div class="bg-black px-6 py-5">
        <p class="text-[9px] font-black uppercase tracking-[0.3em] text-[#444]">Label</p>
        <p class="mt-4 text-[36px] font-light leading-none tracking-[-0.03em] text-white">Value</p>
    </div>
</div>
```

The `gap-px bg-[#1a1a1a]` grid trick renders 1px borders between cells without an actual border element.

---

## 7. Status Colors

Use inline `style="color: {{ $color }}"` for dynamic status values:

| Status | Color hex |
|--------|-----------|
| `new`, `draft`, `planning` | `#777` |
| `active`, `published`, `won`, `completed` | `#4c4` |
| `qualified`, `in_progress` | `#7af` |
| `proposal_sent` | `#99f` |
| `on_hold`, `pending` | `#ca7` |
| `lost`, `cancelled` | `#c44` |
| `archived`, `inactive` | `#555` |

PHP pattern in Blade:
```php
@php
    $colorMap = ['active' => '#4c4', 'draft' => '#777', 'lost' => '#c44'];
    $color = $colorMap[$model->status] ?? '#555';
@endphp
<span class="text-[10px] font-bold uppercase tracking-[0.15em]" style="color: {{ $color }}">{{ $model->status }}</span>
```

---

## 8. Page Header Pattern

Every admin page starts with this header block:

```html
<div class="flex items-end justify-between border-b border-[#1a1a1a] pb-8">
    <div>
        <p class="text-[9px] font-black uppercase tracking-[0.35em] text-[#444]">Section Name</p>
        <h1 class="mt-3 text-[32px] font-light tracking-[-0.025em] text-white">Page Title</h1>
        <p class="mt-2 text-[13px] leading-relaxed text-[#555]">Short description.</p>
    </div>
    <!-- Optional: action button on the right -->
    <a href="..." class="inline-flex items-center gap-2 border border-white bg-white px-5 py-2.5 text-[10px] font-bold uppercase tracking-[0.2em] text-black transition hover:bg-transparent hover:text-white">
        New item
    </a>
</div>
```

---

## 9. Sidebar

- Background: `bg-black`
- Width expanded: `w-[240px]`
- Width collapsed: `w-[56px]`
- Right border: `border-r border-[#1f1f1f]`
- Group pattern: `group/sidebar` with `data-collapsed` attribute
- Items hidden when collapsed: `group-data-[collapsed=true]/sidebar:hidden`
- Active item: white text + `bg-[#111]` row background + `absolute left-0 inset-y-[5px] w-px bg-white` left bar (background alone reads as too flat/muted — pair it with the line, don't drop either)
- Inactive item: `text-[#888] hover:bg-[#0a0a0a] hover:text-white` (icon `text-[#666]`, `text-white` when active — kept a shade dimmer than the label so the label still reads as the primary signal)
- Group label: `text-[9px] font-black uppercase tracking-[0.35em] text-[#666]`
- Footer controls (sign out, collapse toggle): `text-[#777]`/`text-[#555]` at rest, `hover:bg-[#0a0a0a] hover:text-white` — same hover language as nav items, not a separate muted-forever treatment

**Note:** the sidebar previously used `#3a3a3a`/`#555`/`#444` throughout with no active-row background — direct user feedback ("sidepanel feels muted/gray") led to this brightening pass (2026-07-02). Don't regress to the old values.

---

## 10. Notification Bars

```html
<!-- Success -->
<div class="shrink-0 flex items-center gap-3 px-8 py-2.5 border-b border-emerald-900/60 bg-emerald-950/40">
    <p class="text-[12px] text-emerald-400/90">{{ session('status') }}</p>
</div>

<!-- Error -->
<div class="shrink-0 flex items-start gap-3 px-8 py-2.5 border-b border-red-900/60 bg-red-950/40">
    <li class="text-[12px] text-red-400/90">Error text</li>
</div>
```

---

## 11. Anti-patterns (Never Do)

- ❌ `rounded-*` on any admin component
- ❌ `bg-stone-*`, `text-stone-*`, `border-stone-*`
- ❌ `bg-amber-*`, `text-amber-*` (amber-400 buttons are forbidden)
- ❌ `bg-white/[0.02]` card backgrounds (too subtle, use bg-black or bg-[#080808])
- ❌ Custom CSS in `<style>` tags on content pages (use Tailwind utilities only)
- ❌ `backdrop-blur` on tables or cards
- ❌ Rounded full pills for status badges
- ❌ `text-headline`, `text-eyebrow`, `text-body-large` CSS classes (these exist in the frontend build, not the admin)
