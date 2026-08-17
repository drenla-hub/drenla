# Client Portal — Task Tracker

_Last updated: 2026-07-03. See `plan-client.md` for narrative context, `collaboration-notes.md` for the original portal build's decisions log._

## Already Done (baseline, shipped 2026-07-02)

- [x] `client` auth guard, magic-link + fallback login, `EnsureClientPortalAccess` middleware.
- [x] Dashboard, project detail (milestones + gating), finance list/detail, proposal detail.
- [x] `MilestoneGatingService` — blocked iff `payment_required && payment_status !== 'paid'`; blocks downstream milestone visibility and task completion.
- [x] Client-scoping (`abort_unless($model->client_id === $client->id, 404)`) on every portal controller.

## This Iteration (2026-07-03)

- [x] Add `portal.projects.index` / `portal.proposals.index` routes + controllers + views.
- [x] Add persistent nav tabs (Overview/Projects/Finance/Proposals) to `layouts/portal.blade.php`.
- [x] Remove the portal layout's `max-w-[1100px]` cap — full-width shell, matching admin.
- [x] Extend `ProjectTimeline::build()` with optional visibility-scoping params (admin call site unaffected).
- [x] Add stats strip + full Gantt swimlane table to `portal/projects/show.blade.php`, re-themed light, no assignee/hours.
- [x] Fix payment-color bug: milestone workflow `status` (can be literal `"paid"`) was being used for payment-state color instead of the actual gating result — fixed everywhere (Gantt row dot, payment marker, milestone-card badge) to route through `MilestoneGatingService`, with a new **overdue** (orange) state.
- [x] Move payment markers from per-row to a single full-table-height overlay (a payment due date is a timeline fact, not a per-row/per-task thing).
- [x] Rebuild `portal/proposals/show.blade.php` to embed the real print/PDF template (`admin/proposals/print.blade.php`) via a signed URL, plus a client-facing Download PDF route (`Portal\ProposalController::download()`).
- [x] Replace the purple gradient/pattern cover-header art with solid black, in both `admin/proposals/print.blade.php` and `admin/finance/print.blade.php` (shared `_cover-header.blade.php` partial).

## Verification

- [x] `php artisan test` — 157/157 passing.
- [x] Regression tests added: `tests/Feature/PortalAuthorizationTest.php` — index-route client-scoping, assignee-name/estimated-hours never rendered, blocked milestone's tasks hidden from the progress table (not just the card list), past-due unpaid milestone labeled "overdue" (never "paid").
- [ ] Manual click-through as a real client (magic-link or token) — nav tabs, full-width layout, Gantt table visual check, proposal iframe + PDF download, header art change on an actual exported PDF (not just the HTML template).

## Deferred / Open Gaps

- [ ] Milestone → specific `FinanceDocument` FK (payment_status is currently set manually by staff, not derived from a real invoice/payment record).
- [ ] Self-service online payment initiation from the portal.
- [ ] `cover_tone` appearance setting (plum/graphite/forest) no longer affects the cover-header background now that it's hardcoded solid black — if per-proposal tone customization for the header is still wanted, revisit (currently only affects the border-bottom accent color).
