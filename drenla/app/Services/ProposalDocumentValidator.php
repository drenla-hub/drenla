<?php

namespace App\Services;

use App\Support\ProposalDocumentData;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validates a full `document_data` array against the block-type schema in
 * ProposalDocumentData, so a malformed block (wrong type, missing required
 * field) can never reach ProposalRenderService/the PDF exporter. Call this
 * wherever document_data is written — the admin builder CL is designing, an
 * API endpoint, a console import, etc. — not just one call site.
 *
 * Each block is validated with its own scoped Validator::make() call rather
 * than one big dot-path ruleset with Rule::forEach — deeply nested wildcard
 * rules (e.g. `sections.*.blocks.*.stages.*.label`) trip a Laravel edge case
 * (an "Undefined array key" warning from ValidationRuleParser) when an inner
 * array is empty. Validating one block at a time sidesteps it entirely and
 * gives cleaner, block-scoped error messages besides.
 *
 * @throws ValidationException
 */
class ProposalDocumentValidator
{
    public static function validate(array $document): array
    {
        $validator = Validator::make($document, self::documentRules());
        $validator->validate();

        $errors = [];

        foreach (($document['sections'] ?? []) as $sectionIndex => $section) {
            foreach ((is_array($section) ? ($section['blocks'] ?? []) : []) as $blockIndex => $block) {
                $path = "sections.{$sectionIndex}.blocks.{$blockIndex}";
                $blockValidator = Validator::make(is_array($block) ? $block : [], self::blockRules(is_array($block) ? $block : []));

                if ($blockValidator->fails()) {
                    foreach ($blockValidator->errors()->messages() as $field => $messages) {
                        $errors["{$path}.{$field}"] = $messages;
                    }
                }
            }
        }

        foreach (($document['acceptance']['signers'] ?? []) as $signerIndex => $signer) {
            $signerValidator = Validator::make(is_array($signer) ? $signer : [], [
                'name' => ['required', 'string', 'max:255'],
                'role' => ['nullable', 'string', 'max:255'],
                'subtitle' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:255'],
            ]);

            if ($signerValidator->fails()) {
                foreach ($signerValidator->errors()->messages() as $field => $messages) {
                    $errors["acceptance.signers.{$signerIndex}.{$field}"] = $messages;
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $document;
    }

    private static function documentRules(): array
    {
        return [
            'header' => ['sometimes', 'array'],
            'header.document_label' => ['nullable', 'string', 'max:255'],
            'header.client_label' => ['nullable', 'string', 'max:255'],
            'header.scope_label' => ['nullable', 'string', 'max:255'],
            'header.reference_label' => ['nullable', 'string', 'max:255'],
            'header.date_label' => ['nullable', 'string', 'max:255'],
            'header.client_name' => ['nullable', 'string', 'max:255'],
            'header.scope' => ['nullable', 'string'],

            'hero' => ['sometimes', 'array'],
            'hero.title' => ['nullable', 'string', 'max:500'],
            'hero.subtitle' => ['nullable', 'string', 'max:500'],

            'appearance' => ['sometimes', 'array'],
            'appearance.cover_tone' => ['nullable', 'string', Rule::in(['plum', 'graphite', 'forest'])],
            'appearance.hero_surface' => ['nullable', 'string', Rule::in(['mist', 'white', 'warm'])],
            'appearance.section_strip' => ['nullable', 'string', Rule::in(['stone', 'sand', 'slate'])],
            'appearance.content_density' => ['nullable', 'string', Rule::in(['comfortable', 'compact'])],
            'appearance.section_banner_style' => ['nullable', 'string', Rule::in(['plain', 'clipped'])],
            'appearance.masthead_tagline' => ['nullable', 'boolean'],

            'intro' => ['sometimes', 'array'],
            'intro.section_label' => ['nullable', 'string', 'max:255'],
            'intro.left_heading' => ['nullable', 'string', 'max:255'],
            'intro.left_body' => ['nullable', 'string'],
            'intro.right_heading' => ['nullable', 'string', 'max:255'],
            'intro.right_body' => ['nullable', 'string'],

            'sections' => ['sometimes', 'array'],
            'sections.*.label' => ['nullable', 'string', 'max:255'],
            'sections.*.page_title' => ['nullable', 'string', 'max:255'],
            'sections.*.layout' => ['nullable', 'string', Rule::in(['single-column', 'two-column'])],
            'sections.*.blocks' => ['sometimes', 'array'],

            'acceptance' => ['sometimes', 'array'],
            'acceptance.enabled' => ['nullable', 'boolean'],
            'acceptance.intro_text' => ['nullable', 'string'],
            'acceptance.date_label' => ['nullable', 'string', 'max:255'],
            'acceptance.signers' => ['sometimes', 'array'],
        ];
    }

    /** @return array<string, array> field => rules, scoped to one block's own keys */
    private static function blockRules(array $block): array
    {
        $type = in_array($block['type'] ?? null, ProposalDocumentData::blockTypes(), true)
            ? $block['type']
            : ProposalDocumentData::BLOCK_TYPE_NARRATIVE;

        $common = [
            'type' => ['nullable', 'string', Rule::in(ProposalDocumentData::blockTypes())],
        ];

        return match ($type) {
            ProposalDocumentData::BLOCK_TYPE_BULLET_LIST => $common + [
                'title' => ['nullable', 'string', 'max:255'],
                'intro' => ['nullable', 'string'],
                'bullet_style' => ['nullable', 'string', Rule::in(['square', 'dash'])],
                'column' => ['nullable', 'string', Rule::in(['left', 'right', 'full'])],
                'items' => ['nullable', 'array'],
                'items.*' => ['string'],
            ],

            ProposalDocumentData::BLOCK_TYPE_MULTI_COLUMN_LIST => $common + [
                'title' => ['nullable', 'string', 'max:255'],
                'columns' => ['required', 'array', 'min:1', 'max:4'],
                'columns.*.heading' => ['nullable', 'string', 'max:255'],
                'columns.*.items' => ['nullable', 'array'],
                'columns.*.items.*' => ['string'],
            ],

            ProposalDocumentData::BLOCK_TYPE_STAGE_GRID => $common + [
                'columns' => ['nullable', 'integer', Rule::in([1, 2])],
                'stages' => ['required', 'array', 'min:1'],
                'stages.*.label' => ['required', 'string', 'max:255'],
                'stages.*.duration' => ['nullable', 'string', 'max:255'],
                'stages.*.items' => ['nullable', 'array'],
                'stages.*.items.*' => ['string'],
                'stages.*.note' => ['nullable', 'string'],
            ],

            ProposalDocumentData::BLOCK_TYPE_COMMENT_LINES => $common + [
                'label' => ['nullable', 'string', 'max:255'],
                'line_count' => ['nullable', 'integer', 'min:1', 'max:12'],
            ],

            ProposalDocumentData::BLOCK_TYPE_SIGNATURE => $common + [
                'intro' => ['nullable', 'string'],
                'date_label' => ['nullable', 'string', 'max:255'],
                'signers' => ['required', 'array', 'min:1'],
                'signers.*.role' => ['nullable', 'string', 'max:255'],
                'signers.*.name' => ['required', 'string', 'max:255'],
                'signers.*.subtitle' => ['nullable', 'string', 'max:255'],
                'signers.*.phone' => ['nullable', 'string', 'max:255'],
            ],

            default => $common + [
                'number' => ['nullable', 'string', 'max:20'],
                'title' => ['nullable', 'string', 'max:255'],
                'subtitle' => ['nullable', 'string', 'max:255'],
                'body' => ['nullable', 'string'],
                'column' => ['nullable', 'string', Rule::in(['left', 'right', 'full'])],
            ],
        };
    }
}
