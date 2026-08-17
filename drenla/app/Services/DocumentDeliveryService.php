<?php

namespace App\Services;

use App\Mail\FinanceDocumentDeliveryMail;
use App\Mail\ProposalDeliveryMail;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Proposal;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Emails a client a PDF copy of a proposal or finance document, with a deep link
 * into their portal. Deliberately does NOT toggle portal_access_enabled itself —
 * that stays a staff decision made explicitly on the client screen (CL's
 * portal-access-issuance UI) — sending a document requires access to already be
 * on, so the two concerns don't get tangled.
 */
class DocumentDeliveryService
{
    public function __construct(
        private readonly ProposalPdfExporter $proposalExporter,
        private readonly FinanceDocumentPdfExporter $financeExporter,
    ) {}

    public function sendProposal(Proposal $proposal): void
    {
        if (! $proposal->is_client_visible) {
            throw new RuntimeException('Only a client-visible proposal can be sent to a client.');
        }

        $client = $this->requireDeliverableClient($proposal->client);

        $pdf = $this->proposalExporter->export($proposal);

        $portalUrl = $this->portalUrl($client, route('portal.proposals.show', $proposal, absolute: false));

        Mail::to($client->email)->send(new ProposalDeliveryMail($proposal, $portalUrl, $pdf));
    }

    public function sendFinanceDocument(FinanceDocument $document): void
    {
        $client = $this->requireDeliverableClient($document->client);

        $pdf = $this->financeExporter->export($document);

        $portalUrl = $this->portalUrl($client, route('portal.finance.show', $document, absolute: false));

        Mail::to($client->email)->send(new FinanceDocumentDeliveryMail($document, $portalUrl, $pdf));
    }

    /**
     * @throws RuntimeException if the client can't actually be reached or hasn't been
     *                          granted portal access yet
     */
    private function requireDeliverableClient(?Client $client): Client
    {
        if (! $client) {
            throw new RuntimeException('This document has no client to send it to.');
        }

        if (! $client->email) {
            throw new RuntimeException('The client has no email address on file.');
        }

        if (! $client->portal_access_enabled) {
            throw new RuntimeException('Enable portal access for this client before sending them a document.');
        }

        if (! $client->portal_access_token) {
            $client->regeneratePortalToken();
        }

        return $client;
    }

    private function portalUrl(Client $client, string $documentPath): string
    {
        return route('portal.access', $client->portal_access_token).'?redirect='.urlencode($documentPath);
    }
}
