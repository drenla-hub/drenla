<?php

namespace App\Support;

use App\Models\Proposal;

class ProposalDocumentData
{
    // ── Template keys ─────────────────────────────────────────────────────
    public const TEMPLATE_KEY = 'drenla_project_brief';

    public const TEMPLATE_REALESTATE_KEY = 'drenla_realestate_brief';

    public const TEMPLATE_VERSION = 'v1';

    // ── Template registry ─────────────────────────────────────────────────
    public static function templateOptions(): array
    {
        return [
            self::TEMPLATE_KEY => [
                'label' => 'Residential Archviz',
                'description' => 'Residential architectural design and 3D visualization brief.',
                'version' => self::TEMPLATE_VERSION,
            ],
            self::TEMPLATE_REALESTATE_KEY => [
                'label' => 'Real Estate Archviz & Branding',
                'description' => 'Real estate development — visualization, brand identity and marketing scope.',
                'version' => self::TEMPLATE_VERSION,
            ],
        ];
    }

    // ── Defaults dispatcher ───────────────────────────────────────────────
    public static function defaults(?Proposal $proposal = null, ?string $templateKey = null): array
    {
        $key = $templateKey ?? $proposal?->template_key ?? self::TEMPLATE_KEY;

        return match ($key) {
            self::TEMPLATE_REALESTATE_KEY => self::defaultsRealEstate($proposal),
            default => self::defaultsResidential($proposal),
        };
    }

