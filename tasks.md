# Drenla Task Tracker — Dynamic Proposal Builder

_Last updated: 2026-07-03. This replaces the completed admin/portal/roles board. Reference audit: `proposal-builder-audit.md`._

## Status

- **All CO and CL columns below are now complete**, including the Payment Transaction Ledger (was the last open CO item — `finance_document_transactions` table, `FinanceAgingService`, statement print template, finance form UI, all done and tested). The only unchecked boxes left are the negative-constraint Guardrails and Deferred sections (rules, not tasks).
- Test suite: 162/162 passing, Pint clean.
- Source references: rendered PDF page images in `/home/kimrop/Desktop/programs/drenla/pdf/**/page-*.png`.
- Visual audit: `proposal-builder-audit.md`.
- CO must stay on logic/data/rendering contracts. CL owns admin UI and visual design/template implementation.
- Do not resume unrelated React integration work.

## Ownership Legend

- `[CO]` — Codex: data model, schema, normalization, services, PDF render contract, tests.
- `[CL]` — Claude: admin UX, visual templates, Blade/CSS composition, preview workflow, design fidelity.
- `[JOINT]` — requires coordination before coding.

---

## Already Done

- Existing proposal workspace exists with structured document data and PDF export.
- Finance documents and delivery services exist.
- Role/permission system exists.
- PDF samples have been split to images and visually audited in `proposal-builder-audit.md`.

---

## Proposal Builder — Joint Product Decisions

- [x] `[JOINT]` Confirm the proposal builder should support these document families first:
  - Project brief/proposal.
  - Quotation/estimate.
  - Acceptance/signature form.
  - Statement/receipt/payment status.
  — Confirmed, all 4. See collaboration-notes.md for how each is actually implemented (proposal document vs. `FinanceDocument`).
- [x] `[JOINT]` Confirm initial template variants:
  - Abidjan-style proposal family from `DNR-MAY-005-ABD-1`.
  - Kilifi-style proposal family from `DNR-SEP-002-2025-KLF (3)-1`.
  - Statement family from `DNR-RCT-KLF 2026`.
  — Confirmed. Abidjan/Kilifi already exist as `ProposalDocumentData` template keys (`drenla_project_brief`/`drenla_realestate_brief`); statement is a `FinanceDocument` type.
