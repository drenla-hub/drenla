<?php

use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\FinanceDocumentItem;
use App\Models\Proposal;
use App\Services\ProposalRenderService;
use App\Support\ProposalDocumentData;

function renderableProposal(array $documentOverrides = [], ?string $title = null): Proposal
{
    static $counter = 0;
    $counter++;

    $client = Client::create(['name' => 'Farasi Villas (Abidjan) '.$counter, 'status' => 'active']);

    return Proposal::create([
        'client_id' => $client->id,
        'title' => $title ?? 'Farasi Villas (Abidjan) Archviz & Branding Proposal '.$counter,
        'status' => 'draft',
        'issue_date' => '2026-05-13',
        'document_data' => $documentOverrides,
    ]);
}

it('composes a cover page and one page per section, in order', function () {
    $proposal = renderableProposal([
        'hero' => ['title' => 'Farasi Villas'],
        'sections' => [
            ['label' => 'Section A', 'page_title' => 'Scope of 3D Visualization', 'layout' => 'single-column', 'blocks' => []],
            ['label' => 'Section B', 'page_title' => 'Work Process', 'layout' => 'two-column', 'blocks' => []],
        ],
    ]);

    $render = app(ProposalRenderService::class)->build($proposal);

    expect($render->pages[0]['kind'])->toBe('cover');
    expect($render->pages[1]['kind'])->toBe('section');
    expect($render->pages[1]['label'])->toBe('Section A');
    expect($render->pages[2]['label'])->toBe('Section B');
});

it('exposes document-level metadata for the template header', function () {
    $proposal = renderableProposal(['hero' => ['title' => 'Farasi Villas']]);

    $render = app(ProposalRenderService::class)->build($proposal);

    expect($render->metadata['title'])->toBe('Farasi Villas');
    expect($render->metadata['reference_number'])->toBe($proposal->reference_number);
    expect($render->metadata['date'])->toBe('13/05/2026');
});

it('has no quotation page or totals when nothing is linked', function () {
    $proposal = renderableProposal();

    $render = app(ProposalRenderService::class)->build($proposal);

    expect($render->hasQuotation())->toBeFalse();
    expect($render->totals)->toBeNull();
    expect(collect($render->pages)->pluck('kind'))->not->toContain('quotation');
});

it('folds in the linked quotation FinanceDocument as a page, with totals', function () {
    $proposal = renderableProposal();

    $quotation = FinanceDocument::create([
        'client_id' => $proposal->client_id,
        'proposal_id' => $proposal->id,
        'type' => 'quotation',
        'reference_number' => 'DNR-MAY-010',
        'currency' => 'USD',
        'issue_date' => '2026-05-12',
        'payment_terms' => "50% before project commencement\n40% after Stage 4\n10% after final deliverable",
        'payment_info' => 'A.C Name: DRENLA VENTURES LIMITED',
    ]);

    FinanceDocumentItem::create([
        'finance_document_id' => $quotation->id,
        'title' => 'Real Estate Brand Identity Package',
        'quantity' => 1,
        'unit_price' => 3500,
        'total' => 3500,
    ]);
    FinanceDocumentItem::create([
        'finance_document_id' => $quotation->id,
        'title' => 'Commercial 3D Visualization Package',
        'quantity' => 1,
        'unit_price' => 16000,
        'total' => 16000,
    ]);
    $quotation->recalculate()->save();

    $render = app(ProposalRenderService::class)->build($proposal->fresh());

    expect($render->hasQuotation())->toBeTrue();
    expect($render->totals['subtotal'])->toBe('19500.00');
    expect($render->totals['currency'])->toBe('USD');

    $quotationPage = collect($render->pages)->firstWhere('kind', 'quotation');
    expect($quotationPage)->not->toBeNull();
    expect($quotationPage['items'])->toHaveCount(2);
    expect($quotationPage['payment_terms'])->toContain('50% before project commencement');
});

