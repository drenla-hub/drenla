<?php

use App\Services\ProposalDocumentValidator;
use Illuminate\Validation\ValidationException;

it('passes a well-formed document with every block type', function () {
    $data = [
        'hero' => ['title' => 'Farasi Villas (Abidjan)'],
        'appearance' => ['cover_tone' => 'plum', 'section_banner_style' => 'clipped', 'masthead_tagline' => true],
        'sections' => [
            [
                'label' => 'Section A',
                'layout' => 'single-column',
                'blocks' => [
                    ['type' => 'narrative', 'number' => '1', 'title' => 'Exterior', 'body' => 'Text'],
                    ['type' => 'bullet_list', 'items' => ['One', 'Two']],
                    ['type' => 'multi_column_list', 'columns' => [['heading' => 'Ground Floor', 'items' => ['Lounge']]]],
                    ['type' => 'stage_grid', 'stages' => [['label' => 'Stage 1', 'duration' => '2 Weeks', 'items' => ['Review']]]],
                    ['type' => 'comment_lines', 'line_count' => 5],
                    ['type' => 'signature_block', 'signers' => [['name' => 'Arch. Victor']]],
                ],
            ],
        ],
        'acceptance' => ['enabled' => true, 'signers' => [['name' => 'Arch. Victor']]],
    ];

    expect(fn () => ProposalDocumentValidator::validate($data))->not->toThrow(ValidationException::class);
});

it('rejects a stage_grid block with no stages', function () {
    ProposalDocumentValidator::validate([
        'sections' => [
            ['blocks' => [['type' => 'stage_grid', 'stages' => []]]],
        ],
    ]);
})->throws(ValidationException::class);

it('rejects a multi_column_list block with no columns', function () {
    ProposalDocumentValidator::validate([
        'sections' => [
            ['blocks' => [['type' => 'multi_column_list', 'columns' => []]]],
        ],
    ]);
})->throws(ValidationException::class);

it('rejects a signature_block signer with no name', function () {
    ProposalDocumentValidator::validate([
        'sections' => [
            ['blocks' => [['type' => 'signature_block', 'signers' => [['role' => 'Client Contact']]]]],
        ],
    ]);
})->throws(ValidationException::class);

it('rejects an unknown appearance enum value', function () {
    ProposalDocumentValidator::validate([
        'appearance' => ['cover_tone' => 'neon-pink'],
    ]);
})->throws(ValidationException::class);

it('rejects an unknown section layout', function () {
    ProposalDocumentValidator::validate([
        'sections' => [['layout' => 'three-column', 'blocks' => []]],
    ]);
})->throws(ValidationException::class);

it('rejects a comment_lines block with an out-of-range line_count', function () {
    ProposalDocumentValidator::validate([
        'sections' => [
            ['blocks' => [['type' => 'comment_lines', 'line_count' => 50]]],
        ],
    ]);
})->throws(ValidationException::class);