    // ── Default: Residential Archviz ──────────────────────────────────────
    private static function defaultsResidential(?Proposal $proposal = null): array
    {
        $clientName = $proposal?->client?->name ?? '';
        $title = $proposal?->title ?? '';

        return [
            'header' => [
                'document_label' => 'PROJECT BRIEF',
                'client_label' => 'CLIENT',
                'scope_label' => 'SCOPE',
                'reference_label' => 'REFERENCE #',
                'date_label' => 'DATE',
                'client_name' => $clientName,
                'scope' => "RESIDENTIAL\nARCHITECTURAL\nDESIGN VISUALIZATION",
            ],
            'hero' => [
                'title' => $title,
                'subtitle' => '',
            ],
            'appearance' => self::defaultAppearance(),
            'intro' => [
                'section_label' => 'Project Description',
                'left_heading' => 'Brief',
                'left_body' => $proposal?->summary ?? '',
                'right_heading' => 'Key Objective',
                'right_body' => 'To develop the best possible ideas for the look of the spaces, visualize them in the right SCALE AND PROPORTIONS FOR BUILDING.',
            ],
            'sections' => [

                // ── Page 1 (continued): Work Scope summary ────────────────
                self::emptySection([
                    'label' => 'Work Scope',
                    'page_title' => '',
                    'layout' => 'two-column',
                    'blocks' => [
                        self::emptyBlock([
                            'title' => '',
                            'body' => "This project brief covers the full scope of Architectural Design and 3D Visualization services for this residential property.\n\n• Section A — Scope of 3D Visualization: Exterior and interior 3D renders, photorealistic visualization, and full technical working drawings.\n• Section B — Work Process: Staged delivery across 4 key milestones with defined timelines and client approval gates.\n• Terms of Engagement: Project terms, revision policy, intellectual property rights and payment milestone structure.",
                            'column' => 'left',
                        ]),
                        self::emptyBlock([
                            'title' => 'Key Deliverables',
                            'body' => "• Photorealistic Exterior Renders (Daytime + Evening)\n• Full Interior 3D Visualization per Room\n• 2D Rendered Floorplans & Section Cuts\n• Ceiling Design Drawings with Light Points\n• Cabinetry & Wardrobe Detail Drawings\n• Plumbing & Electrical Layout Plans",
                            'column' => 'right',
                        ]),
                    ],
                ]),

                // ── Page: Scope of 3D Visualization ───────────────────────
                self::emptySection([
                    'label' => 'Section A',
                    'page_title' => 'Scope of 3D Visualization',
                    'layout' => 'single-column',
                    'blocks' => [
                        self::emptyBlock([
                            'number' => '1',
                            'title' => 'Exterior Visualization',
                            'body' => "• General Site Layout\n• Building Facade Features and Finishes\n• Balconies & Terraces\n• Swimming Pool Area\n• General Landscaping\n• Gate & Driveway Design",
                            'column' => 'full',
                        ]),
                        self::emptyBlock([
                            'number' => '2',
                            'title' => 'Interior Visualization',
                            'body' => "Ground Floor: Entrance Porche & Foyer, Powder Room, Main Lounge, Dining Area, Kitchen & Pantry, Laundry Room, Study/Library\nFirst Floor: Guest Master Ensuite, Stairwell & Landing, Kids Bedroom 01 Ensuite, Bedroom 02 Ensuite, Mini-Lounge or TV Room FF, Master Bedroom Ensuite, Master Bathroom, Master Closet\nGuest Wing: Mini Lounge, Kitchenette, Bedroom Ensuite, Cloak Room, Pool House, Gym Area, Sauna/Steam Room",
                            'column' => 'full',
                        ]),
                        self::emptyBlock([
                            'number' => '3',
                            'title' => '3D Visualization',
                            'body' => "Photorealistic Interior Renders to Capture the following:\n• Furniture Layout & Design\n• Wall & Floor Finishes — paint, tilework, laminates, natural stone, coastal textures\n• Ceiling Design\n• Cabinetry & Wardrobe Installations\n• Lighting & Plumbing Fixtures — premium, coastal-inspired accents\n• Soft Furnishing Staging",
                            'column' => 'full',
                        ]),
                        self::emptyBlock([
                            'number' => '4',
                            'title' => 'Detailed 2D Working Drawings & Rendered Plans',
                            'body' => "• Floor Plans, layouts, circulation, and furniture placement\n• Ceiling Design Drawings with light-points, ventilation, and detailing\n• Elevations & Cross-Sections showing proportions and finishes\n• Wardrobes & Cabinetry detailed drawings for joinery and storage\n• Plumbing & Electrical Layouts with sanitary fixtures, outlets, and switches\n• Finish Schedules covering materials, textures, and coastal treatments",
                            'column' => 'full',
                        ]),
                    ],
                ]),

                // ── Page: Work Process ────────────────────────────────────
                self::emptySection([
                    'label' => 'Section B',
                    'page_title' => 'Work Process',
                    'layout' => 'two-column',
                    'blocks' => [
                        self::emptyBlock([
                            'title' => 'Stage 1',
                            'subtitle' => '1 Week',
                            'body' => "- Review project brief & land dimensions\n- Gather all reference materials & regulations\n- Site and spatial analysis report\n- Concept scope & moodboards",
                            'column' => 'left',
                        ]),
                        self::emptyBlock([
                            'title' => 'Stage 2',
                            'subtitle' => '3 Weeks',
                            'body' => "- Architectural Floorplan Development\n- Full 3D massing of Main Villa & Guest Wing\n- Landscape Layout featuring Pool Area\n- Elevation & Cross-sectional Drawings\n- 3D Exterior & Landscape Renders",
                            'column' => 'right',
                        ]),
                        self::emptyBlock([
                            'title' => 'Stage 3',
                            'subtitle' => '3 Weeks',
                            'body' => "- Commence Interior Design Massing\n- Finalization of Door & Window Schedule\n- Interior Spatial Finishes & Technical Installations\n- Full Interior compilation & submission",
                            'column' => 'left',
                        ]),
                        self::emptyBlock([
                            'title' => 'Stage 4',
                            'subtitle' => '3 Weeks',
                            'body' => "- Architectural Drawings Compilation & Submission\n- Full 2D Rendered Plans ft Interior Spaces\n- Interior Design Technical Drawings\n- Finalization of 3D Animation Video",
                            'column' => 'right',
                        ]),
                    ],
                ]),

                // ── Page: Terms of Engagement ─────────────────────────────
                self::emptySection([
                    'label' => 'Terms of Engagement',
                    'page_title' => '',
                    'layout' => 'single-column',
                    'blocks' => [
                        self::emptyBlock(['number' => '1', 'body' => "The Client agrees to submit all style reference materials, including inspiration images, sketches, and idea schedules, either before project commencement or during the initial project stage. Any additional references provided after this stage will constitute a scope increase, subject to the Designer's discretion, and may incur additional costs."]),
                        self::emptyBlock(['number' => '2', 'body' => 'First Site Visit Fees of Kes 30,000 is required and ONLY redeemable on total project costing. However consequent site visits shall be billed separately per visit at the same amount.']),
                        self::emptyBlock(['number' => '3', 'body' => "For visualization we'll provide 3 views/Angles per space. Daytime visualization for all Interior spaces, while both Daytime & Evening views for Exteriors. (This view will allow us to showcase the spaces in both day light and artificial evening lights.)"]),
                        self::emptyBlock(['number' => '4', 'body' => 'Client agrees to approve each stage prior to commencement of the next stage so as to ensure seamless delivery of design details as changes and revisions arising from a crossed stage may attract additional costs.']),
                        self::emptyBlock(['number' => '5', 'body' => 'The intellectual property claim to all digital products resulting from services rendered maintain to be the property of Drenla® and are only automatically and fully transferred to the client(s) after a full settlement of the Professional Design Fee.']),
                        self::emptyBlock(['body' => 'Kindly note that upon acceptance of these Terms of Engagement, this proposal and all details contained herein shall be considered legally binding with respect to the services rendered by DRENLA VENTURES LIMITED (hereafter referred to as the "Designer") to the Client (hereafter referred to as the "Client").']),
                        self::emptyBlock(['body' => 'This agreement covers all deliverables listed under Sections A & B, including any client-approved comments or mark-ups signed off on good-will and mutual agreement by both parties.']),
                    ],
                ]),
            ],
        ];
    }

