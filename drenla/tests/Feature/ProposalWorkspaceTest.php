<?php

use App\Data\ExportedProposalPdf;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\User;
use App\Services\ProposalPdfExporter;
use Illuminate\Support\Facades\File;

it('creates a structured proposal workspace', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Mercy Cheru',
        'email' => 'mercy@example.com',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->post('/admin/proposals', [
            'client_id' => $client->id,
            'title' => 'Greenheart Kilifi Residential Visualization',
            'status' => 'draft',
            'document_status' => 'review',
            'template_key' => 'drenla_project_brief',
            'issue_date' => '2026-05-26',
            'value' => '250000',
            'document' => [
                'header' => [
                    'document_label' => 'PROJECT BRIEF',
                    'client_label' => 'CLIENT',
                    'scope_label' => 'SCOPE',
                    'reference_label' => 'INVOICE #',
                    'date_label' => 'DATE',
                    'client_name' => 'Mercy Cheru',
                    'scope' => "RESIDENTIAL\nARCHITECTURAL\nDESIGN VISUALIZATION",
                ],
                'hero' => [
                    'title' => "GREENHEART KILIFI\nRESIDENTIAL VISUALIZATION.",
                    'subtitle' => '',
                ],
                'intro' => [
                    'section_label' => 'Project Description',
                    'left_heading' => 'Brief',
                    'left_body' => 'This is a comprehensive proposal to develop the visual direction.',
                    'right_heading' => 'Key Objective',
                    'right_body' => 'Create compelling architectural visuals that support decision-making.',
                ],
                'sections' => [
                    [
                        'label' => 'Delivery Scope',
                        'title' => 'Outputs',
                        'body' => 'Render suite, board layouts, and presentation support.',
                        'aside_title' => 'Approvals',
                        'aside_body' => 'Approval checkpoints are milestone-based.',
                        'layout' => 'two-column',
                    ],
                ],
            ],
        ])
        ->assertRedirect();

    $proposal = Proposal::first();

    expect($proposal)->not->toBeNull();
    expect($proposal->reference_number)->toStartWith('DNR-MAY-');
    expect($proposal->document_data['hero']['title'])->toBe("GREENHEART KILIFI\nRESIDENTIAL VISUALIZATION.");
    expect($proposal->summary)->toBe('This is a comprehensive proposal to develop the visual direction.');
});

it('renders the proposal preview for admins', function () {
    $user = User::factory()->superAdmin()->create();
    $proposal = Proposal::create([
        'title' => 'Greenheart Kilifi Residential Visualization',
        'slug' => 'greenheart-kilifi-residential-visualization',
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-05-26',
        'document_data' => [
            'hero' => ['title' => "GREENHEART KILIFI\nRESIDENTIAL VISUALIZATION."],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('admin.proposals.preview', $proposal))
        ->assertOk()
        ->assertSee('GREENHEART KILIFI')
        ->assertSee('REFERENCE #');
});

it('exports a proposal pdf for admins', function () {
    $user = User::factory()->superAdmin()->create();
    $proposal = Proposal::create([
        'title' => 'Greenheart Kilifi Residential Visualization',
        'slug' => 'greenheart-kilifi-residential-visualization',
        'status' => 'draft',
        'document_status' => 'approved',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-05-26',
        'document_data' => [
            'hero' => ['title' => "GREENHEART KILIFI\nRESIDENTIAL VISUALIZATION."],
        ],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'proposal-pdf-');
    File::put($path, '%PDF-1.4 test');

    $this->mock(ProposalPdfExporter::class, function ($mock) use ($path) {
        $mock->shouldReceive('export')
            ->once()
            ->andReturn(new ExportedProposalPdf($path, 'greenheart-kilifi.pdf'));
    });

    $this->actingAs($user)
        ->get(route('admin.proposals.export', $proposal))
        ->assertOk()
        ->assertDownload('greenheart-kilifi.pdf');

    $proposal->refresh();

    expect($proposal->last_exported_filename)->toBe('greenheart-kilifi.pdf');
    expect($proposal->last_exported_at)->not->toBeNull();
});
