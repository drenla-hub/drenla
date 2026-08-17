@php
    $logoPath = base_path('../frontend/src/assets/drenla-logo-white.png');

    $embed = function (string $path, string $mime): ?string {
        if (! file_exists($path)) return null;
        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    };

    $logoData = $embed($logoPath, 'image/png');
    $qrData   = $embed(public_path('drenla-qr.svg'), 'image/svg+xml');

    $footerEmail    = \App\Models\SiteSetting::getValue('contact_email',   'hello@drenla.com');
    $footerPhone    = \App\Models\SiteSetting::getValue('primary_phone',   '');
    $footerLocation = \App\Models\SiteSetting::getValue('office_location', 'Nairobi, Kenya');
    $footerAddress  = \App\Models\SiteSetting::getValue('office_address',  '');

    $mulishLightPath   = base_path('../frontend/public/fonts/Mulish-Light.ttf');
    $mulishRegularPath = base_path('../frontend/public/fonts/Mulish-Regular.ttf');
    $mulishBoldPath    = base_path('../frontend/public/fonts/Mulish-Bold.ttf');
    $mulishLight   = $embed($mulishLightPath,   'font/ttf');
    $mulishRegular = $embed($mulishRegularPath, 'font/ttf');
    $mulishBold    = $embed($mulishBoldPath,    'font/ttf');

    $docDate = $document->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y');
    $docLabel = strtoupper($document->type);
    $docClientName = $document->client?->name ?? '';
    // _cover-header falls back to $proposal->reference_number when this is null —
    // there's no $proposal in this view's scope, so always resolve a concrete value here.
    $docReference = $document->reference_number ?: '—';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->reference_number ?: ucfirst($document->type) }}</title>

    @vite('resources/css/app.css')

    <style>
        html, body {
            background-color: {{ $previewMode ? '#111111' : '#ffffff' }} !important;
            color: #0b0d10 !important;
        }
        body { padding: {{ $previewMode ? '24px' : '0' }}; font-family: 'Mulish', sans-serif; }

        @if ($mulishLight)
        @font-face { font-family: 'Mulish'; src: url('{{ $mulishLight }}') format('truetype'); font-weight: 300; }
        @endif
        @if ($mulishRegular)
        @font-face { font-family: 'Mulish'; src: url('{{ $mulishRegular }}') format('truetype'); font-weight: 400; }
        @endif
        @if ($mulishBold)
        @font-face { font-family: 'Mulish'; src: url('{{ $mulishBold }}') format('truetype'); font-weight: 700; }
        @endif

        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print {
            body { padding: 0 !important; background: #fff !important; }
            .preview-toolbar { display: none !important; }
            .document { width: auto; min-height: auto; box-shadow: none; }
        }

        .document {
            width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff;
            padding-bottom: 70px;
            box-shadow: {{ $previewMode ? '0 40px 120px rgba(0,0,0,0.45)' : 'none' }};
        }

        /* ── Cover header (shared visual language with the proposal PDF) ─── */
        .cover-header {
            position: relative; min-height: 220px;
            padding: 38px 60px 0; background: #000; overflow: hidden;
            border-bottom: 2px solid rgba(255,255,255,0.88);
        }
        .brand { position: relative; z-index: 2; width: 228px; }
        .brand img { display: block; width: 100%; height: auto; }
        .meta-rail {
            position: absolute; left: 60px; right: 60px; bottom: 0; z-index: 2;
            display: grid; grid-template-columns: 1.25fr 0.85fr 1.15fr 0.8fr 0.55fr;
        }
        .meta-item { position: relative; min-height: 100px; padding: 0 18px 24px 0; color: #fff; }
        .meta-item + .meta-item { padding-left: 20px; }
        .meta-item + .meta-item::before {
            content: ''; position: absolute; left: 0; bottom: 0;
            width: 2px; background: rgba(255,255,255,0.92);
            height: 100%;
        }
        .meta-item:nth-child(3)::before,
        .meta-item:nth-child(5)::before {
            height: 52%;
        }
        .meta-label { margin: 0; font-size: 17px; font-weight: 700; letter-spacing: -0.02em; text-transform: uppercase; }
        .meta-value { margin-top: 10px; font-size: 13px; line-height: 1.15; color: rgba(255,255,255,0.92); white-space: pre-line; text-transform: uppercase; }
        .meta-value.scope { font-size: 12px; line-height: 1.28; letter-spacing: 0.01em; }

        /* ── Quotation / invoice / receipt page ────────────────────────────── */
        .quot-body { padding: 28px 76px 36px; }
        .quot-meta-row {
            display: grid; grid-template-columns: 1fr 1fr 1fr;
            gap: 0; border-bottom: 1.5px solid #dfe1e4; padding-bottom: 16px; margin-bottom: 16px;
        }
        .quot-meta-label { font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(17,19,23,0.4); margin-bottom: 4px; }
        .quot-meta-value { font-size: 13px; font-weight: 600; color: #111317; }
        .quot-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .quot-table thead tr { border-bottom: 1.5px solid #111317; }
        .quot-table th {
            padding: 6px 8px; text-align: left;
            font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(17,19,23,0.55);
        }
        .quot-table th.r { text-align: right; }
        .quot-table tbody tr { border-bottom: 1px solid #ebebec; }
        .quot-table td { padding: 10px 8px; vertical-align: top; }
        .quot-table td.r { text-align: right; font-size: 13px; color: #111317; white-space: nowrap; }
        .quot-item-row { display: flex; align-items: flex-start; gap: 8px; }
        /* Diagonal mark beside a priced line item — a short rotated stroke, not a
           checkmark. Hidden (not removed) on descriptive sub-item rows so the
           title text still lines up with the priced items above it. */
        .quot-check {
            flex-shrink: 0; width: 10px; height: 15px; margin-top: 1px; position: relative;
        }
        .quot-check::before {
            content: ''; position: absolute; left: 4px; top: 0; width: 2px; height: 100%;
            background: #333; transform: rotate(18deg);
        }
        .quot-item-title { font-size: 13px; font-weight: 700; color: #111317; line-height: 1.3; }
        .quot-item-desc  { font-size: 11px; color: rgba(17,19,23,0.55); line-height: 1.4; margin-top: 2px; }
        .quot-table tfoot td { padding: 6px 8px; font-size: 13px; }
        .quot-table tfoot .quot-subtotal td { border-top: 1.5px solid #dfe1e4; font-weight: 600; }
        .quot-table tfoot .quot-total td { font-weight: 700; font-size: 14px; color: #111317; border-top: 1.5px solid #111317; }
        .quot-table tfoot td.r { text-align: right; }
        .quot-vat-note {
            font-size: 11px; font-weight: 600; color: rgba(17,19,23,0.55);
            margin: 6px 0 18px; letter-spacing: 0.01em;
        }
        .quot-section-head {
            font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
            color: #111317; margin-bottom: 6px; margin-top: 16px; padding: 6px 0 6px;
            border-bottom: 1px solid #dfe1e4;
        }
        .quot-section-body {
            font-size: 12px; line-height: 1.65; color: rgba(17,19,23,0.78); white-space: pre-line;
        }
        .quot-sign-area {
            margin-top: 28px; display: flex; justify-content: flex-end;
        }
        .quot-sign-box {
            text-align: center; width: 160px;
            border-top: 1px solid #111317; padding-top: 6px;
            font-size: 10px; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: rgba(17,19,23,0.45);
        }

        /* ── Statement aging summary (statement type only) ───────────────────── */
        .statement-aging-grid {
            display: grid; grid-template-columns: repeat(5, 1fr); gap: 0 16px;
            margin: 24px 0 8px; padding: 10px 0;
            border-top: 1px dashed rgba(17,19,23,0.35); border-bottom: 1px dashed rgba(17,19,23,0.35);
        }
        .statement-aging-head {
            grid-row: 1; font-size: 10px; font-weight: 700; letter-spacing: 0.06em;
            text-transform: uppercase; color: #111317; padding-bottom: 8px;
            border-bottom: 1px dashed rgba(17,19,23,0.25);
        }
        .statement-aging-value { grid-row: 2; font-size: 13px; color: rgba(17,19,23,0.7); padding-top: 8px; }
        .statement-aging-total { font-weight: 700; color: #111317; }
        .statement-next-due {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 14px; padding-top: 10px; border-top: 1px solid #dfe1e4;
            font-size: 12px; font-weight: 600; color: rgba(17,19,23,0.75);
        }

        /* ── Fixed footer ──────────────────────────────────────────────────── */
        .pdf-footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            display: grid; grid-template-columns: 60px 1fr 1fr;
            align-items: center; gap: 20px;
            padding: 9px 76px; background: #fff;
            border-top: 1.5px solid #d4d6d9;
            font-family: 'Mulish', sans-serif;
        }
        .pdf-footer-qr { display: flex; flex-direction: column; align-items: center; gap: 3px; }
        .pdf-footer-qr img { display: block; width: 44px; height: 44px; }
        .pdf-footer-qr-label {
            font-size: 8px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(15,17,20,0.4);
        }
        .pdf-footer-col { font-size: 11px; line-height: 1.7; color: rgba(15,17,20,0.5); letter-spacing: 0.01em; }
        .pdf-footer-col strong { font-weight: 600; color: rgba(15,17,20,0.65); display: block; }
    </style>
</head>
<body>

@if ($previewMode)
    <div class="flex items-center justify-between gap-4 mb-4 px-[18px] py-[14px] border border-white/10 bg-black/90 text-white/80" style="width:210mm;margin-left:auto;margin-right:auto;">
        <div class="text-[11px] uppercase tracking-[0.15em]">Previewing <span class="text-white/60">{{ $document->reference_number ?: 'unsaved reference' }}</span></div>
        <div class="flex gap-2">
            <a href="{{ route('admin.finance.edit', $document) }}"
               class="border border-white/15 px-4 py-2.5 text-[11px] uppercase tracking-[0.18em] text-white/70 hover:border-white/30 hover:text-white transition-colors">
                ← Back to editor
            </a>
            <a href="{{ route('admin.finance.export', $document) }}"
               class="border border-white bg-white px-4 py-2.5 text-[11px] uppercase tracking-[0.18em] text-black hover:bg-white/90 transition-colors">
                Download PDF
            </a>
        </div>
    </div>
@endif

<div class="document">

    @include('admin.proposals._cover-header', [
        'metalRailData' => [
            'document_label'  => $docLabel,
            'client_label'    => 'CLIENT',
            'scope_label'     => 'REFERENCE',
            'reference_label' => $docLabel.' #',
            'date_label'      => 'DATE',
            'client_name'     => $docClientName,
            'scope'           => $document->client?->company_name ?? '',
            'reference_value' => $docReference,
            'date_value'      => $docDate,
        ],
    ])

    @if ($document->type === 'statement')
        @include('admin.finance._statement-page', ['document' => $document])
    @else
        @include('admin.finance._document-page', ['document' => $document])
    @endif

</div>{{-- /.document --}}

<div class="pdf-footer">
    <div class="pdf-footer-qr">
        @if ($qrData)<img src="{{ $qrData }}" alt="drenla.com">@endif
        <span class="pdf-footer-qr-label">drenla.com</span>
    </div>
    <div class="pdf-footer-col">
        @if ($footerEmail)<strong>{{ $footerEmail }}</strong>@endif
        @if ($footerPhone){{ $footerPhone }}@endif
    </div>
    <div class="pdf-footer-col">
        @if ($footerAddress)<strong>{{ $footerAddress }}</strong>@endif
        @if ($footerLocation){{ $footerLocation }}@endif
    </div>
</div>

</body>
</html>