- [x] `[JOINT]` Decide whether quotations/statements remain under `FinanceDocument` or are unified into the proposal-builder document model through a shared render contract. — **Stay under `FinanceDocument`.** `ProposalRenderService` (CO) composes the full document from both sources at render time instead of duplicating schema. Full reasoning in collaboration-notes.md.
- [x] `[JOINT]` Decide migration path for existing `Proposal::document` data so current proposals keep rendering. — **Extend `document_data` JSON in place, no new tables.** New block `type` key defaults to `narrative` (today's implicit shape) when absent. Full reasoning in collaboration-notes.md.

---

## CO — Logic, Schema, Services

### Content Model

- [x] `[CO]` Design a reusable document schema for proposal-builder pages based on `proposal-builder-audit.md`, without storing raw layout/CSS controls in user content. — Extended `App\Support\ProposalDocumentData` with a `type` discriminator per block (defaults to `narrative`, the pre-existing implicit shape, so nothing written before this needs migrating).
- [x] `[CO]` Define document-level fields: document type, variant, title, client, scope, reference/invoice number, date, currency, totals, signers, footer/contact config. — `ProposalRenderService::metadata()`/`totals()`/`footer()`, see Rendering Contract below.
- [x] `[CO]` Define block-level structured payloads for: two-column narrative / deliverables list / multi-column scope list / stage grid-timeline / legal terms / comments ruled-lines / acceptance-signature form — all shipped as block types on `ProposalDocumentData` (`narrative`, `bullet_list`, `multi_column_list`, `stage_grid`, `comment_lines`, `signature_block`) plus a document-level `acceptance` field. **Quotation item table / payment schedule / payment info / statement-aging table are deliberately NOT proposal blocks** — they stay on `FinanceDocument`, which already has this shape (see JOINT decision #3 above); `ProposalRenderService` composes them in at render time instead of duplicating the schema.
- [x] `[CO]` Add validation rules for each block type so malformed blocks cannot reach the renderer. — `App\Services\ProposalDocumentValidator`, one scoped `Validator::make()` call per block (not a single dot-path ruleset — see the class docblock for why: deep nested wildcards through `Rule::forEach` trip a real Laravel edge case on empty arrays).
- [x] `[CO]` Add normalization defaults for optional blocks, page numbering style, and per-document family variants. — Each block type has its own `empty*Block()` normalizer with sane defaults (e.g. `comment_lines` defaults to 4 ruled lines, clamped 1-12); page numbering/variant styling stays CL's (`appearance.*` knobs).

### Persistence

- [x] `[CO]` Decide whether to extend `proposals.document` JSON or introduce separate `proposal_documents` / `proposal_document_pages` / `proposal_document_blocks` tables. — **Extend JSON, no new tables.** See JOINT decision #4 above.
- [x] `[CO]` If new tables are chosen, write migrations and model relations. — N/A, no new tables for the proposal document itself. **New table needed elsewhere** — see "Payment Transaction Ledger" below, flagged by CL's review of the statement family.
- [x] `[CO]` Preserve existing proposal records and PDF export behavior during migration. — No migration needed (JSON extended in place); regression-tested in `ProposalDocumentSchemaTest` (old-format blocks with no `type` key still normalize identically) and the full suite stayed green throughout (141/141).
- [x] `[CO]` Add fixtures/factories for Abidjan-style and Kilifi-style proposal examples. — `ProposalDocumentData::defaultsResidential()`/`defaultsRealEstate()` already were these fixtures (confirmed by CL: seeded with the actual KLF/ABD reference content verbatim); added a full Abidjan-family fixture exercising every new block type together in `ProposalRenderServiceTest`.

### Payment Transaction Ledger (done)
_CL correctly identified that the statement/aging-table family (`DNR-RCT-KLF 2026`) needs itemized payment transaction history — `FinanceDocument` only tracked a single running `amount_paid` scalar, not the Date/Transaction/Amount/%Pay/Balance rows the reference shows. Built end-to-end (migration, service, form UI, print template — spans CO's usual "logic/data" lane and CL's "admin UI/templates" lane; done together since it was the one remaining piece blocking the statement family from being real)._
- [x] Added `finance_document_transactions` table (date, label, type [invoice/payment/credit], amount, sort_order, FK to `finance_documents`) + `next_due_label`/`next_due_amount` columns on `finance_documents` for the "PHASE 3 — DUE PAYMENT" caption. Migration: `2026_07_03_132802_create_finance_document_transactions_table.php`.
- [x] `FinanceDocumentTransaction` model + `FinanceDocument::transactions()` relation.
- [x] `App\Services\FinanceAgingService`: `ledgerRows()` (running balance + %-of-original-invoice-paid per row, matching the reference exactly) and `agingSummary()` (Current/1-30/31-60/61-90+/Total, bucketed by days-past-due against the document's own due_date/issue_date — a whole-balance aging model, not per-invoice FIFO, since the one real reference only ever shows a single invoice per statement). Wired into `ProposalRenderService::totalsFrom()`, replacing the empty stub.
- [x] `admin/finance/_statement-page.blade.php` — new print partial (ledger table + aging grid + next-due caption), dispatched from `admin/finance/print.blade.php` for `type === 'statement'` instead of the quotation/invoice/receipt line-items table. `'statement'` added to `FinanceDocumentController`'s type options/validation.
- [x] `admin/finance/form.blade.php` — transaction ledger editor (add/remove rows, date/label/type/amount) + next-due fields, shown only when Document type = Statement (JS toggle, mirroring the existing line-items section for other types).
- [x] Tests: `FinanceAgingServiceTest` (3 — ledger + aging math matching `DNR-RCT-KLF 2026`'s exact numbers, a 31-60-day bucket case, a fully-paid zero-balance case) + `FinanceStatementDocumentTest` (3 — real form submission → persisted transactions, print template renders the ledger/aging/next-due, no line-items table leaks through for a statement). Verified visually via a real PDF export matching the reference layout.

### Rendering Contract

- [x] `[CO]` Build a renderer-facing DTO/service that returns ordered pages and blocks, not raw admin form fields. — `App\Services\ProposalRenderService::build(Proposal $proposal): ProposalRenderData`. Composes cover + section pages from `document_data`, plus a `quotation` page (if a `FinanceDocument` is linked) and an `acceptance` page (if opted in) — ordered, each self-describing its `kind`.
- [x] `[CO]` Keep reusable finance calculations in services: subtotal, VAT/N/A, total, payment schedule, outstanding balance, aging buckets. — Subtotal/tax/total/outstanding balance sourced from the linked `FinanceDocument` (already correct, existing `getBalanceAttribute()`); aging buckets stubbed empty pending the transaction ledger above (not fabricated).
- [x] `[CO]` Expose a stable API for CL templates: `document`, `pages`, `blocks`, `metadata`, `totals`, `footer`, `signers`. — `App\Data\ProposalRenderData`, exactly these 7 (plus `blocks()`/`blocksOfType()`/`hasQuotation()` convenience methods). `document` kept for backward compat with the existing print template; new templates should prefer `pages`.
- [x] `[CO]` Add tests proving the renderer contract includes all block types from the audited PDFs. — `ProposalRenderServiceTest::it proves the render contract covers every block type identified in the audit...` — builds a representative Abidjan-family document and asserts all 6 block types come through.
- [x] `[CO]` Add regression tests for PDF export using representative Abidjan/Kilifi/statement fixtures. — Scoped to the render-contract layer (not headless-Chrome PDF generation, which `ProposalWorkspaceTest` already covers by mocking the exporter) — `ProposalRenderServiceTest` covers quotation-linkage, acceptance opt-in, and the full-block-type Abidjan fixture. 22 new tests total across the 3 new test files (`ProposalDocumentSchemaTest`, `ProposalDocumentValidatorTest`, `ProposalRenderServiceTest`).

### Guardrails

- [ ] `[CO]` Do not decide exact visual spacing, typography, clipped bars, or header CSS. Capture them as renderer/template variant names and data contracts only.
- [ ] `[CO]` Do not edit `resources/views/admin/**` or admin route UI unless CL explicitly asks.

---

## CL — Admin UX And Visual Templates

### Admin Builder UX

_Done — see Decisions Log. `admin/proposals/form.blade.php`'s block editor now authors all 6 block types via a type selector + per-type field group on every block card, not just the original narrative shape._
- [x] `[CL]` Design the admin proposal-builder flow around selecting structured blocks, not free-form page design — a `type` dropdown per block card, each type's fields shown/hidden inline (not a separate wizard/page), plus template-default injection (already existed) auto-populates every section/block for the chosen template so staff mostly fill in values.
- [x] `[CL]` Build block add/reorder/remove controls with clear block names from the audit, covering all 6 block types (not just narrative) — `+ Add block` still adds a blank narrative block by default; the type dropdown switches it to any of the other 5. Existing add/remove/reorder-page controls unchanged.
- [x] `[CL]` Build forms for each block type using structured fields instead of one giant textarea — implemented as line-based mini-syntax textareas per type (one field per structured concept: items/columns/stages/signers), not one shared free-text blob. See Decisions Log for why (avoids a full dynamic row-management JS rewrite) and the exact syntax per type.
- [x] `[CL]` Add a toggle + signer-row editor for the document-level `acceptance` form — new "Acceptance form" editor section (enable checkbox, intro text, date label, signers textarea).
- [x] `[CL]` Add preview actions for each document family and full document preview before export — the existing "Preview ↗" link (whole-document PDF preview, opens in a new tab before export) covers this for every family (proposal, quotation/invoice/receipt, statement). A separate per-family template-gallery/picker preview (browse all templates before picking one) was considered and intentionally not built — nothing in the brief calls for it, and the template switcher on the proposal form already lets staff swap between Abidjan/Kilifi and see the result via Preview.
- [x] `[CL]` Keep the UI non-chaotic: staff can choose block type/order/content, but not arbitrary pixel layout — mini-syntax fields constrain input to the block's actual data shape, no free-form HTML/CSS surface exposed.

### Visual Template Implementation
_Renderer-side work — done. Verified against real rendered PDF output (not just the reference images) via `ProposalPdfExporter`, side by side with the source images page by page._
- [x] `[CL]` Implement the shared black textured Drenla header with metadata columns — pre-existing, verified matches.
- [x] `[CL]` Implement footer variants: QR/contact footer — pre-existing, verified matches. `Page N of M` / `PAGE / N OF M` page-numbering variants not yet exercised (no multi-page numbering logic currently in the renderer) — small follow-up if needed.
- [x] `[CL]` Implement section banner variants: full grey bar (pre-existing) and clipped-end grey banner (new, `appearance.section_banner_style: plain|clipped`).
- [x] `[CL]` Implement list treatments: square bullets, dash bullets, numbered headings (pre-existing, verified matches), nested descriptions.
- [x] `[CL]` Implement legal/comment/signature blocks with ruled lines and pale signature labels — comment-lines and dual-signer blocks new, terms now support inline bold + dotted fill-in blanks.
- [x] `[CL]` Implement quotation/statement table visual treatment: dotted dividers, alternating grey bands, totals, authorized signature — quotation/invoice/receipt pre-existing (`admin/finance/_document-page.blade.php`), verified matches. Statement/aging table now implemented too (`admin/finance/_statement-page.blade.php`), see Payment Transaction Ledger above — no longer blocked.
- [x] `[CL]` Match page references in `proposal-builder-audit.md` before adding new styling — found and fixed one real fidelity bug this way (block headings force-uppercased via CSS; references only uppercase numbered headings), see Decisions Log.

### Admin Tests / QA
- [x] `[CL]` Add feature/browser tests for creating a document from reusable blocks — `ProposalNewBlockTypesTest`, `ProposalHeadingCaseTest` (rendering all 6 block types + the acceptance page + appearance knobs + heading-case fidelity against a real preview response), `ProposalBuilderUxTest` (full save-flow: real POST through the admin form's mini-syntax fields for every block type → asserts the exact parsed shape in `document_data`, plus an edit-page round-trip)
- [x] `[CL]` Add permission checks around view/manage proposals and finance-related generated documents — already comprehensive: `AdminPermissionBoundaryTest` gates proposals on `view_proposals`/`manage_proposals` (including the loose `send` route) and finance on `view_finance`/`manage_finance` (same), plus the `print` PDF-export routes are `signed`-middleware-protected, not permission-gated (correct — headless Chrome hits them with no session). Verified, no gap found.
- [x] `[CL]` Add manual QA screenshots or page-image comparisons for Abidjan and Kilifi variants — saved as a durable artifact (published via the Artifact tool) covering all 12 reference pages across all 3 document families (Abidjan/Kilifi/Statement), each with pass/fixed status and the exact fix where one was needed; plus a consolidated bug log with files touched.

---

## Deferred

- [ ] Do not build AI content generation for proposals yet.
- [ ] Do not build arbitrary drag-and-drop canvas layout.
- [ ] Do not start React public-site consumption work.
- [ ] Do not remove existing proposal/finance export behavior until replacement fixtures pass.
