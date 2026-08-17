# Drenla Proposal Builder Audit

Date: 2026-07-02

Source images are in `/home/kimrop/Desktop/programs/drenla/pdf/{pdf-name}/page-N.png`.

## Purpose

All reviewed files are proposal-family documents, but they are not one flat template. The system should generate them from reusable document parts:
- A shared Drenla header/footer identity system.
- Proposal/project brief content pages.
- Scope/detail section pages.
- Work process and terms pages.
- Quotation/estimate pages.
- Acceptance/signature pages.
- Statement/receipt pages.

The admin should let staff compose these parts dynamically without creating chaotic free-form design controls. Staff should choose blocks and edit content; the renderer should own structure, spacing, rules, bullets, dotted lines, page numbers, and signature layout.

## Shared Visual System

Common patterns across the samples:
- Top header is usually black with a purple horizontal-line texture on the left and faint vertical grid divisions.
- Header contains the Drenla logo plus metadata columns: document type, client, scope, invoice/reference number, date.
- White body surface, black/grey text, generous margins.
- Grey section bars divide major content blocks.
- Some inner proposal pages use a light watermark Drenla logo at top left.
- KLF inner pages add a faint top-right tagline: `Unlocking Great Ideas`.
- Lists use square bullets, short dash bullets, or numbered headings depending on block type.
- Tables use dotted horizontal leaders, alternating light-grey item bands, and strong total rows.
- Comment areas and signature areas use thin horizontal rules.
- Footer usually includes QR code, `drenla.com`, contact details, address, and a long light-grey horizontal rule.
- Page numbering varies by family: `Page 2 of 6`, `2 OF 5`, or no page number on finance/acceptance pages.

## Page Reference Notes

### DNR-MAY-005-ABD-1

`DNR-MAY-005-ABD-1/page-1.png`
- Project brief cover/summary.
- Black textured header with five metadata columns.
- Large proposal title on white.
- Grey bars for `PROJECT DESCRIPTION` and `WORK SCOPE`.
- Two-column text blocks for Brief/Key Objective and Work Scope/Key Deliverables.
- Numbered list on left, numbered deliverables on right.
- Footer has QR code, long grey rule, email/phone/address.

`DNR-MAY-005-ABD-1/page-2.png`
- Inner section page: `SECTION A. DEVELOPING THE BRAND IDENTITY`.
- Faint Drenla watermark logo at top.
- Full-width grey section bar.
- Text-heavy page with numbered subsections and square bullet lists.
- Two-column grouped lists for Print Visuals / Digital Visuals / Brand Assets.
- Client comments area with multiple ruled lines.
- Two signature lines at bottom, plus `Page 2 of 6`.

`DNR-MAY-005-ABD-1/page-3.png`
- Scope list page for exterior/interior visualization.
- Faint logo, large whitespace, black headings.
- Exterior bullet list at top.
- Interior lists split into two columns: Block Type 001 and Block Type 002.
- Comment ruled lines and dual signature lines.
- `Page 3 of 6`.

`DNR-MAY-005-ABD-1/page-4.png`
- Deliverables/detail and animation deliverables page.
- Top deliverables list with square bullets.
- Thin horizontal rule separates sections.
- `3D-ANIMATION VIDEO DELIVERABLES` heading.
- Numbered subsections for exterior and interior visualization frames.
- Comment ruled lines and dual signature lines.
- `Page 4 of 6`.

`DNR-MAY-005-ABD-1/page-5.png`
- Work stage timeline plus legal terms.
- Stage grid: two columns for stages 1-4, a single lower row for stage 5.
- Thin horizontal separators between stage rows.
- Durations emphasized in bold (`2 WEEK`, `3 WEEKS`, etc.).
- Grey bar `SECTION E: DESIGN TERMS OF ENGAGEMENT`.
- Numbered legal terms with bold inline emphasis.
- Contains dotted fill-in placeholders in legal acceptance text.
- `Page 5 of 6`.

`DNR-MAY-005-ABD-1/page-6.png`
- Quote estimate page.
- Black textured quote header with metadata.
- Three metadata fields below header: quotation number, date, contact name.
- Item table with dotted header divider, small diagonal mark icons, alternating grey item bands, nested item descriptions.
- Totals block aligned right.
- Payment schedule grey section bar and payment info block.
- Authorized signature image/rule at lower right.
- QR footer and contact footer.

`DNR-MAY-005-ABD-1/page-7.png`
- Acceptance/signature form.
- Black textured quote header.
- Large grey callout: read terms before signing.
- Two signer blocks: client contact and Drenla Ventures.
- Each signer block has name, phone, role/company, pale `SIGNATURE` label, and signature line.
- Large whitespace; month/year at lower right.
- Footer includes QR, thank-you line, grey rule, contact details.

