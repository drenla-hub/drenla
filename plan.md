# Drenla — Architecture, Vision & Delivery Plan

_Last updated: 2026-07-02_

## 0. Vision

Drenla's backend should not feel like a generic admin CRUD app bolted onto a marketing site. The bar is: **an internal operating system for a studio** — one place where a proposal becomes a project, a project becomes milestones and tasks, milestones gate payments, and the client sees a live, trustworthy mirror of their own engagement without ever emailing "what's the status?" The existing AI assistant embedded in `/admin` (Gemini-backed, tool-calling over projects) is already a signal of that ambition — lean into it rather than treating it as a side feature.

Two things make this "out of this world" instead of "another Laravel admin":
1. **The gating pipeline is real, not decorative.** Payment-required milestones actually block downstream project progression and client visibility — enforced in one service, reflected everywhere (admin, portal, invoices) from a single source of truth.
2. **The client portal is a product, not an afterthought.** Token/auth-gated, reads the exact same models as `/admin`, and is polished enough that a client would believe it was built as the primary product.

Everything below is scoped to make that true incrementally — not to gold-plate before the foundations are solid.

## 1. Current State (verified against code, not the old task list)

The Laravel app at `drenla/` is substantially built out already. Confirmed present:
- Admin auth + roles (`super_admin`, `manager`, `editor`) via `EnsureUserIsAdmin` middleware, `AdminAuthController`.
- Admin CRUD for: focus areas, case studies, articles, clients, proposals, projects (+ milestones + tasks), finance documents, inquiries, site settings.
- Proposal workspace with structured document data, branded print/PDF export (`ProposalPdfExporter`), reference-number generation.
- Full public API under `/api/site/*` (settings, home, work, work/{slug}, insights, insights/{slug}, resources, focus-areas, contact) — published-only filtering in place.
- Data model already includes the hard parts of the gating story: `project_milestones.payment_required`, `payment_status`, `blocked_reason`; `clients.portal_access_enabled` / `portal_access_token`; `proposals.access_token`, `is_client_visible`. **The schema already anticipates the client portal — it has not been built on top of yet.**
- An embedded AI assistant in `/admin` (`app/AiAssistant/**`) with a tool registry (`ListProjectsTool`, `ProjectOverviewTool`) and a Gemini provider.
- Feature test coverage for admin access, contact submission, proposal workspace, public API, admin AI assistant.

Confirmed **not yet built** (this is the real remaining scope, not what the old `tasks.md` claimed):
- No homepage content management screens — `HomepageSection` model exists, no controller/views.
- No media library — `MediaAsset` model + `getUrlAttribute()` exist, no upload/browse/attach UI anywhere.
- No client portal at all — no portal routes, no portal auth guard/middleware, no portal views, despite the schema being ready for it.
- No milestone/payment **gating enforcement** — the fields exist (`payment_status`, `blocked_reason`) but nothing currently reads them to block a downstream stage; it's inert data today.
- No dedicated invoice/estimate/receipt workflow beyond one generic finance form (no type-specific UX, no PDF export/send for finance docs the way proposals have one).
- No task comments UI (the `TaskComment` model and relation exist and are unused in the UI).
- No proposal file attachments, no client_contacts structure, no lead/relationship notes beyond a flat `notes` text field on `Inquiry`.
- No email/notification delivery workflow for client-facing documents.

### A note on repo hygiene
`frontend/drenla/` is a **stale duplicate Laravel skeleton** (fresh install, no admin/API code, has its own `.codex`/`opencode.json`/`.mcp.json`). The real, live backend is `drenla/` at the repo root. Don't build anything in `frontend/drenla/` — it appears to be scaffolding debris from an earlier tool run. Flagging it here rather than deleting it, since neither of us created it and it may be intentionally kept by the user.

## 2. Role Division — CL and CO

Both agents work in the same working directory (`/home/kimrop/Desktop/programs/drenla`) concurrently. The split is by **file/module ownership**, not by "who's free" — this is what prevents collisions. Full detail and live coordination lives in `collaboration-notes.md`; this section is the standing contract.

### CL (Claude) — Laravel Admin Panel, end-to-end
Everything a staff user touches at `/admin`. Owns:
- `app/Http/Controllers/Admin/**` (existing + new: `HomepageSectionController`, `MediaAssetController`)
- `resources/views/admin/**`, `resources/views/layouts/admin.blade.php`
- `resources/js/admin-assistant.js`, `resources/js/editor.js`
- The **admin route group** inside `routes/web.php` (see shared-file protocol below)
- `app/Http/Middleware/EnsureUserIsAdmin.php`
- `app/AiAssistant/**` (admin-only feature)
- `tests/Feature/Admin*.php`
- `DESIGN_LANGUAGE.md` — CL keeps it current as new admin patterns are added

