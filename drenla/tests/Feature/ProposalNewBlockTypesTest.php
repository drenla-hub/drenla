<?php

use App\Models\Client;
use App\Models\Proposal;
use App\Models\User;

it('renders the new structured block types in a proposal preview', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Amara Wanjiru',
        'email' => 'amara-blocks@example.com',
        'status' => 'active',
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Block Types Preview',
        'slug' => 'block-types-preview',
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-07-02',
        'document_data' => [
            'appearance' => [
                'section_banner_style' => 'clipped',
                'masthead_tagline' => true,
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
                            'bullet_style' => 'square',
                            'items' => ['General Site Layout', 'Building Facade Features'],
                        ],
                        [
                            'type' => 'multi_column_list',
                            'title' => 'Interior Visualization',
                            'columns' => [
                                ['heading' => 'Ground Floor', 'items' => ['Main Lounge', 'Kitchen']],
                                ['heading' => 'First Floor', 'items' => ['Master Bedroom']],
                            ],
                        ],
                        [
                            'type' => 'stage_grid',
                            'columns' => 2,
                            'stages' => [
                                ['label' => 'Stage 1', 'duration' => '2 Weeks', 'items' => ['Preliminary brief review']],
                                ['label' => 'Stage 2', 'duration' => '3 Weeks', 'items' => ['3D Full Site Massing']],
                            ],
                        ],
                        [
                            'type' => 'comment_lines',
                            'label' => 'Client Additional Comments',
                            'line_count' => 3,
                        ],
                        [
                            'type' => 'signature_block',
                            'intro' => 'Please review before signing.',
                            'signers' => [
                                ['role' => 'Client Contact', 'name' => 'Amara Wanjiru', 'subtitle' => '[Owner]', 'phone' => '+254 700 000 000'],
                            ],
                            'date_label' => 'July, 2026',
                        ],
                        [
                            'number' => '1',
                            'body' => 'The Client agrees to **submit all reference materials** before commencement, to .................... (hereafter referred to as the Client).',
                        ],
                    ],
                ],
                // Second section — exercises the subsequent-page masthead (only renders past the first section).
                [
                    'label' => 'Section B',
                    'page_title' => 'Terms',
                    'layout' => 'single-column',
                    'blocks' => [
                        ['number' => '1', 'body' => 'Closing terms clause.'],
                    ],
                ],
            ],
            'acceptance' => [
                'enabled' => true,
                'intro_text' => 'Please read and understand all terms listed before signing below',
                'date_label' => 'July, 2026',
                'signers' => [
                    ['role' => 'Client Contact', 'name' => 'Amara Wanjiru', 'subtitle' => '[Owner]', 'phone' => '+254 700 000 000'],
                    ['role' => 'Drenla Ventures', 'name' => 'Mr Mbuya Adrian', 'subtitle' => '[Head of Design]', 'phone' => '+254 759 947 183'],
                ],
            ],
        ],
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.proposals.preview', $proposal))
        ->assertOk();

    // Structured block types render. Headings/labels are uppercased by the
    // renderer to match the reference documents — assert the actual output case.
    $response->assertSee('General Site Layout');
    $response->assertSee('GROUND FLOOR');
    $response->assertSee('STAGE 1');
    $response->assertSee('3D Full Site Massing');
    $response->assertSee('Client Additional Comments');

    // Terms inline bold + dotted fill-in.
    $response->assertSee('<strong>submit all reference materials</strong>', false);
    $response->assertSee('terms-dots', false);

    // Inline signature block + standalone acceptance form (signer names are uppercased by the renderer).
    $response->assertSeeInOrder(['SIGNATURE', 'ACCEPTANCE FORM', 'MR MBUYA ADRIAN']);

    // Clipped banner + masthead tagline appearance knobs.
    $response->assertSee('section-strip narrative-section clipped', false);
    $response->assertSee('Unlocking Great Ideas');
});

it('does not render the acceptance form page when not enabled', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Baraka Njoroge',
        'email' => 'baraka-blocks@example.com',
        'status' => 'active',
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'No Acceptance Preview',
        'slug' => 'no-acceptance-preview',
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-07-02',
        'document_data' => [],
    ]);

    $this->actingAs($user)
        ->get(route('admin.proposals.preview', $proposal))
        ->assertOk()
        ->assertDontSee('ACCEPTANCE FORM');
});

it('renders a compact "Sign Here" pair for role-only signers, distinct from a full signer block', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Naliaka Otieno',
        'email' => 'naliaka-blocks@example.com',
        'status' => 'active',
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Compact Signature Preview',
        'slug' => 'compact-signature-preview',
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-07-02',
        'document_data' => [
            'sections' => [
                [
                    'label' => 'Section A',
                    'page_title' => 'Comments',
                    'layout' => 'single-column',
                    'blocks' => [
                        [
                            'type' => 'signature_block',
                            'signers' => [
                                ['role' => 'Sign Here'],
                                ['role' => 'Sign Here'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.proposals.preview', $proposal))
        ->assertOk();

    $response->assertSee('sig-block-simple-row', false);
    // Role-only signers use the compact inline pattern, not the rich pale-SIGNATURE-label block.
    $response->assertDontSee('SIGNATURE');
});