### DNR-RCT-KLF 2026

`DNR-RCT-KLF 2026/page-1.png`
- Statement/receipt page, not a full proposal brief.
- Same black textured header but document type is `STATEMENT`.
- Metadata columns: client, scope, invoice number, date.
- Body has quotation number, invoice date, contact person.
- Finance statement table columns: Date, Transaction, Amount, % Pay, Balance.
- Aging summary area uses dotted horizontal rules and repeated bucket labels.
- Total amount and due amount emphasized.
- Authorized signature image/rule at lower right.
- QR/footer/contact system.

### DNR-SEP-002-2025-KLF (3)-1

`DNR-SEP-002-2025-KLF (3)-1/page-1.png`
- Project brief cover/summary for Greenheart Kilifi.
- Same black textured header family.
- Large all-caps project title.
- Grey bars use title case (`Project Description`, `Work Scope`) instead of all caps.
- Brief and Key Objective in two columns.
- Key Deliverables section with numbered rich paragraphs in two columns.
- Footer QR/contact system.

`DNR-SEP-002-2025-KLF (3)-1/page-2.png`
- Inner scope page with a distinct lighter header style.
- Faint Drenla logo top left and faint `Unlocking Great Ideas` top right.
- Grey clipped-end section banner: `SECTION A. SCOPE OF 3D VISUALIZATION`.
- Thin horizontal rules separate content zones.
- Exterior list, then interior list split into three columns: Ground Floor, First Floor, Guest Wing.
- Square bullet markers used both for group labels and list items.
- Lower sections for `3D VISUALIZATION` and `DETAILED 2D-WORKING DRAWINGS & RENDERED PLANS`.
- Footer page label: `PAGE` left, `2 OF 5` right.

`DNR-SEP-002-2025-KLF (3)-1/page-3.png`
- Work process and terms page.
- Faint logo/tagline top.
- Clipped-end grey section banner: `SECTION B: WORK PROCESS`.
- Four-stage grid in two columns with dash bullet lists and bold stage durations.
- Second clipped-end grey banner: `TERMS OF ENGAGEMENT`.
- Numbered legal terms with bold inline emphasis.
- Footer page label: `PAGE` left, `3 OF 5` right.

`DNR-SEP-002-2025-KLF (3)-1/page-4.png`
- Quotation page.
- Black textured header with `QUOTATION`.
- Quotation metadata row.
- Item table with diagonal mark icons, dotted header rule, compact descriptions, cost/qty/amount columns.
- Totals block aligned right.
- Bold note: fee provided is VAT exclusive.
- Grey `PAYMENT TERMS` section bar.
- Payment info block and authorized signature.
- QR/footer/contact system.

`DNR-SEP-002-2025-KLF (3)-1/page-5.png`
- Acceptance form.
- Black textured header with `ACCEPTANCE FORM`.
- Grey callout to read terms before signing.
- Client contact and Drenla signer blocks.
- Pale `SIGNATURE` labels and thin signature rules.
- Month/year lower right.
- Footer `PAGE` left, `5 OF 5` right, with thank-you line.

## Reusable Block Types Needed

Core document parts:
- Document header: black textured metadata header.
- Inner page masthead: faint logo/tagline.
- Footer: QR/contact footer and page-number footer variants.
- Section banner: plain grey full-width bar and clipped-end grey banner.
- Two-column narrative block.
- Multi-column deliverables block.
- Numbered rich deliverables block.
- Bullet list block with square bullets.
- Dash-list stage block.
- Stage grid/timeline block.
- Legal terms block with numbered clauses and inline bold fragments.
- Comment ruled-lines block.
- Signature block and acceptance form.
- Quotation line-item table with nested descriptions.
- Payment schedule/terms block.
- Payment info block.
- Statement/payment-aging table.

## System Implications

The proposal builder should not expose raw layout controls for each page. It should expose:
- Document type: project brief, quotation, acceptance, statement.
- Variant/family: Abidjan-style proposal, Kilifi-style proposal, finance statement.
- Ordered sections.
- Section block type.
- Structured content fields for each block.
- Optional comments/signatures/payment sections.

The renderer owns:
- Headers, metadata columns, texture, bars, rules, bullets, dotted lines, footer, page numbering, table treatment, and signature placement.

The admin owns:
- Selecting/ordering blocks.
- Editing text, deliverables, stage entries, payment terms, finance items, and signer details.
- Previewing generated pages and exporting PDF.