    // ── Default: Real Estate Archviz & Branding ───────────────────────────
    private static function defaultsRealEstate(?Proposal $proposal = null): array
    {
        $clientName = $proposal?->client?->name ?? '';
        $title = $proposal?->title ?? '';

        return [
            'header' => [
                'document_label' => 'PROJECT BRIEF',
                'client_label' => 'CLIENT',
                'scope_label' => 'SCOPE',
                'reference_label' => 'INVOICE #',
                'date_label' => 'DATE',
                'client_name' => $clientName,
                'scope' => "REAL ESTATE ARCHVIZ\n& BRANDING SCOPE",
            ],
            'hero' => [
                'title' => $title,
                'subtitle' => 'Archviz & Branding Proposal',
            ],
            'appearance' => self::defaultAppearance([
                'cover_tone' => 'graphite',
                'hero_surface' => 'warm',
                'section_strip' => 'sand',
            ]),
            'intro' => [
                'section_label' => 'Project Description',
                'left_heading' => 'Brief',
                'left_body' => $proposal?->summary ?? '',
                'right_heading' => 'Key Objective',
                'right_body' => "To develop the best possible ideas for the look of the spaces, visualize them in the right SCALE AND PROPORTIONS FOR CONSTRUCTION AND OFF-PLAN MARKETING.\n\nBy developing these Visual renders and Walkthrough of the exterior and interior spaces, it will NOT only make it easier for decision making on the materials to use and cost husbandry BUT also highlight unique features and selling points of each space to captivate potential buyers and investors prior and after implementation.",
            ],
            'sections' => [

                // ── Page 1 (continued): Work Scope summary ────────────────
                self::emptySection([
                    'label' => 'Work Scope',
                    'page_title' => '',
                    'layout' => 'two-column',
                    'blocks' => [
                        self::emptyBlock([
                            'title' => '',
                            'body' => "This brief covers the full scope of Architectural Visualization, Brand Identity and Marketing Collateral development for this real estate development.\n\n• Section A — Developing the Brand Identity: Brand research, naming, visual identity system, print and digital marketing collateral.\n• Section B — Exterior & Interior Visualization: Full site massing, facade detailing and interior spaces across all unit typologies.\n• Section C — Deliverables in Detail: Photorealistic renders, technical working drawings and 3D animation video.\n• Section D — Work Process: Staged delivery across 5 key milestones with defined timelines and approval gates.\n• Section E — Design Terms of Engagement: Project terms, revision policy, intellectual property rights and payment structure.",
                            'column' => 'left',
                        ]),
                        self::emptyBlock([
                            'title' => 'Key Deliverables',
                            'body' => "• Brand Identity Package (Logo, Colors, Typeface, Patterns)\n• Marketing Brochure & Print Collateral\n• Exterior & Interior 3D Renders (2–3 per space)\n• Full Site Plan & Aerial Drone Views\n• 3D Animation FlyThrough Video (4K, 2+ min)\n• 2D Rendered Floorplans & Technical Drawings",
                            'column' => 'right',
                        ]),
                    ],
                ]),

                // ── Page A: Brand Identity ────────────────────────────────
                self::emptySection([
                    'label' => 'Section A',
                    'page_title' => 'Developing the Brand Identity',
                    'layout' => 'single-column',
                    'blocks' => [
                        self::emptyBlock([
                            'title' => 'Goal / Objective',
                            'body' => "The goal is to create a representation of what this Project is — ITS IDENTITY. Here we'll bring the visual look and the story or reputation appropriate for the development under one umbrella to create the image we'd like our audiences to have. The identity will be built to be evident across all touch points where customers and relevant others will come into contact with the brand.",
                        ]),
                        self::emptyBlock([
                            'number' => '1',
                            'title' => 'Brand Organization',
                            'body' => "This first stage will include research and exploration of ideas from naming to listing all channels to be used for launch, marketing and selling of the Properties. We will also make a decision on which TOUCH POINTS should receive all the leads, ie Social Media Marketing Templates and deploy necessary 'plug and play' assets to facilitate the efforts.",
                        ]),
                        self::emptyBlock([
                            'number' => '2',
                            'title' => 'Visual Design Deliverables',
                            'body' => "This stage includes design of the brand visual symbols:\n• The Project Brand Logo 1/5 Variations\n• Brand colors\n• Brand typeface\n• Brand patterns\n• Brand Iconography Style\n\nPRINT VISUALS:\n• Stationery (cards, letterheads)\n• Marketing Brochure\n• Buyer Onboarding documents\n• Brand Folders\n• Street Banners & Billboards\n• Brand merchandize and gift items\n• Wayfinding Graphics & Signages\n• Brand Launch Event Concepts\n\nDIGITAL VISUALS:\n• Social media templates\n• Digital banners\n• Email signature templates\n• Buy-on Website UX/UI\n\nBRAND ASSETS:\n• Expansive Brand Style Guide",
                        ]),
                    ],
                ]),

                // ── Page B: Exterior & Interior Visualization ─────────────
                self::emptySection([
                    'label' => 'Section B',
                    'page_title' => 'Exterior & Interior Visualization',
                    'layout' => 'two-column',
                    'blocks' => [
                        self::emptyBlock([
                            'number' => '1',
                            'title' => 'Exterior Visualization',
                            'body' => "• Full Site Plan\n• Main Gates & Front Facade\n• General Site Landscaping\n• Building Facades: 2 Typology Block Details\n• Club House\n• Outdoor Pool Area & Gardens\n• Main Access Street — Lighting & Sidewalks\n• Executive Experiences ie Gym Studio\n• Outdoor Restaurant",
                            'column' => 'left',
                        ]),
                        self::emptyBlock([
                            'title' => 'Interior Design & Visualization',
                            'body' => "BLOCK TYPE 001:\n• Main Entry, Main Lounge, Dining Area\n• Main Kitchen, Cloak Room\n• Stairwell & Understair Landing\n• Outdoor Varandas & Balconies\n• Guest Bedroom + Bathroom\n• Bedroom 001 & 002, Family Room\n• Master Bedroom, Master Bathroom\n• Master Walk In, Garage w/ Staging\n\nBLOCK TYPE 002:\n• Same as Block Type 001 configuration",
                            'column' => 'right',
                        ]),
                    ],
                ]),

                // ── Page C: Deliverables + 3D Animation ──────────────────
                self::emptySection([
                    'label' => 'Section C',
                    'page_title' => 'Deliverables in Detail',
                    'layout' => 'two-column',
                    'blocks' => [
                        self::emptyBlock([
                            'title' => 'Photorealistic Renders & Working Drawings',
                            'body' => "• Furniture layout & Design\n• Wall & Floor Finish — Paint, Tilework, laminates & Texturised Effects\n• Ceiling Design\n• Cabinetry & Technical Installations\n• Lighting & Plumbing fixtures\n• Soft Furnishing staging\n• 2D Layout Rendered Floorplans\n• Fullsite Drone views",
                            'column' => 'left',
                        ]),
                        self::emptyBlock([
                            'title' => '3D Animation Video Deliverables',
                            'body' => "EXTERIOR FRAMES:\n• Full Site Aerial Views\n• Main Gate & Streetside Approach\n• Front side Perimeter Facade\n• Primary Units Facade — 2 Typologies\n• Club House Approach\n• Pool Area & Tropical Landscaping Detail\n\nINTERIOR FRAMES:\n• Gym + Spa, Rooftop Restaurant\n• Primary Units Interior Spaces\n• Main Entry + Main Lounge\n• Dining + Kitchens, Bedrooms + Cloakroom\n• Master Bedroom + Master Bathroom Suite\n• Family Spaces + Balconies",
                            'column' => 'right',
                        ]),
                    ],
                ]),

                // ── Page D: Work Process (5 stages) ──────────────────────
                self::emptySection([
                    'label' => 'Section D',
                    'page_title' => 'Work Process',
                    'layout' => 'two-column',
                    'blocks' => [
                        self::emptyBlock([
                            'title' => 'Stage 1', 'subtitle' => '2 Weeks', 'column' => 'left',
                            'body' => "- Preliminary brief review\n- Consolidation of relevant materials\n- General Analysis Report\n- Concept Scope & MoodBoard",
                        ]),
                        self::emptyBlock([
                            'title' => 'Stage 2', 'subtitle' => '3 Weeks', 'column' => 'right',
                            'body' => "- 3D Full Site Massing: All Site Structures\n- Landscaping, Parking + Gardens\n- Commence Primary Units Exterior Detail\n- Initiate Brand Styling Development",
                        ]),
                        self::emptyBlock([
                            'title' => 'Stage 3', 'subtitle' => '2 Weeks', 'column' => 'left',
                            'body' => "- Initial Interior Design Massing\n- Groundfloor + First Floor Spaces\n- Initial Sampling of Concept Renders\n- Finalization of Brand Identity",
                        ]),
                        self::emptyBlock([
                            'title' => 'Stage 4', 'subtitle' => '1.5 Weeks', 'column' => 'right',
                            'body' => "- Final Exterior Facade Visualization\n- Interior Spatial Final Renders\n- Finalization of Marketing Collaterals\n  (eg Project Brochure)\nFinal Renders Approval for Stage 5",
                        ]),
                        self::emptyBlock([
                            'title' => 'Stage 5', 'subtitle' => '2 Weeks', 'column' => 'left',
                            'body' => "- 3D FlyThrough Animation Render Scenes\n- Post Processing + Compilation\nFinal Submission",
                        ]),
                    ],
                ]),

                // ── Page E: Terms ─────────────────────────────────────────
                self::emptySection([
                    'label' => 'Section E',
                    'page_title' => 'Design Terms of Engagement',
                    'layout' => 'single-column',
                    'blocks' => [
                        self::emptyBlock(['number' => '1', 'body' => "Atleast 2-3 static renders per space while for 3D Animation visualization we'll provide 4K 2> Min Video."]),
                        self::emptyBlock(['number' => '2', 'body' => 'Time of Day in visualization for exterior spaces — Alternating Daytime/Evening views. (This view will allow us to showcase the spaces in both day light and artificial evening lights.)']),
                        self::emptyBlock(['number' => '3', 'body' => 'There are unlimited revisions, all to be submitted in ONE phase after client approval before proceeding to the next stage. This ensures seamless delivery of design deliverables.']),
                        self::emptyBlock(['number' => '4', 'body' => 'Client shall provide all necessary material eg Plans and Schedules required to ensure seamless delivery of project deliverables in scope. Additional work, or schedules out of scope may attract additional costs at standard studio rates to be disclosed upon enquiry.']),
                        self::emptyBlock(['number' => '5', 'body' => 'Intellectual Property Rights of Digital Products emerging from the delivery of services rendered remain in DRENLA VENTURES LIMITED ownership and ONLY fully transferred for use by client upon settlement of final Installment of the mutually agreed project fees.']),
                        self::emptyBlock(['body' => 'Kindly note that upon acceptance of these Terms of Engagement, this proposal and all details contained herein shall be considered legally binding with respect to the services rendered by DRENLA VENTURES LIMITED (hereafter referred to as the "Designer") to the Client (hereafter referred to as the "Client").']),
                        self::emptyBlock(['body' => 'This agreement covers all deliverables listed under Sections A, B & C, including any client-approved comments or mark-ups signed off on good-will and mutual agreement by both parties.']),
                    ],
                ]),
            ],
        ];
    }

