<?php

use App\Models\Client;
use App\Models\Proposal;
use App\Models\User;

it('uppercases numbered block headings but leaves unnumbered titles in their authored case', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::create([
        'name' => 'Chege Mwaura',
        'email' => 'chege-heading@example.com',
        'status' => 'active',
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'title' => 'Heading Case Preview',
        'slug' => 'heading-case-preview',
        'status' => 'draft',
        'document_status' => 'draft',
        'template_key' => 'drenla_project_brief',
        'issue_date' => '2026-07-02',
        'document_data' => [
            'sections' => [
                [
                    'label' => 'Work Scope',
                    'layout' => 'two-column',
                    'blocks' => [
                        ['title' => 'Key Deliverables', 'body' => 'A list of deliverables.', 'column' => 'right'],
                    ],
                ],
                [
                    'label' => 'Section A',
                    'page_title' => 'Scope',
                    'layout' => 'single-column',
                    'blocks' => [
                        ['number' => '1', 'title' => 'Exterior Visualization', 'body' => 'Body text.'],
                    ],
                ],
            ],
        ],
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.proposals.preview', $proposal))
        ->assertOk();

    // Un-numbered block title: reference documents show this in title case ("Key Deliverables").
    $response->assertSee('Key Deliverables');
    $response->assertDontSee('KEY DELIVERABLES');

    // Numbered block title: reference documents show this fully upper-cased ("1. EXTERIOR VISUALIZATION").
    $response->assertSee('EXTERIOR VISUALIZATION');
});
