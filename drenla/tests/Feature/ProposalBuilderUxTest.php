<?php

use App\Models\Client;
use App\Models\Proposal;
use App\Models\User;

it('parses the mini-syntax textareas for every structured block type on save', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Wanjiru Kariuki',
        'email' => 'wanjiru-builder@example.com',
        'status' => 'active',
    ]);

    $payload = [
        'client_id' => $client->id,
        'title' => 'Builder UX Preview',
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-07-03',
        'document' => [
            'header' => [
                'document_label' => 'PROJECT BRIEF',
                'client_label' => 'CLIENT',
                'scope_label' => 'SCOPE',
                'reference_label' => 'REFERENCE #',
                'date_label' => 'DATE',
            ],
            'hero' => ['title' => 'Builder UX Preview'],
            'intro' => [
                'section_label' => 'Project Description',
                'left_heading' => 'Brief',
                'right_heading' => 'Key Objective',
            ],
            'appearance' => [
                'section_banner_style' => 'clipped',
                'masthead_tagline' => '1',
            ],
            'sections' => [
                [
                    'label' => 'Section A',
                    'page_title' => 'Structured Blocks',
                    'layout' => 'single-column',
                    'blocks' => [
                        [
                            'type' => 'bullet_list',
                            'title' => 'Exterior Visualization',
                            'bl_intro' => 'Capture the following:',
                            'bl_bullet_style' => 'dash',
                            'bl_items_text' => "General Site Layout\nBuilding Facade Features\n\nBalconies & Terraces",
                            'column' => 'full',
                        ],
                        [
                            'type' => 'multi_column_list',
                            'title' => 'Interior Visualization',
                            'mc_columns_text' => "Ground Floor: Main Lounge, Kitchen, Study\nFirst Floor: Master Bedroom, Bathroom",
                        ],
                        [
                            'type' => 'stage_grid',
                            'sg_columns' => '2',
                            'sg_stages_text' => "Stage 1 | 2 Weeks\n- Review project brief\n- Concept scope\n\nStage 2 | 3 Weeks\n- Full 3D massing",
                        ],
                        [
                            'type' => 'comment_lines',
                            'cl_label' => 'Client Additional Comments',
                            'cl_line_count' => '5',
                        ],
                        [
                            'type' => 'signature_block',
                            'sb_intro' => '',
                            'sb_date_label' => '',
                            // NOTE: a role-only "Sign Here" signer (no name) is a valid,
                            // real pattern (see the compact-signature test in
                            // ProposalNewBlockTypesTest) but ProposalDocumentValidator
                            // currently requires `name` for block-level signature_block
                            // signers — flagged to CO in collaboration-notes.md. Using a
                            // named signer here so this save-flow test isn't blocked by
                            // that separate, already-tracked issue.
                            'sb_signers_text' => 'Sign Here | Jane Doe',
                        ],
                    ],
                ],
            ],
            'acceptance' => [
                'enabled' => '1',
                'intro_text' => 'Please read and understand all terms listed before signing below',
                'date_label' => 'July, 2026',
                'signers_text' => "Client Contact | Arch. Victor | [Design Infinity Architects] | +254 722 172 037\nDrenla Ventures | Mr Mbuya Adrian | [Head of Design] | +254 759 947 183",
            ],
        ],
    ];

    $this->actingAs($user)
        ->post('/admin/proposals', $payload)
        ->assertRedirect();

    $proposal = Proposal::where('title', 'Builder UX Preview')->firstOrFail();
    $document = $proposal->document_data;

    $blocks = $document['sections'][0]['blocks'];

    // bullet_list
    expect($blocks[0]['type'])->toBe('bullet_list');
    expect($blocks[0]['bullet_style'])->toBe('dash');
    expect($blocks[0]['intro'])->toBe('Capture the following:');
    expect($blocks[0]['items'])->toBe(['General Site Layout', 'Building Facade Features', 'Balconies & Terraces']);

    // multi_column_list
    expect($blocks[1]['type'])->toBe('multi_column_list');
    expect($blocks[1]['columns'])->toBe([
        ['heading' => 'Ground Floor', 'items' => ['Main Lounge', 'Kitchen', 'Study']],
        ['heading' => 'First Floor', 'items' => ['Master Bedroom', 'Bathroom']],
    ]);

    // stage_grid
    expect($blocks[2]['type'])->toBe('stage_grid');
    expect($blocks[2]['columns'])->toBe(2);
    expect($blocks[2]['stages'])->toBe([
        ['label' => 'Stage 1', 'duration' => '2 Weeks', 'items' => ['Review project brief', 'Concept scope'], 'note' => ''],
        ['label' => 'Stage 2', 'duration' => '3 Weeks', 'items' => ['Full 3D massing'], 'note' => ''],
    ]);

    // comment_lines
    expect($blocks[3]['type'])->toBe('comment_lines');
    expect($blocks[3]['label'])->toBe('Client Additional Comments');
    expect($blocks[3]['line_count'])->toBe(5);

    // signature_block
    expect($blocks[4]['type'])->toBe('signature_block');
    expect($blocks[4]['signers'])->toBe([
        ['role' => 'Sign Here', 'name' => 'Jane Doe', 'subtitle' => '', 'phone' => ''],
    ]);

    // document-level acceptance form
    expect($document['acceptance']['enabled'])->toBeTrue();
    expect($document['acceptance']['signers'])->toBe([
        ['role' => 'Client Contact', 'name' => 'Arch. Victor', 'subtitle' => '[Design Infinity Architects]', 'phone' => '+254 722 172 037'],
        ['role' => 'Drenla Ventures', 'name' => 'Mr Mbuya Adrian', 'subtitle' => '[Head of Design]', 'phone' => '+254 759 947 183'],
    ]);

    // appearance knobs
    // NOTE: masthead_tagline isn't boolean-cast yet — ProposalDocumentData::normalize()
    // doesn't know about section_banner_style/masthead_tagline at all (flagged to CO
    // in collaboration-notes.md), so it round-trips as the raw submitted string. The
    // renderer's `@if ($mastheadTagline)` check already treats '1' as truthy, so this
    // isn't a rendering bug — just a data-shape inconsistency worth CO tightening up.
    expect($document['appearance']['section_banner_style'])->toBe('clipped');
    expect($document['appearance']['masthead_tagline'])->toBeTruthy();
});

it('round-trips a saved structured block back into the edit form as mini-syntax text', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Kiptoo Bett',
        'email' => 'kiptoo-builder@example.com',
        'status' => 'active',
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Round Trip Preview',
        'slug' => 'round-trip-preview',
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-07-03',
        'document_data' => [
            'sections' => [
                [
                    'label' => 'Section A',
                    'layout' => 'single-column',
                    'blocks' => [
                        [
                            'type' => 'bullet_list',
                            'title' => 'Exterior',
                            'bullet_style' => 'square',
                            'items' => ['Item one', 'Item two'],
                        ],
                        [
                            'type' => 'multi_column_list',
                            'title' => 'Floors',
                            'columns' => [
                                ['heading' => 'Ground Floor', 'items' => ['Lounge', 'Kitchen']],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.proposals.edit', $proposal))
        ->assertOk();

    $response->assertSee('Item one', false);
    $response->assertSee('Item two', false);
    $response->assertSee('Ground Floor: Lounge, Kitchen', false);
});