    // ── Normalise (merge saved data with defaults) ─────────────────────────
    public static function normalize(?array $data, ?Proposal $proposal = null): array
    {
        $templateKey = $proposal?->template_key ?? self::TEMPLATE_KEY;
        $defaults = self::defaults($proposal, $templateKey);
        $data = is_array($data) ? $data : [];

        $header = array_merge($defaults['header'], is_array($data['header'] ?? null) ? $data['header'] : []);
        $hero = array_merge($defaults['hero'], is_array($data['hero'] ?? null) ? $data['hero'] : []);
        $appearance = array_merge($defaults['appearance'] ?? self::defaultAppearance(), is_array($data['appearance'] ?? null) ? $data['appearance'] : []);
        $intro = array_merge($defaults['intro'], is_array($data['intro'] ?? null) ? $data['intro'] : []);
        $acceptance = self::normalizeAcceptance(is_array($data['acceptance'] ?? null) ? $data['acceptance'] : ($defaults['acceptance'] ?? []));

        $sections = collect($data['sections'] ?? $defaults['sections'])
            ->filter(fn ($s) => is_array($s))
            ->map(fn (array $s) => self::normalizeSection($s))
            ->values()
            ->all();

        if ($sections === []) {
            $sections = $defaults['sections'];
        }

        return [
            'header' => self::cleanStrings($header),
            'hero' => self::cleanStrings($hero),
            'appearance' => self::cleanStrings($appearance),
            'intro' => self::cleanStrings($intro),
            'sections' => $sections,
            'acceptance' => $acceptance,
        ];
    }