it('adds an acceptance page with signers only when the proposal opts in', function () {
    $withoutAcceptance = renderableProposal();
    $renderWithout = app(ProposalRenderService::class)->build($withoutAcceptance);

    expect(collect($renderWithout->pages)->pluck('kind'))->not->toContain('acceptance');
    expect($renderWithout->signers)->toBe([]);

    $withAcceptance = renderableProposal([
        'acceptance' => [
            'enabled' => true,
            'intro_text' => 'Please read and understand all terms listed before signing below',
            'date_label' => 'MAY, 2026',
            'signers' => [
                ['role' => 'Client Contact', 'name' => 'Arch. Victor', 'subtitle' => '[Design Infinity Architects]', 'phone' => '+254722172037'],
                ['role' => 'Drenla Ventures', 'name' => 'Mr Mbuya Adrian', 'subtitle' => '[Head of Design]', 'phone' => '+254759947183'],
            ],
        ],
    ]);
    $renderWith = app(ProposalRenderService::class)->build($withAcceptance);

    $acceptancePage = collect($renderWith->pages)->firstWhere('kind', 'acceptance');
    expect($acceptancePage)->not->toBeNull();
    expect($renderWith->signers)->toHaveCount(2);
    expect($renderWith->signers[1]['name'])->toBe('Mr Mbuya Adrian');
});

it('flattens blocks across pages and filters by type', function () {
    $proposal = renderableProposal([
        'sections' => [
            [
                'label' => 'Section A',
                'layout' => 'two-column',
                'blocks' => [
                    ['type' => 'stage_grid', 'stages' => [['label' => 'Stage 1', 'duration' => '2 Weeks', 'items' => ['Review']]]],
                    ['type' => 'comment_lines', 'line_count' => 6],
                ],
            ],
        ],
    ]);

    $render = app(ProposalRenderService::class)->build($proposal);

    expect($render->blocks())->toHaveCount(2);
    expect($render->blocksOfType(ProposalDocumentData::BLOCK_TYPE_STAGE_GRID))->toHaveCount(1);
    expect($render->blocksOfType(ProposalDocumentData::BLOCK_TYPE_COMMENT_LINES))->toHaveCount(1);
});

it('proves the render contract covers every block type identified in the audit, for the Abidjan-style family', function () {
    // Representative of DNR-MAY-005-ABD-1: two-column narrative (page 1), a
    // multi-column deliverables list, a stage-grid work process, comment ruled
    // lines + a dual-signature block on inner pages, and legal terms rendered as
    // numbered narrative blocks (that shape already existed and still does).
    $proposal = renderableProposal([
        'sections' => [
            [
                'label' => 'Work Scope', 'layout' => 'two-column',
                'blocks' => [
                    ['type' => 'narrative', 'body' => 'Full scope text', 'column' => 'left'],
                    ['type' => 'bullet_list', 'title' => 'Key Deliverables', 'items' => ['Renders', 'Floorplans'], 'column' => 'right'],
                ],
            ],
            [
                'label' => 'Section A', 'page_title' => 'Scope of 3D Visualization', 'layout' => 'single-column',
                'blocks' => [
                    ['type' => 'multi_column_list', 'title' => 'Interior Visualization', 'columns' => [
                        ['heading' => 'Ground Floor', 'items' => ['Main Lounge']],
                        ['heading' => 'First Floor', 'items' => ['Master Bedroom Ensuite']],
                        ['heading' => 'Guest Wing', 'items' => ['Mini Lounge']],
                    ]],
                    ['type' => 'comment_lines', 'label' => 'Client Comments'],
                    ['type' => 'signature_block', 'signers' => [
                        ['role' => 'Client', 'name' => 'Jane Doe'],
                        ['role' => 'Drenla', 'name' => 'John Smith'],
                    ]],
                ],
            ],
            [
                'label' => 'Section B', 'page_title' => 'Work Process', 'layout' => 'two-column',
                'blocks' => [
                    ['type' => 'stage_grid', 'stages' => [
                        ['label' => 'Stage 1', 'duration' => '2 Week', 'items' => ['Preliminary brief review']],
                        ['label' => 'Stage 2', 'duration' => '3 Weeks', 'items' => ['3D Full Site Massing']],
                    ]],
                ],
            ],
            [
                'label' => 'Terms of Engagement', 'layout' => 'single-column',
                'blocks' => [
                    ['type' => 'narrative', 'number' => '1', 'body' => 'First Site Visit Fees of Kes 30,000 is required.'],
                ],
            ],
        ],
    ]);

    $render = app(ProposalRenderService::class)->build($proposal);
    $typesPresent = collect($render->blocks())->pluck('type')->unique()->sort()->values()->all();

    expect($typesPresent)->toBe([
        'bullet_list', 'comment_lines', 'multi_column_list', 'narrative', 'signature_block', 'stage_grid',
    ]);
});
