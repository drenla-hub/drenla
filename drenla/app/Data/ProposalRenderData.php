<?php

namespace App\Data;

/**
 * The stable, renderer-facing shape `ProposalRenderService` produces. Templates
 * (CL's) should render from `pages` — an ordered list, each self-describing its
 * `kind` — rather than reaching back into raw `document_data` or `Proposal`
 * relations directly. `document` is kept for backward compatibility with the
 * existing print template, which already consumes the normalized document_data
 * array as-is; new templates should prefer `pages`.
 */
final class ProposalRenderData
{
    /**
     * @param  array  $document  Full normalized document_data (back-compat with the existing template)
     * @param  array  $metadata  document_label, client_name, scope, reference_number, date, title, subtitle, variant, template_key
     * @param  array  $pages  Ordered list of page arrays, each ['kind' => 'cover'|'section'|'quotation'|'acceptance', ...]
     * @param  array|null  $totals  Quotation totals (subtotal/tax_amount/total_amount/balance/currency/payment_terms/payment_info), null if no quotation linked
     * @param  array  $footer  email/phone/location/address, sourced from SiteSetting
     * @param  array  $signers  Flattened signer list — acceptance signers if enabled, else empty
     */
    public function __construct(
        public readonly array $document,
        public readonly array $metadata,
        public readonly array $pages,
        public readonly ?array $totals,
        public readonly array $footer,
        public readonly array $signers,
    ) {}

    /** @return array<int, array> every block, from every page, in document order — flattened for consumers that don't need page grouping */
    public function blocks(): array
    {
        return collect($this->pages)
            ->flatMap(fn (array $page) => $page['blocks'] ?? [])
            ->values()
            ->all();
    }

    /** @return array<int, array> blocks matching a given ProposalDocumentData::BLOCK_TYPE_* value */
    public function blocksOfType(string $type): array
    {
        return collect($this->blocks())
            ->filter(fn (array $block) => ($block['type'] ?? null) === $type)
            ->values()
            ->all();
    }

    public function hasQuotation(): bool
    {
        return $this->totals !== null;
    }

    public function toArray(): array
    {
        return [
            'document' => $this->document,
            'metadata' => $this->metadata,
            'pages' => $this->pages,
            'totals' => $this->totals,
            'footer' => $this->footer,
            'signers' => $this->signers,
        ];
    }
}