    /**
     * The acceptance/signature-form page — off by default (`enabled: false`), a
     * proposal opts in when staff want a signature page in the exported document.
     * Same signer shape as an inline `signature_block` (see emptySignatureBlock),
     * just document-level rather than embedded in a section.
     */
    public static function normalizeAcceptance(array $acceptance = []): array
    {
        $defaults = [
            'enabled' => false,
            'intro_text' => 'Please read and understand all terms listed before signing below',
            'date_label' => '',
            'signers' => [],
        ];

        $merged = array_merge($defaults, $acceptance);
        $merged['enabled'] = (bool) $merged['enabled'];
        $merged['intro_text'] = trim((string) $merged['intro_text']);
        $merged['date_label'] = trim((string) $merged['date_label']);
        $merged['signers'] = collect($merged['signers'] ?? [])
            ->filter(fn ($s) => is_array($s))
            ->map(fn (array $s) => [
                'role' => trim((string) ($s['role'] ?? '')),
                'name' => trim((string) ($s['name'] ?? '')),
                'subtitle' => trim((string) ($s['subtitle'] ?? '')),
                'phone' => trim((string) ($s['phone'] ?? '')),
            ])
            ->values()
            ->all();

        return $merged;
    }

    /** Normalise one section, migrating old body/aside_body format if needed. */
    public static function normalizeSection(array $section): array
    {
        // ── Migrate old format (has body/aside_body at section level) ──────
        if (! isset($section['blocks'])) {
            $blocks = [];
            $layout = $section['layout'] ?? 'single-column';

            if (filled($section['body'] ?? '')) {
                $blocks[] = self::emptyBlock([
                    'title' => $section['title'] ?? '',
                    'body' => $section['body'] ?? '',
                    'column' => ($layout === 'two-column') ? 'left' : 'full',
                ]);
            }
            if (filled($section['aside_body'] ?? '')) {
                $blocks[] = self::emptyBlock([
                    'title' => $section['aside_title'] ?? '',
                    'body' => $section['aside_body'] ?? '',
                    'column' => ($layout === 'two-column') ? 'right' : 'full',
                ]);
            }

            return [
                'label' => $section['label'] ?? '',
                'page_title' => $section['page_title'] ?? ($section['title'] ?? ''),
                'layout' => $layout,
                'blocks' => $blocks,
            ];
        }

        // ── Normalise blocks ───────────────────────────────────────────────
        $blocks = collect($section['blocks'] ?? [])
            ->filter(fn ($b) => is_array($b))
            ->map(fn (array $b) => self::normalizeBlock($b))
            ->values()
            ->all();

        return [
            'label' => trim((string) ($section['label'] ?? '')),
            'page_title' => trim((string) ($section['page_title'] ?? '')),
            'layout' => in_array($section['layout'] ?? '', ['single-column', 'two-column'], true)
                ? $section['layout']
                : 'single-column',
            'blocks' => $blocks,
        ];
    }

