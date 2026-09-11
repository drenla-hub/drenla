@php
    $logoPath = base_path('../frontend/src/assets/drenla-logo-white.png');

    $embed = function (string $path, string $mime): ?string {
        if (! file_exists($path)) return null;
        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    };

    $logoData = $embed($logoPath, 'image/png');
    $qrData   = $embed(public_path('drenla-qr.svg'), 'image/svg+xml');

    // Contact details from site settings
    $footerEmail    = \App\Models\SiteSetting::getValue('contact_email',   'hello@drenla.com');
    $footerPhone    = \App\Models\SiteSetting::getValue('primary_phone',   '');
    $footerLocation = \App\Models\SiteSetting::getValue('office_location', 'Nairobi, Kenya');
    $footerAddress  = \App\Models\SiteSetting::getValue('office_address',  '');

    // Embed Mulish fonts for reliable offline-capable PDF rendering
    $mulishLightPath   = base_path('../frontend/public/fonts/Mulish-Light.ttf');
    $mulishRegularPath = base_path('../frontend/public/fonts/Mulish-Regular.ttf');
    $mulishBoldPath    = base_path('../frontend/public/fonts/Mulish-Bold.ttf');
    $mulishLight   = $embed($mulishLightPath,   'font/ttf');
    $mulishRegular = $embed($mulishRegularPath, 'font/ttf');
    $mulishBold    = $embed($mulishBoldPath,    'font/ttf');

    $issueDate  = $proposal->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y');
    $scopeLines = preg_split('/\r\n|\r|\n/', (string) ($document['header']['scope'] ?? '')) ?: [];
    $appearance = array_merge([
        'cover_tone' => 'plum',
        'hero_surface' => 'mist',
        'section_strip' => 'stone',
        'content_density' => 'comfortable',
    ], is_array($document['appearance'] ?? null) ? $document['appearance'] : []);

    $coverThemes = [
        'plum' => ['bg' => '#130e24', 'border' => 'rgba(255,255,255,0.88)'],
        'graphite' => ['bg' => '#15181d', 'border' => 'rgba(255,255,255,0.82)'],
        'forest' => ['bg' => '#10201b', 'border' => 'rgba(212,232,224,0.88)'],
    ];
    $heroSurfaces = [
        'mist' => '#f7f7f7',
        'white' => '#ffffff',
        'warm' => '#f3eee5',
    ];
    $stripTones = [
        'stone' => ['bg' => '#dfe1e4', 'text' => 'rgba(15,17,20,0.84)'],
        'sand' => ['bg' => '#e6ddcf', 'text' => 'rgba(33,28,23,0.84)'],
        'slate' => ['bg' => '#d6dbe2', 'text' => 'rgba(22,28,34,0.84)'],
    ];
    $densityMap = [
        'comfortable' => ['hero' => '42px 76px 48px', 'page' => '34px 76px 44px', 'narrative' => '28px 76px 36px', 'gap' => '48px'],
        'compact' => ['hero' => '34px 68px 38px', 'page' => '26px 68px 34px', 'narrative' => '22px 68px 28px', 'gap' => '36px'],
    ];

    $coverTheme = $coverThemes[$appearance['cover_tone'] ?? 'plum'] ?? $coverThemes['plum'];
    $heroSurface = $heroSurfaces[$appearance['hero_surface'] ?? 'mist'] ?? $heroSurfaces['mist'];
    $stripTone = $stripTones[$appearance['section_strip'] ?? 'stone'] ?? $stripTones['stone'];
    $density = $densityMap[$appearance['content_density'] ?? 'comfortable'] ?? $densityMap['comfortable'];

    /**
     * Convert plain-text body into styled HTML for the PDF.
     *
     * Pre-pass — "column grid" detection:
     *   If EVERY non-blank line is "HEADER: item, item, item" (2–4 lines total)
     *   the whole body renders as a three-column dark-header table — NO outer card border.
     *
     * Line rules (fallback):
     *  • Blank line              → spacer
     *  • "• …" / "■ …" / "- …" → bullet (original dot character preserved)
     *  • "STAGE N …"             → stage sub-heading
     *  • "N. CAPS…"              → numbered heading
     *  • Line ending with ":"    → bold lead-in sentence
     *  • All-caps ≥ 4 chars     → grey full-bleed sub-band heading
     *  • Normal                 → paragraph
     */
    $renderBody = function (?string $text): string {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $lines    = preg_split('/\r\n|\r|\n/', trim($text));
        $nonEmpty = array_values(array_filter($lines, fn($l) => trim($l) !== ''));

        // ── Column grid pre-pass ──────────────────────────────────────────
        if (count($nonEmpty) >= 2 && count($nonEmpty) <= 4) {
            $allCols = true;
            foreach ($nonEmpty as $l) {
                if (! preg_match('/^(.+?):\s+(.+)/', trim($l), $cm) || ! str_contains($cm[2], ',')) {
                    $allCols = false; break;
                }
            }
            if ($allCols) {
                $cols = [];
                foreach ($nonEmpty as $l) {
                    preg_match('/^(.+?):\s+(.+)/', trim($l), $cm);
                    $items = array_values(array_filter(array_map('trim', explode(',', $cm[2]))));
                    $cols[] = ['header' => trim($cm[1]), 'items' => $items];
                }
                $n   = count($cols);
                $out = '<div class="body-col-grid" style="grid-template-columns:repeat('.$n.',1fr);">';
                foreach ($cols as $col) {
                    $out .= '<div class="body-col">';
                    $out .= '<div class="body-col-head"><span class="body-col-sq">■</span>'.htmlspecialchars(mb_strtoupper($col['header'])).'</div>';
                    foreach ($col['items'] as $item) {
                        // A literal "---" item is a deliberate visual gap between two
                        // sub-groups within one column (e.g. Guest Wing's rooms vs.
                        // amenities in the reference documents) — not a real bullet.
                        if ($item === '---') {
                            $out .= '<div class="body-col-group-gap"></div>';
                        } elseif ($item) {
                            $out .= '<div class="body-col-item"><span class="bci-dot">■</span><span>'.htmlspecialchars($item).'</span></div>';
                        }
                    }
                    $out .= '</div>';
                }
                $out .= '</div>';
                return $out;
            }
        }

        // ── Line-by-line fallback ─────────────────────────────────────────
        $html   = '';
        $inList = false;

        $closeList = function () use (&$html, &$inList) {
            if ($inList) { $html .= '</div>'; $inList = false; }
        };

        foreach ($lines as $raw) {
            $line = rtrim($raw);

            if (trim($line) === '') {
                $closeList();
                $html .= '<div style="height:6px"></div>';
                continue;
            }

            // Bullet: •  ·  ■  (preserve original dot)
            if (preg_match('/^([•·■])\s+(.+)/', $line, $m)) {
                if (!$inList) { $html .= '<div class="bullet-list">'; $inList = true; }
                $dot = $m[1] === '■' ? '■' : '•';
                $cls = $m[1] === '■' ? 'bullet-dot sq' : 'bullet-dot';
                $html .= '<div class="bullet-item"><span class="'.$cls.'">'.$dot.'</span><span>'.htmlspecialchars($m[2]).'</span></div>';
                continue;
            }

            // Dash bullet: "- text"
            if (preg_match('/^-\s+(.+)/', $line, $m)) {
                if (!$inList) { $html .= '<div class="bullet-list">'; $inList = true; }
                $html .= '<div class="bullet-item"><span class="bullet-dot">–</span><span>'.htmlspecialchars($m[1]).'</span></div>';
                continue;
            }

            $closeList();

            // STAGE N sub-heading in body text
            if (preg_match('/^STAGE\s+\d+/i', trim($line))) {
                $html .= '<div class="stage-head">'.htmlspecialchars(trim($line)).'</div>';
                continue;
            }

            // Numbered + ALL-CAPS heading: "1. EXTERIOR VISUALIZATION"
            if (preg_match('/^(\d+\.\s+)([A-Z][A-Z0-9 &\+\-\/\(\)\.]+)$/', trim($line), $m)) {
                $html .= '<h3 class="numbered-head"><span class="num">'.htmlspecialchars($m[1]).'</span>'.htmlspecialchars($m[2]).'</h3>';
                continue;
            }

            // ALL-CAPS sub-band heading (≥ 4 chars)
            if (preg_match('/^[A-Z][A-Z0-9 &\+\-\/\(\)\.\:]{3,}$/', trim($line))) {
                $html .= '<h4 class="caps-head">'.htmlspecialchars(trim($line)).'</h4>';
                continue;
            }

            // Bold lead-in (ends with ":")
            if (preg_match('/[:]$/', trim($line))) {
                $html .= '<p class="body-intro">'.htmlspecialchars(trim($line)).'</p>';
                continue;
            }

            $html .= '<p class="body-p">'.htmlspecialchars(trim($line)).'</p>';
        }

        $closeList();
        return $html;
    };

    /**
     * Terms/legal clause text needs two inline patterns the line-based
     * $renderBody doesn't handle: **bold** phrases mid-sentence, and a literal
     * run of dots/underscores as a fill-in-the-blank line (e.g. "to
     * .................... (hereafter referred to as the Client)"). Escapes
     * first, then re-introduces exactly these two markers — never raw HTML.
     */
    $renderTerms = function (?string $text): string {
        $text = (string) $text;

        $escaped = htmlspecialchars($text);
        $escaped = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $escaped);
        $escaped = preg_replace('/(\.{4,}|_{4,})/', '<span class="terms-dots"></span>', $escaped);

        return $escaped;
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $proposal->reference_number ?: $proposal->title }}</title>

    @vite('resources/css/app.css')

    <style>
        /* ── Base overrides (unlayered beats @layer base from app.css) ───── */
        html, body {
            background-color: {{ $previewMode ? '#111111' : '#ffffff' }} !important;
            color: #0b0d10 !important;
        }
        body { padding: {{ $previewMode ? '24px' : '0' }}; font-family: 'Mulish', sans-serif; }

        /* ── Embedded Mulish (no external request needed) ────────────────── */
        @if ($mulishLight)
        @font-face { font-family: 'Mulish'; src: url('{{ $mulishLight }}') format('truetype'); font-weight: 300; }
        @endif
        @if ($mulishRegular)
        @font-face { font-family: 'Mulish'; src: url('{{ $mulishRegular }}') format('truetype'); font-weight: 400; }
        @endif
        @if ($mulishBold)
        @font-face { font-family: 'Mulish'; src: url('{{ $mulishBold }}') format('truetype'); font-weight: 700; }
        @endif

        /* ── Print settings ──────────────────────────────────────────────── */
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print {
            body { padding: 0 !important; background: #fff !important; }
            .preview-toolbar { display: none !important; }
            .document { width: auto; min-height: auto; box-shadow: none; }
        }

        /* ── Page wrapper ───────────────────────────────────────────────── */
        .document {
            width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff;
            padding-bottom: 70px; /* clear fixed footer */
            box-shadow: {{ $previewMode ? '0 40px 120px rgba(0,0,0,0.45)' : 'none' }};
        }

        /* ── Cover header ───────────────────────────────────────────────── */
        .cover-header {
            position: relative; min-height: 220px;
            padding: 38px 60px 0; background: #000; overflow: hidden;
            border-bottom: 2px solid {{ $coverTheme['border'] }};
        }
        .brand { position: relative; z-index: 2; width: 228px; }
        .brand img { display: block; width: 100%; height: auto; }
        .meta-rail {
            position: absolute; left: 60px; right: 60px; bottom: 0; z-index: 2;
            display: grid; grid-template-columns: 1.25fr 0.85fr 1.15fr 0.8fr 0.55fr;
        }
        .meta-item { position: relative; min-height: 100px; padding: 0 18px 24px 0; color: #fff; }
        .meta-item + .meta-item { padding-left: 20px; }
        /* Vertical dividers — alternate tall / short like candles */
        .meta-item + .meta-item::before {
            content: ''; position: absolute; left: 0; bottom: 0;
            width: 2px; background: rgba(255,255,255,0.92);
            height: 100%;
        }
        /* Short candles: items at positions 3 and 5 (the 2nd and 4th dividers) */
        .meta-item:nth-child(3)::before,
        .meta-item:nth-child(5)::before {
            height: 52%;
        }
        .meta-label { margin: 0; font-size: 17px; font-weight: 700; letter-spacing: -0.02em; text-transform: uppercase; }
        .meta-value { margin-top: 10px; font-size: 13px; line-height: 1.15; color: rgba(255,255,255,0.92); white-space: pre-line; text-transform: uppercase; }
        .meta-value.scope { font-size: 12px; line-height: 1.28; letter-spacing: 0.01em; }

        /* ── Hero / title band ──────────────────────────────────────────── */
        .hero-band { padding: {{ $density['hero'] }}; background: {{ $heroSurface }}; }
        .hero-band h1 {
            margin: 0; font-size: 60px; line-height: 1.06;
            font-weight: 300; letter-spacing: -0.055em;
            text-transform: uppercase; white-space: pre-line; color: #0f1114;
        }
        .hero-band .subtitle { margin: 18px 0 0; font-size: 17px; line-height: 1.5; color: rgba(15,17,20,0.72); }

        /* ── Section label strip ─────────────────────────────────────────── */
        .section-strip {
            padding: 10px 76px; background: {{ $stripTone['bg'] }};
            font-size: 14px; line-height: 1.3; color: {{ $stripTone['text'] }};
        }
        /* Clipped-end variant (KLF-style family) — diagonal cut on the right edge */
        .section-strip.clipped {
            clip-path: polygon(0 0, calc(100% - 22px) 0, 100% 100%, 0 100%);
        }
        .section-strip-label { font-weight: 700; text-transform: uppercase; }
        /* Sub-heading below grey band (for non-Section-X labels that have a page_title) */
        .section-sub-heading {
            padding: 14px 76px 0;
            font-size: 18px; font-weight: 700;
            letter-spacing: -0.025em; color: #111317;
        }

        /* ── Content areas ───────────────────────────────────────────────── */
        .page-block     { padding: {{ $density['page'] }}; }
        .two-column     { display: grid; grid-template-columns: 1fr 1fr; gap: {{ $density['gap'] }}; }
        .block-heading  { margin: 0 0 16px; font-size: 19px; font-weight: 700; letter-spacing: -0.03em; color: #111317; }
        .narrative-section { page-break-inside: avoid; }
        .narrative-body    { padding: {{ $density['narrative'] }}; }

        /* ── Numbered section block ──────────────────────────────────────── */
        .block-item            { margin-bottom: 28px; }
        .block-item:last-child { margin-bottom: 0; }
        .block-item-heading {
            margin: 0 0 12px; font-size: 16px; font-weight: 700;
            letter-spacing: -0.01em; color: #111317;
            display: flex; align-items: baseline; gap: 0;
        }
        /* Numbered items (e.g. "1. EXTERIOR VISUALIZATION") are upper-cased in the
           PHP, not by CSS — un-numbered titles (e.g. "Key Deliverables") stay in
           their authored case. Matches the reference documents exactly; do not
           reintroduce a blanket text-transform here. */
        .block-item-num  { margin-right: 7px; }
        .block-item-sub  {
            margin-bottom: 8px; font-size: 11px; font-weight: 700;
            letter-spacing: 0.09em; text-transform: uppercase; color: rgba(17,19,23,0.45);
        }

        /* ── Stage grid (two-column work-process sections) ───────────────── */
        .stage-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 0 48px; align-items: start;
        }
        .stage-grid-sep {
            grid-column: 1 / -1;
            border-top: 1px solid #dfe1e4;
            margin: 18px 0;
        }
        .stage-block            { padding-bottom: 4px; }
        .stage-block-head {
            padding-bottom: 6px; margin-bottom: 8px;
            border-bottom: 1.5px solid #dfe1e4;
            font-size: 12px; font-weight: 700;
            letter-spacing: 0.07em; text-transform: uppercase; color: #111317;
        }
        .stage-block-dur {
            margin-top: 10px; padding-top: 6px;
            border-top: 1px solid #e5e6e8;
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.1em; text-transform: uppercase; color: rgba(17,19,23,0.55);
        }

        /* ── Column grid (Interior Visualization "Header: item, item" body) */
        /* Plain white columns — small square-bullet heading + rule beneath, no
           colour-blocked bar and no vertical divider lines (matches the reference
           documents exactly; this used to be a dark #1c1e22 header band with a 1px
           divider trick, which the source PDFs never actually have). */
        .body-col-grid {
            display: grid;
            gap: 0 32px;
            margin: 8px 0 14px;
        }
        .body-col-head {
            display: flex; align-items: center; gap: 7px;
            padding-bottom: 8px; margin-bottom: 10px;
            border-bottom: 1px solid #dfe1e4;
            font-size: 12px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #111317;
        }
        .body-col-sq   { font-size: 7px; flex-shrink: 0; color: #111317; }
        .body-col-item  { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 6px; }
        .body-col-group-gap { height: 18px; }
        .bci-dot { flex-shrink: 0; font-size: 8px; width: 9px; margin-top: 4px; color: #333; }
        .body-col-item span:last-child { font-size: 13px; line-height: 1.4; color: rgba(17,19,23,0.82); }

        /* ── Rich body text ─────────────────────────────────────────────── */
        .body-p     { margin: 0 0 5px; font-size: 13px; line-height: 1.5; color: rgba(17,19,23,0.82); }
        .body-intro { margin: 0 0 7px; font-size: 13px; font-weight: 700; line-height: 1.4; color: #111317; }
        .numbered-head {
            margin: 18px 0 8px; font-size: 14px; font-weight: 700;
            letter-spacing: 0.01em; text-transform: uppercase; color: #111317;
        }
        .numbered-head .num { margin-right: 4px; }
        /* ALL-CAPS sub-band: full-bleed grey strip within narrative-body  */
        .caps-head {
            margin: 14px -76px 8px; padding: 7px 76px;
            background: #ebebec;
            font-size: 12px; font-weight: 700;
            letter-spacing: 0.05em; text-transform: uppercase; color: #111317;
        }
        .stage-head {
            margin: 0 0 6px; font-size: 12px; font-weight: 700;
            letter-spacing: 0.06em; text-transform: uppercase; color: #111317;
        }
        .bullet-list  { margin: 4px 0 8px; }
        .bullet-item  { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 3px; }
        .bullet-dot   { flex-shrink: 0; width: 14px; font-size: 12px; color: #555; margin-top: 2px; }
        .bullet-dot.sq { font-size: 8px; margin-top: 4px; color: #333; }
        .bullet-item span:last-child { font-size: 13px; line-height: 1.42; color: rgba(17,19,23,0.82); }

        /* ── Terms list ─────────────────────────────────────────────────── */
        .terms-item     { display: flex; gap: 10px; margin-bottom: 12px; }
        .terms-num      { flex-shrink: 0; font-size: 13px; font-weight: 700; color: #111317; min-width: 18px; }
        .terms-body     { font-size: 13px; line-height: 1.56; color: rgba(17,19,23,0.82); }
        .terms-clause   { margin-bottom: 10px; font-size: 13px; line-height: 1.56; color: rgba(17,19,23,0.82); }
        .terms-body strong, .terms-clause strong { font-weight: 700; color: #111317; }
        .terms-dots {
            display: inline-block; min-width: 160px; border-bottom: 1px solid rgba(17,19,23,0.35);
            margin: 0 2px;
        }
        .terms-quot-ref {
            margin-top: 18px; padding-top: 14px; border-top: 1px solid #dfe1e4;
            font-size: 12px; color: rgba(17,19,23,0.6);
        }
        .terms-quot-ref strong { color: #111317; font-weight: 700; }

        /* ── Page topbar (faint watermark masthead on pages after the cover) ── */
        .page-topbar {
            display: flex; justify-content: space-between; align-items: center;
            padding: 24px 76px 22px;
        }
        .page-topbar img { opacity: 0.16; }
        .page-topbar-wordmark { font-size: 18px; font-weight: 300; letter-spacing: -0.05em; color: rgba(15,17,20,0.16); }
        .page-topbar-tagline { font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(15,17,20,0.3); }

        /* ── Comment ruled-lines block ─────────────────────────────────────── */
        .comment-lines-label {
            margin: 0 0 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; color: rgba(17,19,23,0.5);
        }
        .comment-line { height: 22px; border-bottom: 1px solid #dfe1e4; }

        /* ── Signature / acceptance block ──────────────────────────────────── */
        .sig-block-callout {
            padding: 18px 24px; margin-bottom: 24px; background: #f2f2f3;
            font-size: 13px; font-weight: 600; color: #111317;
        }
        .sig-block { margin-bottom: 36px; }
        .sig-block-role {
            margin: 0 0 14px; font-size: 20px; font-weight: 300; letter-spacing: -0.02em; color: #111317;
        }
        .sig-block-row { display: grid; grid-template-columns: 1fr auto 220px; align-items: end; gap: 24px; }
        .sig-block-name { font-size: 13px; font-weight: 700; color: #111317; }
        .sig-block-subtitle { margin-top: 2px; font-size: 12px; color: rgba(17,19,23,0.55); }
        .sig-block-phone { font-size: 13px; font-weight: 600; color: #111317; white-space: nowrap; }
        .sig-block-signature-label {
            display: block; font-size: 22px; letter-spacing: 0.02em; color: rgba(17,19,23,0.12);
        }
        .sig-block-signature-line { margin-top: 4px; border-bottom: 1px solid rgba(17,19,23,0.35); }
        .sig-block-date { margin-top: 40px; text-align: right; font-size: 15px; font-weight: 600; color: #111317; }

        /* Compact "Sign Here ________" pair — inner content pages, not the full acceptance form */
        .sig-block-simple-row { display: flex; gap: 64px; margin-top: 32px; }
        .sig-block-simple { flex: 1; display: flex; align-items: baseline; gap: 10px; font-size: 13px; color: #111317; }
        .sig-block-simple-line { flex: 1; border-bottom: 1px solid rgba(17,19,23,0.35); }

        /* ── Quotation page ─────────────────────────────────────────────── */
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
           checkmark (the reference documents use a single diagonal slash, not a
           check glyph). Hidden (not removed) on descriptive sub-item rows so the
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

        /* ── Fixed footer (all pages) ─────────────────────────────────────── */
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
            font-size: 8px; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: rgba(15,17,20,0.4);
        }
        .pdf-footer-col { font-size: 11px; line-height: 1.7; color: rgba(15,17,20,0.5); letter-spacing: 0.01em; }
        .pdf-footer-col strong { font-weight: 600; color: rgba(15,17,20,0.65); display: block; }

        /* Per-page "Page N of M" — a normal-flow element placed at the end of each
           page's content (not inside .pdf-footer, which is position:fixed and so
           can only ever show one static value repeated on every page — see the
           $pageCounter comment above the sections loop for why this isn't done via
           CSS @page counters either). */
        .pdf-page-number {
            text-align: right; font-family: 'Mulish', sans-serif; font-size: 10px;
            font-weight: 600; letter-spacing: 0.04em; color: rgba(15,17,20,0.35);
            margin-top: 24px;
        }
    </style>
</head>
<body>

{{-- ═══ Preview toolbar (Tailwind) ══════════════════════════════════════ --}}
@if ($previewMode)
    <div class="flex items-center justify-between gap-4 mb-4 px-[18px] py-[14px] border border-white/10 bg-black/90 text-white/80" style="width:210mm;margin-left:auto;margin-right:auto;">
        <div class="text-[11px] uppercase tracking-[0.15em]">Previewing <span class="text-white/60">{{ $proposal->reference_number ?: 'unsaved reference' }}</span></div>
        <div class="flex gap-2">
            <a href="{{ route('admin.proposals.edit', $proposal) }}"
               class="border border-white/15 px-4 py-2.5 text-[11px] uppercase tracking-[0.18em] text-white/70 hover:border-white/30 hover:text-white transition-colors">
                ← Back to editor
            </a>
            <a href="{{ route('admin.proposals.export', $proposal) }}"
               class="border border-white bg-white px-4 py-2.5 text-[11px] uppercase tracking-[0.18em] text-black hover:bg-white/90 transition-colors">
                Download PDF
            </a>
        </div>
    </div>
@endif

<div class="document">

    {{-- ════════════════════ PAGE 1: COVER ════════════════════════════════ --}}
    @include('admin.proposals._cover-header', ['metalRailData' => $document['header']])

    {{-- Hero title band --}}
    <section class="hero-band">
        <h1>{{ $document['hero']['title'] }}</h1>
        @if (filled($document['hero']['subtitle']))
            <p class="subtitle">{{ $document['hero']['subtitle'] }}</p>
        @endif
    </section>

    {{-- Intro section strip + two-column copy --}}
    <div class="section-strip">
        <span class="section-strip-label">{{ $document['intro']['section_label'] }}</span>
    </div>
    <section class="page-block">
        <div class="two-column narrative-section">
            <div>
                <h2 class="block-heading">{{ $document['intro']['left_heading'] }}</h2>
                <div>{!! $renderBody($document['intro']['left_body']) !!}</div>
            </div>
            <div>
                <h2 class="block-heading">{{ $document['intro']['right_heading'] }}</h2>
                <div>{!! $renderBody($document['intro']['right_body']) !!}</div>
            </div>
        </div>
    </section>

    {{-- ════════════════════ CONTENT SECTIONS (block-based) ══════════════ --}}
    @php
        $totalSections = count($document['sections']);

        // "Page N of M" footer — a plain incrementing label placed in each page's own
        // content flow (not CSS @page counters: Chrome's --print-to-pdf doesn't render
        // @page margin-box content/counter(), a known real limitation, confirmed against
        // a reference proposal template that hand-numbers pages the same way for the
        // same reason). Cover is page 1; only sections that actually render (pass the
        // same $hasContent check below) advance the count, so an empty section never
        // creates a numbering gap.
        $contentSectionCount = collect($document['sections'])->filter(function ($s) {
            $blocks = is_array($s['blocks'] ?? null) ? $s['blocks'] : [];

            return filled($s['label'] ?? '') || filled($s['page_title'] ?? '')
                || collect($blocks)->contains(fn ($b) => filled($b['body'] ?? '') || filled($b['title'] ?? '') || filled($b['number'] ?? ''));
        })->count();
        $totalPages = 1 + $contentSectionCount + ($quotation ? 1 : 0) + (($document['acceptance']['enabled'] ?? false) ? 1 : 0);
        $pageCounter = 1;
    @endphp
    @foreach ($document['sections'] as $sectionIndex => $section)
        @php
            $sectionBlocks = is_array($section['blocks'] ?? null) ? $section['blocks'] : [];
            $isLastSection = $sectionIndex === $totalSections - 1;

            $hasContent = filled($section['label'] ?? '')
                       || filled($section['page_title'] ?? '')
                       || collect($sectionBlocks)->contains(
                              fn($b) => filled($b['body'] ?? '') || filled($b['title'] ?? '') || filled($b['number'] ?? '')
                          );
            if (! $hasContent) continue;
            $pageCounter++;
            $currentPageNum = $pageCounter;

            $bandLabel = mb_strtoupper(trim((string) ($section['label']      ?? '')));
            $bandTitle = mb_strtoupper(trim((string) ($section['page_title'] ?? '')));

            // "Section X" labels (A, B, C…) → combine label + page_title in one band.
            // Other labels (Work Scope, Terms of Engagement) → label only in band;
            // page_title (if any) renders as a sub-heading below the band.
            $isSectionLabel  = (bool) preg_match('/^SECTION\s+[A-Z]/i', $bandLabel);
            if ($isSectionLabel) {
                $bandText       = $bandLabel;
                if ($bandTitle) $bandText .= '.  ' . $bandTitle;
                $showSubHeading = false;
            } else {
                $bandText       = $bandLabel ?: $bandTitle;
                $showSubHeading = $bandLabel !== '' && $bandTitle !== '';
            }

            $layout = $section['layout'] ?? 'single-column';
            // For two-column: separate into left/right/full for content blocks
            // (stage blocks keep original order for row-by-row grid)
            $hasStageBlocks = collect($sectionBlocks)->contains(fn($b) => filled($b['subtitle'] ?? ''));
            $bannerStyle = $appearance['section_banner_style'] ?? 'plain';
            $mastheadTagline = $appearance['masthead_tagline'] ?? false;
        @endphp

        {{-- Subsequent-page masthead: faint watermark logo, no bar/border (forces a new page for every section after Work Scope) --}}
        @if ($sectionIndex > 0)
            <div class="page-topbar" style="page-break-before: always;">
                <div>
                    @if ($logoData)
                        <img src="{{ $logoData }}" alt="Drenla" style="height:18px;display:inline-block;vertical-align:middle;filter:invert(1);">
                    @else
                        <span class="page-topbar-wordmark">DRENLA</span>
                    @endif
                </div>
                @if ($mastheadTagline)
                    <div class="page-topbar-tagline">Unlocking Great Ideas</div>
                @endif
            </div>
        @endif

        {{-- Grey section band --}}
        <div class="section-strip narrative-section {{ $bannerStyle === 'clipped' ? 'clipped' : '' }}">
            <span class="section-strip-label">{{ $bandText }}</span>
        </div>
        {{-- Sub-heading (non-Section-X labels with a page_title, e.g. custom sections) --}}
        @if ($showSubHeading)
            <div class="section-sub-heading">{{ ucwords(mb_strtolower($bandTitle)) }}</div>
        @endif

        {{-- Section body --}}
        <section class="narrative-body narrative-section">

            @if ($layout === 'single-column')
                {{-- ── Single-column blocks ────────────────────────────── --}}
                @foreach ($sectionBlocks as $block)
                    @php $bType = $block['type'] ?? 'narrative'; @endphp

                    @if ($bType === 'stage_grid')
                        @include('admin.proposals._block-stage-grid', ['block' => $block])

                    @elseif ($bType === 'bullet_list')
                        @include('admin.proposals._block-bullet-list', ['block' => $block])

                    @elseif ($bType === 'multi_column_list')
                        @include('admin.proposals._block-multi-column-list', ['block' => $block])

                    @elseif ($bType === 'comment_lines')
                        @include('admin.proposals._block-comment-lines', ['block' => $block])

                    @elseif ($bType === 'signature_block')
                        @include('admin.proposals._block-signature', ['block' => $block])

                    @else
                        @php
                            $bNum  = trim((string) ($block['number']   ?? ''));
                            $bTit  = trim((string) ($block['title']    ?? ''));
                            $bSub  = trim((string) ($block['subtitle'] ?? ''));
                            $bBody = trim((string) ($block['body']     ?? ''));
                            $isTerm = $bNum !== '' && $bTit === '';
                        @endphp

                        @if ($isTerm)
                            <div class="terms-item">
                                <span class="terms-num">{{ $bNum }}.</span>
                                <div class="terms-body">{!! $renderTerms($bBody) !!}</div>
                            </div>

                        @elseif (! $bNum && ! $bTit)
                            @if ($bBody)
                                <div class="terms-clause">{!! $renderTerms($bBody) !!}</div>
                            @endif

                        @else
                            <div class="block-item">
                                @if ($bNum || $bTit)
                                    <h3 class="block-item-heading">
                                        @if ($bNum)<span class="block-item-num">{{ $bNum }}.</span>@endif
                                        {{ $bNum ? mb_strtoupper($bTit) : $bTit }}
                                    </h3>
                                @endif
                                @if ($bSub)<div class="block-item-sub">{{ $bSub }}</div>@endif
                                @if ($bBody)<div>{!! $renderBody($bBody) !!}</div>@endif
                            </div>
                        @endif
                    @endif
                @endforeach

                {{-- Auto-reference to linked quotation at end of last section --}}
                @if ($isLastSection && $quotation)
                    <div class="terms-quot-ref">
                        See billing info. attached in QUOTATION# <strong>{{ $quotation->reference_number }}</strong>
                    </div>
                @endif

            @elseif ($hasStageBlocks)
                {{-- ── Stage grid (Work Process style): row-by-row with separators ── --}}
                <div class="stage-grid">
                    @foreach ($sectionBlocks as $si => $block)
                        @php
                            $bTit  = trim((string) ($block['title']    ?? ''));
                            $bSub  = trim((string) ($block['subtitle'] ?? ''));
                            $bBody = trim((string) ($block['body']     ?? ''));
                            $bCol  = $block['column'] ?? 'full';
                        @endphp

                        {{-- Row separator before each new pair (every 2nd left-column block) --}}
                        @if ($si > 0 && $si % 2 === 0 && $bCol !== 'full')
                            <div class="stage-grid-sep"></div>
                        @endif

                        @if ($bCol === 'full')
                            <div class="block-item" style="grid-column:1/-1;">
                                @if ($bTit)<h3 class="block-item-heading">{{ mb_strtoupper($bTit) }}</h3>@endif
                                @if ($bBody)<div>{!! $renderBody($bBody) !!}</div>@endif
                            </div>
                        @else
                            <div class="stage-block">
                                @if ($bTit)
                                    <div class="stage-block-head">
                                        {{ mb_strtoupper($bTit) }}@if($bSub) :@endif
                                    </div>
                                @endif
                                @if ($bBody)<div>{!! $renderBody($bBody) !!}</div>@endif
                                @if ($bSub)
                                    <div class="stage-block-dur">{{ mb_strtoupper($bSub) }}</div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

            @else
                {{-- ── Two-column content blocks (e.g. Real Estate Section B) ── --}}
                @php
                    $leftBlocks  = array_values(array_filter($sectionBlocks, fn($b) => ($b['column'] ?? 'full') === 'left'));
                    $rightBlocks = array_values(array_filter($sectionBlocks, fn($b) => ($b['column'] ?? 'full') === 'right'));
                    $fullBlocks  = array_values(array_filter($sectionBlocks, fn($b) => ($b['column'] ?? 'full') === 'full'));
                @endphp
                @if ($leftBlocks || $rightBlocks)
                    <div class="two-column" style="margin-bottom:{{ $fullBlocks ? '28px' : '0' }}">
                        <div>
                            @foreach ($leftBlocks as $block)
                                @if (($block['type'] ?? 'narrative') === 'bullet_list')
                                    @include('admin.proposals._block-bullet-list', ['block' => $block])
                                @else
                                    @php $bTit=trim($block['title']??''); $bBody=trim($block['body']??''); $bNum=trim($block['number']??''); @endphp
                                    <div class="block-item">
                                        @if ($bNum||$bTit)<h3 class="block-item-heading">@if($bNum)<span class="block-item-num">{{ $bNum }}.</span>@endif {{ $bNum ? mb_strtoupper($bTit) : $bTit }}</h3>@endif
                                        @if ($bBody)<div>{!! $renderBody($bBody) !!}</div>@endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <div>
                            @foreach ($rightBlocks as $block)
                                @if (($block['type'] ?? 'narrative') === 'bullet_list')
                                    @include('admin.proposals._block-bullet-list', ['block' => $block])
                                @else
                                    @php $bTit=trim($block['title']??''); $bBody=trim($block['body']??''); $bNum=trim($block['number']??''); @endphp
                                    <div class="block-item">
                                        @if ($bNum||$bTit)<h3 class="block-item-heading">@if($bNum)<span class="block-item-num">{{ $bNum }}.</span>@endif {{ $bNum ? mb_strtoupper($bTit) : $bTit }}</h3>@endif
                                        @if ($bBody)<div>{!! $renderBody($bBody) !!}</div>@endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
                @foreach ($fullBlocks as $block)
                    @php $bFullType = $block['type'] ?? 'narrative'; @endphp
                    @if ($bFullType === 'stage_grid')
                        @include('admin.proposals._block-stage-grid', ['block' => $block])
                    @elseif ($bFullType === 'bullet_list')
                        @include('admin.proposals._block-bullet-list', ['block' => $block])
                    @elseif ($bFullType === 'multi_column_list')
                        @include('admin.proposals._block-multi-column-list', ['block' => $block])
                    @elseif ($bFullType === 'comment_lines')
                        @include('admin.proposals._block-comment-lines', ['block' => $block])
                    @elseif ($bFullType === 'signature_block')
                        @include('admin.proposals._block-signature', ['block' => $block])
                    @else
                        @php $bNum=trim($block['number']??''); $bTit=trim($block['title']??''); $bBody=trim($block['body']??''); @endphp
                        <div class="block-item">
                            @if ($bNum||$bTit)<h3 class="block-item-heading">@if($bNum)<span class="block-item-num">{{ $bNum }}.</span>@endif {{ $bNum ? mb_strtoupper($bTit) : $bTit }}</h3>@endif
                            @if ($bBody)<div>{!! $renderBody($bBody) !!}</div>@endif
                        </div>
                    @endif
                @endforeach
            @endif

        </section>

        <div class="pdf-page-number">Page {{ $currentPageNum }} of {{ $totalPages }}</div>

    @endforeach

    {{-- ════════════════════ QUOTATION PAGE (if linked) ═══════════════════ --}}
    @if ($quotation)
        @php
            $qDate    = $quotation->issue_date?->format('j\t\h F Y') ?? '';
            $qScope   = implode("\n", array_filter(
                preg_split('/\r\n|\r|\n/', (string)($document['header']['scope'] ?? '')),
                fn($l) => trim($l) !== ''
            ));
            $qClientName = $quotation->client?->name ?? $proposal->client?->name ?? '';
        @endphp

        {{-- New page break --}}
        <div style="page-break-before: always;">

            {{-- Quotation cover header (same art, different meta) --}}
            @include('admin.proposals._cover-header', [
                'metalRailData' => [
                    'document_label'  => 'QUOTATION',
                    'client_label'    => 'CLIENT',
                    'scope_label'     => 'SCOPE',
                    'reference_label' => 'INVOICE #',
                    'date_label'      => 'DATE',
                    'client_name'     => $qClientName,
                    'scope'           => $qScope,
                    'reference_value' => $quotation->reference_number,
                    'date_value'      => $qDate,
                ],
                'isQuotation'   => true,
            ])

            {{-- Quotation body — shared with the standalone finance document PDF --}}
            @include('admin.finance._document-page', [
                'document' => $quotation,
                'clientNameOverride' => $proposal->client?->name,
            ])

            @php $pageCounter++; @endphp
            <div class="pdf-page-number" style="padding: 0 76px;">Page {{ $pageCounter }} of {{ $totalPages }}</div>
        </div>
    @endif

    {{-- ════════════════════ ACCEPTANCE FORM (opt-in) ═══════════════════ --}}
    @php $acceptance = $document['acceptance'] ?? ['enabled' => false]; @endphp
    @if ($acceptance['enabled'] ?? false)
        <div style="page-break-before: always;">
            @include('admin.proposals._cover-header', [
                'metalRailData' => [
                    'document_label'  => 'ACCEPTANCE FORM',
                    'client_label'    => 'CLIENT',
                    'scope_label'     => 'SCOPE',
                    'reference_label' => $document['header']['reference_label'],
                    'date_label'      => 'DATE',
                    'client_name'     => $document['header']['client_name'],
                    'scope'           => $document['header']['scope'],
                ],
            ])

            <div class="quot-body">
                @include('admin.proposals._block-signature', [
                    'block' => [
                        'intro' => $acceptance['intro_text'],
                        'signers' => $acceptance['signers'],
                        'date_label' => $acceptance['date_label'],
                    ],
                ])
            </div>

            @php $pageCounter++; @endphp
            <div class="pdf-page-number" style="padding: 0 76px;">Page {{ $pageCounter }} of {{ $totalPages }}</div>
        </div>
    @endif

</div>{{-- /.document --}}

{{-- ════════════════════ FIXED FOOTER (every page) ═══════════════════════ --}}
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
