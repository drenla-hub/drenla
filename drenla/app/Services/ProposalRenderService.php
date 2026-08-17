<?php

namespace App\Services;

use App\Data\ProposalRenderData;
use App\Models\Proposal;
use App\Models\SiteSetting;
use App\Support\ProposalDocumentData;

/**
 * Composes the full renderable document for a Proposal: its own brief/scope
 * pages (from `document_data`) plus, where applicable, a quotation page pulled
 * from the linked `FinanceDocument` and an acceptance page if the proposal has
 * opted into one. This is the single place that decision lives — see
 * collaboration-notes.md "quotations/statements stay under FinanceDocument"
 * (2026-07-02, CO) for why quotation data isn't duplicated into document_data.
 *
 * Templates should render from `pages`, not reach into `Proposal` relations or
 * raw `document_data` directly — that's the whole point of the contract.
 */
class ProposalRenderService
{
    public function __construct(private readonly FinanceAgingService $aging = new FinanceAgingService) {}

    public function build(Proposal $proposal): ProposalRenderData
    {
        $document = ProposalDocumentData::normalize($proposal->document_data, $proposal);

        $pages = [
            $this->coverPage($proposal, $document),
            ...$this->sectionPages($document),
        ];

        $quotation = $proposal->quotation;
        $totals = null;

        if ($quotation) {
            $pages[] = $this->quotationPage($quotation);
            $totals = $this->totalsFrom($quotation);
        }

        $signers = [];

        if ($document['acceptance']['enabled']) {
            $pages[] = $this->acceptancePage($document['acceptance']);
            $signers = $document['acceptance']['signers'];
        }

        return new ProposalRenderData(
            document: $document,
            metadata: $this->metadata($proposal, $document),
            pages: $pages,
            totals: $totals,
            footer: $this->footer(),
            signers: $signers,
        );
    }

    private function metadata(Proposal $proposal, array $document): array
    {
        return [
            'document_label' => $document['header']['document_label'],
            'client_name' => $document['header']['client_name'],
            'scope' => $document['header']['scope'],
            'reference_number' => $proposal->reference_number,
            'date' => optional($proposal->issue_date)->format('d/m/Y'),
            'title' => $document['hero']['title'],
            'subtitle' => $document['hero']['subtitle'],
            'template_key' => $proposal->template_key,
            'variant' => ProposalDocumentData::templateOptions()[$proposal->template_key]['label'] ?? $proposal->template_key,
        ];
    }

    private function coverPage(Proposal $proposal, array $document): array
    {
        return [
            'kind' => 'cover',
            'hero' => $document['hero'],
            'intro' => $document['intro'],
            'appearance' => $document['appearance'],
            'blocks' => [],
        ];
    }

    /** @return array<int, array> */
    private function sectionPages(array $document): array
    {
        return collect($document['sections'])
            ->map(fn (array $section) => [
                'kind' => 'section',
                'label' => $section['label'],
                'page_title' => $section['page_title'],
                'layout' => $section['layout'],
                'blocks' => $section['blocks'],
            ])
            ->all();
    }

    private function quotationPage($quotation): array
    {
        return [
            'kind' => 'quotation',
            'reference_number' => $quotation->reference_number,
            'issue_date' => optional($quotation->issue_date)->format('d/m/Y'),
            'currency' => $quotation->currency,
            'items' => $quotation->items->map(fn ($item) => [
                'title' => $item->title,
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'total' => (string) $item->total,
            ])->all(),
            'subtotal' => (string) $quotation->subtotal,
            'tax_amount' => (string) $quotation->tax_amount,
            'total_amount' => (string) $quotation->total_amount,
            'payment_terms' => $quotation->payment_terms,
            'payment_info' => $quotation->payment_info,
            'blocks' => [],
        ];
    }

    private function acceptancePage(array $acceptance): array
    {
        return [
            'kind' => 'acceptance',
            'intro_text' => $acceptance['intro_text'],
            'date_label' => $acceptance['date_label'],
            'signers' => $acceptance['signers'],
            'blocks' => [],
        ];
    }

    private function totalsFrom($quotation): array
    {
        return [
            'currency' => $quotation->currency,
            'subtotal' => (string) $quotation->subtotal,
            'tax_amount' => (string) $quotation->tax_amount,
            'total_amount' => (string) $quotation->total_amount,
            'amount_paid' => (string) $quotation->amount_paid,
            'balance' => $quotation->balance,
            // Empty when no transaction rows exist yet (a quotation with no ledger
            // history has nothing to age) — not fabricated, genuinely zero buckets.
            'aging' => $this->aging->agingSummary($quotation->loadMissing('transactions')),
        ];
    }

    private function footer(): array
    {
        return [
            'email' => SiteSetting::getValue('contact_email', 'hello@drenla.com'),
            'phone' => SiteSetting::getValue('primary_phone', ''),
            'location' => SiteSetting::getValue('office_location', 'Nairobi, Kenya'),
            'address' => SiteSetting::getValue('office_address', ''),
        ];
    }
}