    public static function fromRequest(array $input, ?Proposal $proposal = null): array
    {
        $normalized = self::normalize($input, $proposal);
        $normalized['header']['document_label'] = mb_strtoupper($normalized['header']['document_label']);
        $normalized['header']['client_label'] = mb_strtoupper($normalized['header']['client_label']);
        $normalized['header']['scope_label'] = mb_strtoupper($normalized['header']['scope_label']);
        $normalized['header']['reference_label'] = mb_strtoupper($normalized['header']['reference_label']);
        $normalized['header']['date_label'] = mb_strtoupper($normalized['header']['date_label']);

        return $normalized;
    }

    /** Return preset scope + sections for the template-defaults API endpoint. */
    public static function presetFor(string $templateKey, ?Proposal $proposal = null): array
    {
        $d = self::defaults($proposal, $templateKey);

        return [
            'scope' => $d['header']['scope'],
            'subtitle' => $d['hero']['subtitle'] ?? '',
            'sections' => $d['sections'],
        ];
    }

    /**
     * Block `type` values the renderer understands. `narrative` is the original,
     * implicit-only shape every pre-existing block was — absent `type` on saved
     * data defaults to it, so nothing written before this schema extension needs
     * migrating. See collaboration-notes.md "Proposal Builder" decisions.
     */
    public const BLOCK_TYPE_NARRATIVE = 'narrative';

