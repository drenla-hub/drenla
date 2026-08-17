<?php

namespace App\Mail;

use App\Data\ExportedProposalPdf;
use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Proposal $proposal,
        public readonly string $portalUrl,
        public readonly ExportedProposalPdf $pdf,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->proposal->title.' — '.$this->proposal->reference_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proposal-delivery',
            with: [
                'proposal' => $this->proposal,
                'portalUrl' => $this->portalUrl,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdf->path)->as($this->pdf->filename)->withMime('application/pdf'),
        ];
    }
}