Build targets for CL (the actual gaps, see `tasks.md` for the checklist):
homepage content management, media library (upload/browse/attach picker), invoice/estimate/receipt dedicated workflows, payment/gating visibility + admin-side controls, task comments UI, client-contacts and portal-access-issuance UI, admin CRUD test coverage.

### CO — Platform: data model, public API, client portal, business logic
Everything that isn't the staff-facing admin UI. Owns:
- `app/Http/Controllers/Api/**`
- `app/Http/Controllers/Portal/**` (new) and `resources/views/portal/**` (new)
- A new `Auth\ClientPortalAuthController` (separate from `AdminAuthController`, which CL owns)
- `routes/api.php`, new `routes/portal.php`
- `app/Models/**` — CO owns schema/relationship changes (see migration protocol below)
- `database/migrations/**` — CO authors all new migrations
- `app/Services/**` domain services, notably a `MilestoneGatingService` that becomes the single source of truth both admin and portal read from, plus a document-delivery/notification service
- `app/Http/Middleware/EnsureClientPortalAccess.php` (new)
- `tests/Feature/Api*.php`, `Portal*.php`, authorization-boundary and gating tests
- Environment/config docs, asset/media URL strategy doc
- React frontend integration, whenever it resumes (still deferred per current scope)

### Why this split
- Zero directory overlap for the two biggest surfaces (`Admin/` vs `Portal/`+`Api/`).
- The one genuinely shared concern — gating logic — has a clear owner (CO builds the service), and CL only ever *consumes* it to render state, never re-implements it.
- Migrations are single-owner (CO) specifically to avoid two agents generating colliding timestamped migration files or racing `php artisan migrate` against the same sqlite file.

### Shared-file protocol (routes/web.php)
This file has both an admin group (CL) and will get no new portal group added here — portal routes go in the new `routes/portal.php` instead, precisely to keep `web.php` edits scoped to CL. If CO ever needs a change inside `web.php` (e.g. registering middleware), it must be logged in `collaboration-notes.md` before editing.

## 3. Architecture (carried forward, still accurate)

### Laravel is the backend product
- `/admin/*` — authenticated staff (CL's surface)
- `/portal/*` — authenticated/tokenized clients (CO's new surface)
- `/api/*` — public + future authenticated JSON for React (CO's surface)
- `/` — minimal landing, not the public site (React remains the public site for now)

### Data model direction (already largely implemented — see §1)
Canonical entities: `users` (role-enabled), `clients`, `site_settings`, `homepage_sections`, `focus_areas`, `case_studies`, `articles`, `media_assets`, `inquiries`, `proposals`, `projects`, `project_milestones`, `project_tasks`, `task_comments`, `finance_documents`, `finance_document_items`. Remaining schema work (CO-owned): `client_contacts`, `proposal_files`, gating audit trail if needed.

### Public API for React (already implemented)
`GET /api/site/{settings,home,work,work/{slug},insights,insights/{slug},resources,focus-areas}`, `POST /api/site/contact`. React integration itself remains explicitly deferred — do not start it until told.

### Client portal (net-new, CO)
- Auth: token-based via `clients.portal_access_token` (already in schema) or lightweight session auth — CO to decide and document the choice in `collaboration-notes.md` before building.
- Reads the same `Proposal`, `Project`, `ProjectMilestone`, `ProjectTask`, `FinanceDocument` models admin uses — no duplicate "client copy" models.
- Surfaces: proposal view, project/milestone/task progress, invoices/receipts, payment-requirement + gating status.

### Payment-gated workflow (net-new enforcement, CO service + CL admin UI)
`MilestoneGatingService` is the single place that decides whether a milestone/stage is blocked given `payment_required` + `payment_status`. Admin (CL) renders this status and gives staff override controls; portal (CO) enforces it for what a client can see/do.

## 4. Test Plan
- CL: admin CRUD coverage for content types still missing tests (homepage, media), admin gating-visibility rendering, admin invoice/estimate/receipt flows.
- CO: public API coverage (already solid), client-portal authorization boundaries, gating-service unit/feature tests, proposal → project → finance linkage tests.
- Manual acceptance (joint): staff creates client → proposal → project → milestone plan → invoice chain; client logs into portal and sees only their own data; unpaid required milestone blocks the next gated stage in both admin display and portal access.

## 5. Assumptions and Defaults
- React stays a separate deployed frontend; do not touch it yet.
- `frontend/drenla/` is dead scaffolding — ignore it, don't build there.
- Migrations are CO-owned to prevent collisions; CL requests schema changes via `collaboration-notes.md` rather than writing its own migration files.
- Both agents run in the same working directory simultaneously — see `collaboration-notes.md` for the live coordination log, in-progress claims, and schema-change-request queue.
