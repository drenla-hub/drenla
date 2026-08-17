<?php

namespace App\Http\Controllers\Admin\Concerns;

/**
 * The admin form authors the structured block types (bullet_list, multi_column_list,
 * stage_grid, signature_block) through line-based mini-syntax textareas rather than
 * fully dynamic add/remove-row JS for every nested array — one items-per-line
 * textarea instead of a row-managed items[] list, one "Heading: a, b, c" line per
 * column instead of a nested column editor, etc. This trait converts that raw
 * request shape into the structured arrays ProposalDocumentData/ProposalDocumentValidator
 * expect, before either ever sees the request. Kept out of ProposalDocumentData
 * itself (CO's file) since this is purely an admin-form-input concern, not part of
 * the document schema — a different form (or a future JSON-driven builder) could
 * feed the same schema without this parsing step at all.
 */
trait ParsesProposalBlockInput
{
    /**
     * @param  array<string, mixed>  $document  raw `document` request input
     * @return array<string, mixed>
     */
    protected function parseProposalBlockInput(array $document): array
    {
        if (! isset($document['sections']) || ! is_array($document['sections'])) {
            return $document;
        }

        foreach ($document['sections'] as $si => $section) {
            if (! isset($section['blocks']) || ! is_array($section['blocks'])) {
                continue;
            }

            foreach ($section['blocks'] as $bi => $block) {
                if (! is_array($block)) {
                    continue;
                }

                $document['sections'][$si]['blocks'][$bi] = $this->parseBlock($block);
            }
        }

        if (isset($document['acceptance']) && is_array($document['acceptance'])) {
            $document['acceptance']['signers'] = $this->parseSigners($document['acceptance']['signers_text'] ?? '');
        }

        return $document;
    }

    /**
     * Field names below are prefixed per type (`bl_*`, `mc_*`, `sg_*`, `cl_*`,
     * `sb_*`) even though only one type's field group is visible at a time in the
     * form — every group's inputs stay in the DOM (just hidden via CSS) so a
     * generic textarea named e.g. "intro" shared by two types would silently
     * collide on submission (last one in DOM order wins for a repeated form field
     * name). Prefixing keeps every submitted key unique regardless of which
     * group is visible. `title`/`column` are the exception: they're genuinely
     * the same field for every type that uses them (narrative/bullet_list/
     * multi_column_list all mean "this block's heading"), so those stay shared.
     */
    private function parseBlock(array $block): array
    {
        return match ($block['type'] ?? 'narrative') {
            'bullet_list' => [
                'type' => 'bullet_list',
                'title' => $block['title'] ?? '',
                'intro' => $block['bl_intro'] ?? '',
                'bullet_style' => $block['bl_bullet_style'] ?? 'square',
                'items' => $this->linesToList($block['bl_items_text'] ?? ''),
                'column' => $block['column'] ?? 'full',
            ],

            'multi_column_list' => [
                'type' => 'multi_column_list',
                'title' => $block['title'] ?? '',
                'columns' => $this->parseColumns($block['mc_columns_text'] ?? ''),
            ],

            'stage_grid' => [
                'type' => 'stage_grid',
                'columns' => (int) ($block['sg_columns'] ?? 2),
                'stages' => $this->parseStages($block['sg_stages_text'] ?? ''),
            ],

            'comment_lines' => [
                'type' => 'comment_lines',
                'label' => $block['cl_label'] ?? '',
                'line_count' => (int) ($block['cl_line_count'] ?? 4),
            ],

            'signature_block' => [
                'type' => 'signature_block',
                'intro' => $block['sb_intro'] ?? '',
                'date_label' => $block['sb_date_label'] ?? '',
                'signers' => $this->parseSigners($block['sb_signers_text'] ?? ''),
            ],

            default => $block,
        };
    }

    /** One item per non-blank line. */
    private function linesToList(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();
    }

    /**
     * "Heading: item, item, item" per line — the same convention the renderer's
     * legacy free-text column-grid parser already understands, reused here so
     * staff only have to learn one authoring pattern.
     */
    private function parseColumns(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->map(function (string $line) {
                if (! preg_match('/^(.+?):\s*(.*)$/', $line, $m)) {
                    return null;
                }

                return [
                    'heading' => trim($m[1]),
                    'items' => collect(explode(',', $m[2]))
                        ->map(fn ($item) => trim($item))
                        ->filter(fn ($item) => $item !== '')
                        ->values()
                        ->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Blank-line-separated stage blocks. First line of each block is
     * "Label | Duration" (duration optional); remaining lines are items, an
     * optional leading "- " is stripped if present.
     */
    private function parseStages(string $text): array
    {
        $blocks = preg_split('/\r\n\r\n|\r\r|\n\n+/', trim($text));

        return collect($blocks)
            ->map(fn ($block) => trim($block))
            ->filter(fn ($block) => $block !== '')
            ->map(function (string $block) {
                $lines = collect(preg_split('/\r\n|\r|\n/', $block))
                    ->map(fn ($line) => trim($line))
                    ->filter(fn ($line) => $line !== '')
                    ->values();

                if ($lines->isEmpty()) {
                    return null;
                }

                $head = explode('|', $lines->first(), 2);
                $label = trim($head[0] ?? '');
                $duration = trim($head[1] ?? '');

                $items = $lines->slice(1)
                    ->map(fn ($line) => preg_replace('/^-\s*/', '', $line))
                    ->values()
                    ->all();

                return [
                    'label' => $label,
                    'duration' => $duration,
                    'items' => $items,
                    'note' => '',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /** "Role | Name | Subtitle | Phone" per line — trailing parts are optional. */
    private function parseSigners(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->map(function (string $line) {
                $parts = array_pad(explode('|', $line, 4), 4, '');

                return [
                    'role' => trim($parts[0]),
                    'name' => trim($parts[1]),
                    'subtitle' => trim($parts[2]),
                    'phone' => trim($parts[3]),
                ];
            })
            ->values()
            ->all();
    }
}
