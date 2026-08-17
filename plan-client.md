# Client Portal — Plan

_Last updated: 2026-07-03._

## Vision

The client portal should feel like a trustworthy, live mirror of the engagement — a client can see exactly what admin sees about their own project (progress, tasks, payment gates, documents), minus internal ops detail (who's assigned, hour estimates). Gating must be real everywhere it's shown, never decorative.

## Current State (verified against code, 2026-07-03)

Confirmed shipped, end-to-end:
- Separate `client` session guard + magic-link/token login, no password (`config/auth.php`, `app/Http/Controllers/Auth/ClientPortalAuthController.php`, `app/Http/Middleware/EnsureClientPortalAccess.php`).
- Dashboard, project detail, finance list/detail, proposal detail (`app/Http/Controllers/Portal/**`, `resources/views/portal/**`).
- Every controller scopes with `abort_unless($model->client_id === $client->id, 404)`.
- `MilestoneGatingService` — single source of truth: a milestone is blocked iff `payment_required && payment_status !== 'paid'`. Portal shows milestones up to and including the first blocked one; nothing after it; a blocked milestone's tasks are hidden.

## This Iteration's Scope (added 2026-07-03)

- **Section navigation**: portal layout (`resources/views/layouts/portal.blade.php`) had no nav beyond sign-out. Added a persistent tab row (Overview/Projects/Finance/Proposals) with active-state highlighting, plus new `portal.projects.index` and `portal.proposals.index` routes/controllers/views so each section is directly reachable.
- **Full-width shell**: the portal layout's `max-w-[1100px]` cap was removed entirely — the whole shell (not just the progress table) is now full width, matching admin's ops-panel approach.
- **Real progress tracking**: the project show page (`resources/views/portal/projects/show.blade.php`) gained a stats strip (Total/Done/In progress/To do/Blocked/Overdue) and a full week-based Gantt swimlane table, re-themed to the portal's light palette — same structural fidelity as `resources/views/admin/projects/show.blade.php`, minus assignee name and estimated hours (internal-only fields, deliberately never rendered in the portal view).
- **`ProjectTimeline::build()`** (`app/Support/ProjectTimeline.php`) gained optional `$visibleMilestoneIds`/`$taskVisibleMilestoneIds` params so the portal reuses the exact same Gantt math as admin rather than duplicating it — admin's own no-arg call site is unaffected.
- **Payment marker correctness bug fixed**: milestones have two independent fields — a workflow `status` (which can literally hold the value `"paid"`) and a separate `payment_status` (the actual gating signal). The Gantt previously derived color from `status`, which could render a green "paid" dot for a milestone that hadn't actually been paid. Fixed to always derive payment color/label from the gating result (`MilestoneGatingService`), with a new **overdue** state (orange, `#c2410c`) whenever a blocked milestone's `due_date` has passed — a past-due unpaid milestone must never read as "paid" or even plain "pending."
- **Payment markers are date-based, not row-based**: moved from being drawn inside each milestone's own swimlane row (bounded to that row's height) to a single overlay spanning the full table height — a payment due date is a fact about the timeline, not about one task row.
- **Proposal view now embeds the real branded document**: `portal/proposals/show.blade.php` previously re-rendered `$proposal->normalized_document_data` as flattened prose sections. It now embeds the same print/PDF template admin's "Preview" uses (`admin/proposals/print.blade.php`) via a short-lived signed URL (`URL::temporarySignedRoute('admin.proposals.print', ...)` — that route already runs with no session, by design, for headless-Chrome PDF export), plus a client-facing **Download PDF** button (`Portal\ProposalController::download()`, reuses `ProposalPdfExporter`, scoped to the client's own visible proposals).
- **Document header art simplified**: the proposal/finance print template's cover header used a purple gradient/scan-line SVG pattern (`resources/views/admin/proposals/_cover-header.blade.php`); replaced with a solid black background across both `admin/proposals/print.blade.php` and `admin/finance/print.blade.php` (shared partial).

## Architecture Recap

- Guard/auth: `config/auth.php` (`client` guard/provider), `Client` model (`app/Models/Client.php`, token-based, no password).
- Gating: `App\Services\MilestoneGatingService`, `App\Support\PortalMilestoneView`.
- Timeline: `App\Support\ProjectTimeline::build()`.
- Portal controllers: `App\Http\Controllers\Portal\{Dashboard,Project,Proposal,FinanceDocument}Controller`.
- Portal views: `resources/views/portal/**`, layout `resources/views/layouts/portal.blade.php`.
- Shared document template (admin + portal both use it): `resources/views/admin/proposals/print.blade.php`, `resources/views/admin/proposals/_cover-header.blade.php`, `resources/views/admin/finance/print.blade.php`.

## Known Gaps (not built, flagged not blocking)

- No FK from `ProjectMilestone` to a specific `FinanceDocument` — payment_status is set manually by staff, not derived from an actual invoice/payment record.
- No self-service online payment initiation from the portal.
- Proposal "Attachments" section still lists files separately from the embedded document iframe (unchanged from before — reasonable, since attachments are distinct uploaded files, not part of the document body).

## Test Plan

- `php artisan test` — 157/157 passing as of this iteration (was 151 baseline + 6 new tests added: index-route scoping, assignee/hours leak check, blocked-task-hidden-in-table check, overdue-labeling check).
- Manual: log in via portal magic-link, click through all four nav tabs, open a project with a blocked+overdue milestone and confirm the Gantt/stats/milestone-card all say "overdue" (never "paid"), open a proposal and confirm the embedded preview matches admin's and the Download button produces a real PDF.
