<?php

use App\Support\ProposalDocumentData;

it('normalizes a narrative block and defaults its type when absent, for backward compatibility', function () {
    $block = ProposalDocumentData::normalizeBlock(['title' => 'Brief', 'body' => 'Some text', 'column' => 'left']);

    expect($block['type'])->toBe('narrative');
    expect($block['title'])->toBe('Brief');
    expect($block['column'])->toBe('left');
});

it('normalizes a bullet_list block, trimming and dropping blank items', function () {
    $block = ProposalDocumentData::normalizeBlock([
        'type' => 'bullet_list',
        'title' => 'Key Deliverables',
        'bullet_style' => 'dash',
        'items' => ['  Photorealistic renders ', '', 'Floor plans', null],
    ]);

    expect($block['type'])->toBe('bullet_list');
    expect($block['bullet_style'])->toBe('dash');
    expect($block['items'])->toBe(['Photorealistic renders', 'Floor plans']);
});

it('normalizes a multi_column_list block with grouped columns, e.g. Ground/First Floor/Guest Wing', function () {
    $block = ProposalDocumentData::normalizeBlock([
        'type' => 'multi_column_list',
        'title' => 'Interior Visualization',
        'columns' => [
            ['heading' => 'Ground Floor', 'items' => ['Entrance Porche & Foyer', 'Main Lounge']],
            ['heading' => 'First Floor', 'items' => ['Master Bedroom Ensuite']],
            ['heading' => 'Guest Wing', 'items' => ['Mini Lounge', 'Kitchenette']],
        ],
    ]);

    expect($block['columns'])->toHaveCount(3);
    expect($block['columns'][0]['heading'])->toBe('Ground Floor');
    expect($block['columns'][2]['items'])->toBe(['Mini Lounge', 'Kitchenette']);
});

it('normalizes a stage_grid block with duration and item bullets per stage', function () {
    $block = ProposalDocumentData::normalizeBlock([
        'type' => 'stage_grid',
        'stages' => [
            ['label' => 'Stage 1', 'duration' => '2 Weeks', 'items' => ['Preliminary brief review', 'Concept Scope & MoodBoard']],
            ['label' => 'Stage 2', 'duration' => '3 Weeks', 'items' => ['3D Full Site Massing']],
        ],
    ]);

    expect($block['columns'])->toBe(2);
    expect($block['stages'])->toHaveCount(2);
    expect($block['stages'][0]['duration'])->toBe('2 Weeks');
});

it('normalizes a comment_lines block with a clamped line count', function () {
    $normal = ProposalDocumentData::normalizeBlock(['type' => 'comment_lines']);
    $clamped = ProposalDocumentData::normalizeBlock(['type' => 'comment_lines', 'line_count' => 99]);

    expect($normal['line_count'])->toBe(4);
    expect($clamped['line_count'])->toBe(12);
});

it('normalizes a signature_block with multiple signers', function () {
    $block = ProposalDocumentData::normalizeBlock([
        'type' => 'signature_block',
        'signers' => [
            ['role' => 'Client Contact', 'name' => 'Arch. Victor', 'subtitle' => '[Design Infinity Architects]', 'phone' => '+254722172037'],
            ['role' => 'Drenla Ventures', 'name' => 'Mr Mbuya Adrian', 'subtitle' => '[Head of Design]', 'phone' => '+254759947183'],
        ],
    ]);

    expect($block['signers'])->toHaveCount(2);
    expect($block['signers'][1]['name'])->toBe('Mr Mbuya Adrian');
});

it('falls back to narrative for an unrecognized block type instead of throwing', function () {
    $block = ProposalDocumentData::normalizeBlock(['type' => 'made_up_type', 'title' => 'X', 'body' => 'Y']);

    expect($block['type'])->toBe('narrative');
    expect($block['title'])->toBe('X');
});

it('keeps normalizing pre-existing document_data with no type field on any block', function () {
    $document = ProposalDocumentData::normalize([
        'sections' => [
            [
                'label' => 'Section A',
                'layout' => 'single-column',
                'blocks' => [
                    ['number' => '1', 'title' => 'Exterior Visualization', 'body' => 'Bullet text', 'column' => 'full'],
                ],
            ],
        ],
    ]);

    expect($document['sections'][0]['blocks'][0]['type'])->toBe('narrative');
    expect($document['sections'][0]['blocks'][0]['title'])->toBe('Exterior Visualization');
});

it('normalizes the document-level acceptance field, off by default', function () {
    $document = ProposalDocumentData::normalize(['sections' => []]);

    expect($document['acceptance']['enabled'])->toBeFalse();
    expect($document['acceptance']['signers'])->toBe([]);
});

it('normalizes an enabled acceptance field with signers', function () {
    $document = ProposalDocumentData::normalize([
        'sections' => [],
        'acceptance' => [
            'enabled' => true,
            'intro_text' => 'Please read and understand all terms listed before signing below',
            'date_label' => 'MAY, 2026',
            'signers' => [
                ['role' => 'Client Contact', 'name' => 'Arch. Victor', 'subtitle' => '[Design Infinity Architects]', 'phone' => '+254722172037'],
            ],
        ],
    ]);

    expect($document['acceptance']['enabled'])->toBeTrue();
    expect($document['acceptance']['signers'])->toHaveCount(1);
    expect($document['acceptance']['signers'][0]['name'])->toBe('Arch. Victor');
});
