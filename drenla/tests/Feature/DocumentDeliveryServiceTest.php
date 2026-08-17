<?php

use App\Data\ExportedFinanceDocumentPdf;
use App\Data\ExportedProposalPdf;
use App\Mail\FinanceDocumentDeliveryMail;
use App\Mail\ProposalDeliveryMail;
use App\Models\Client;
use App\Models\FinanceDocument;
use App\Models\Proposal;
use App\Services\DocumentDeliveryService;
use App\Services\FinanceDocumentPdfExporter;
use App\Services\ProposalPdfExporter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

function fakeProposalPdf(): ExportedProposalPdf
{
    $path = tempnam(sys_get_temp_dir(), 'proposal-pdf-');
    File::put($path, '%PDF-1.4 test');

    return new ExportedProposalPdf($path, 'proposal.pdf');
}

function fakeFinancePdf(): ExportedFinanceDocumentPdf
{
    $path = tempnam(sys_get_temp_dir(), 'finance-pdf-');
    File::put($path, '%PDF-1.4 test');

    return new ExportedFinanceDocumentPdf($path, 'finance.pdf');
}

it('refuses to send a proposal that is not client-visible', function () {
    Mail::fake();

    $client = Client::create([
        'name' => 'Amara Wanjiru', 'email' => 'amara@example.com', 'status' => 'active',
        'portal_access_enabled' => true, 'portal_access_token' => 'tok-amara',
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id, 'title' => 'Draft Proposal', 'is_client_visible' => false,
    ]);

    (new DocumentDeliveryService(app(ProposalPdfExporter::class), app(FinanceDocumentPdfExporter::class)))
        ->sendProposal($proposal);
})->throws(RuntimeException::class, 'Only a client-visible proposal can be sent to a client.');

it('refuses to send to a client without portal access enabled', function () {
    Mail::fake();

    $client = Client::create([
        'name' => 'Brian Otieno', 'email' => 'brian@example.com', 'status' => 'active',
        'portal_access_enabled' => false,
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id, 'title' => 'Ready Proposal', 'is_client_visible' => true,
    ]);

    (new DocumentDeliveryService(app(ProposalPdfExporter::class), app(FinanceDocumentPdfExporter::class)))
        ->sendProposal($proposal);
})->throws(RuntimeException::class, 'Enable portal access for this client before sending them a document.');

it('emails a proposal pdf with a working portal deep link', function () {
    Mail::fake();

    $client = Client::create([
        'name' => 'Carol Njeri', 'email' => 'carol@example.com', 'status' => 'active',
        'portal_access_enabled' => true, 'portal_access_token' => 'tok-carol',
    ]);

    $proposal = Proposal::create([
        'client_id' => $client->id, 'title' => 'Coastal Villa Proposal', 'is_client_visible' => true,
    ]);

    $this->mock(ProposalPdfExporter::class, function ($mock) {
        $mock->shouldReceive('export')->once()->andReturn(fakeProposalPdf());
    });

    app(DocumentDeliveryService::class)->sendProposal($proposal);

    Mail::assertSent(ProposalDeliveryMail::class, function (ProposalDeliveryMail $mail) use ($client, $proposal) {
        return $mail->hasTo($client->email)
            && $mail->proposal->is($proposal)
            && str_contains($mail->portalUrl, 'tok-carol')
            && str_contains($mail->portalUrl, urlencode('/portal/proposals/'.$proposal->id));
    });
});

it('emails a finance document pdf with a working portal deep link', function () {
    Mail::fake();

    $client = Client::create([
        'name' => 'Derek Mwangi', 'email' => 'derek@example.com', 'status' => 'active',
        'portal_access_enabled' => true, 'portal_access_token' => 'tok-derek',
    ]);

    $document = FinanceDocument::create([
        'client_id' => $client->id, 'type' => 'invoice', 'reference_number' => 'INV-DELIVERY-001',
        'issue_date' => now(),
    ]);

    $this->mock(FinanceDocumentPdfExporter::class, function ($mock) {
        $mock->shouldReceive('export')->once()->andReturn(fakeFinancePdf());
    });

    app(DocumentDeliveryService::class)->sendFinanceDocument($document);

    Mail::assertSent(FinanceDocumentDeliveryMail::class, function (FinanceDocumentDeliveryMail $mail) use ($client, $document) {
        return $mail->hasTo($client->email)
            && $mail->document->is($document)
            && str_contains($mail->portalUrl, 'tok-derek');
    });
});

it('refuses to send when the client has no email address', function () {
    Mail::fake();

    $client = Client::create([
        'name' => 'No Email Client', 'status' => 'active',
        'portal_access_enabled' => true, 'portal_access_token' => 'tok-noemail',
    ]);

    $document = FinanceDocument::create([
        'client_id' => $client->id, 'type' => 'invoice', 'reference_number' => 'INV-DELIVERY-002',
        'issue_date' => now(),
    ]);

    (new DocumentDeliveryService(app(ProposalPdfExporter::class), app(FinanceDocumentPdfExporter::class)))
        ->sendFinanceDocument($document);
})->throws(RuntimeException::class, 'The client has no email address on file.');