    public const BLOCK_TYPE_BULLET_LIST = 'bullet_list';

    public const BLOCK_TYPE_MULTI_COLUMN_LIST = 'multi_column_list';

    public const BLOCK_TYPE_STAGE_GRID = 'stage_grid';

    public const BLOCK_TYPE_COMMENT_LINES = 'comment_lines';

    public const BLOCK_TYPE_SIGNATURE = 'signature_block';

    /** @return list<string> */
    public static function blockTypes(): array
    {
        return [
            self::BLOCK_TYPE_NARRATIVE,
            self::BLOCK_TYPE_BULLET_LIST,
            self::BLOCK_TYPE_MULTI_COLUMN_LIST,
            self::BLOCK_TYPE_STAGE_GRID,
            self::BLOCK_TYPE_COMMENT_LINES,
            self::BLOCK_TYPE_SIGNATURE,
        ];
    }

    /**
     * Normalize any block to its type's canonical shape, filling defaults for
     * missing keys and dropping unknown ones. This is the single dispatch point
     * every block passes through — new types get added here, not scattered
     * through the normalizer.
     */
    public static function normalizeBlock(array $block): array
    {
        $type = in_array($block['type'] ?? null, self::blockTypes(), true)
            ? $block['type']
            : self::BLOCK_TYPE_NARRATIVE;

        return match ($type) {
            self::BLOCK_TYPE_BULLET_LIST => self::emptyBulletListBlock($block),
            self::BLOCK_TYPE_MULTI_COLUMN_LIST => self::emptyMultiColumnListBlock($block),
            self::BLOCK_TYPE_STAGE_GRID => self::emptyStageGridBlock($block),
            self::BLOCK_TYPE_COMMENT_LINES => self::emptyCommentLinesBlock($block),
            self::BLOCK_TYPE_SIGNATURE => self::emptySignatureBlock($block),
            default => self::emptyBlock($block),
        };
    }

    /** Two-column-capable rich text block: numbered legal clauses, deliverable paragraphs, brief/objective text. */
    public static function emptyBlock(array $block = []): array
    {
        $merged = array_merge([
            'number' => '',
            'title' => '',
            'subtitle' => '',
            'body' => '',
            'column' => 'full',
        ], $block);
        $merged['type'] = self::BLOCK_TYPE_NARRATIVE;

        return $merged;
    }

    /** Square/dash bullet list with structured items instead of "\n• " inline text. */
    public static function emptyBulletListBlock(array $block = []): array
    {
        $defaults = [
            'title' => '',
            'intro' => '',
            'bullet_style' => 'square', // square | dash
            'items' => [],
            'column' => 'full',
        ];

        $merged = array_merge($defaults, $block);
        $merged['type'] = self::BLOCK_TYPE_BULLET_LIST;
        $merged['items'] = self::normalizeStringList($merged['items'] ?? []);
        $merged['bullet_style'] = in_array($merged['bullet_style'], ['square', 'dash'], true) ? $merged['bullet_style'] : 'square';

        return $merged;
    }

    /** 2-4 column grouped list, e.g. Ground Floor / First Floor / Guest Wing. */
    public static function emptyMultiColumnListBlock(array $block = []): array
    {
        $defaults = [
            'title' => '',
            'columns' => [],
        ];

        $merged = array_merge($defaults, $block);
        $merged['type'] = self::BLOCK_TYPE_MULTI_COLUMN_LIST;
        $merged['columns'] = collect($merged['columns'] ?? [])
            ->filter(fn ($c) => is_array($c))
            ->map(fn (array $c) => [
                'heading' => trim((string) ($c['heading'] ?? '')),
                'items' => self::normalizeStringList($c['items'] ?? []),
            ])
            ->values()
            ->all();

        return $merged;
    }

    /** Stage/timeline grid — a dedicated visual (bordered cells, duration emphasis) distinct from a generic two-column narrative block. */
    public static function emptyStageGridBlock(array $block = []): array
    {
        $defaults = [
            'columns' => 2,
            'stages' => [],
        ];

        $merged = array_merge($defaults, $block);
        $merged['type'] = self::BLOCK_TYPE_STAGE_GRID;
        $merged['columns'] = in_array((int) $merged['columns'], [1, 2], true) ? (int) $merged['columns'] : 2;
        $merged['stages'] = collect($merged['stages'] ?? [])
            ->filter(fn ($s) => is_array($s))
            ->map(fn (array $s) => [
                'label' => trim((string) ($s['label'] ?? '')),
                'duration' => trim((string) ($s['duration'] ?? '')),
                'items' => self::normalizeStringList($s['items'] ?? []),
                'note' => trim((string) ($s['note'] ?? '')),
            ])
            ->values()
            ->all();

        return $merged;
    }

    /** Structural placeholder — no real content, tells the renderer to draw N ruled lines for client mark-up. */
    public static function emptyCommentLinesBlock(array $block = []): array
    {
        $defaults = [
            'label' => 'Comments',
            'line_count' => 4,
        ];

        $merged = array_merge($defaults, $block);
        $merged['type'] = self::BLOCK_TYPE_COMMENT_LINES;
        $merged['line_count'] = max(1, min(12, (int) $merged['line_count']));

        return $merged;
    }

    /**
     * Dual-signature line pattern (inner pages) and the full acceptance-form page
     * are the same shape — a list of signers — just placed in different contexts.
     */
    public static function emptySignatureBlock(array $block = []): array
    {
        $defaults = [
            'intro' => '',
            'signers' => [],
            'date_label' => '',
        ];

        $merged = array_merge($defaults, $block);
        $merged['type'] = self::BLOCK_TYPE_SIGNATURE;
        $merged['signers'] = collect($merged['signers'] ?? [])
            ->filter(fn ($s) => is_array($s))
            ->map(fn (array $s) => [
                'role' => trim((string) ($s['role'] ?? '')), // free label, e.g. "Client Contact" / "Drenla Ventures"
                'name' => trim((string) ($s['name'] ?? '')),
                'subtitle' => trim((string) ($s['subtitle'] ?? '')), // company/title, e.g. "[Head of Design]"
                'phone' => trim((string) ($s['phone'] ?? '')),
            ])
            ->values()
            ->all();

        return $merged;
    }

    /** @return list<string> */
    private static function normalizeStringList(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();
    }

    public static function emptySection(array $section = []): array
    {
        return array_merge([
            'label' => '',
            'page_title' => '',
            'layout' => 'single-column',
            'blocks' => [],
        ], $section);
    }

    private static function defaultAppearance(array $overrides = []): array
    {
        return array_merge([
            'cover_tone' => 'plum',
            'hero_surface' => 'mist',
            'section_strip' => 'stone',
            'content_density' => 'comfortable',
        ], $overrides);
    }

    public static function narrativeSummary(array $document): array
    {
        $summary = trim((string) ($document['intro']['left_body'] ?? ''));

        $body = collect($document['sections'] ?? [])
            ->flatMap(function (array $section) {
                return collect($section['blocks'] ?? [])
                    ->flatMap(fn (array $b) => [
                        $b['title'] ?? '',
                        $b['body'] ?? '',
                    ])
                    ->all();
            })
            ->filter(fn ($v) => trim((string) $v) !== '')
            ->implode("\n\n");

        return [$summary, $body];
    }

    private static function cleanStrings(array $values): array
    {
        return collect($values)->map(fn ($v) => is_string($v) ? trim($v) : $v)->all();
    }
}
