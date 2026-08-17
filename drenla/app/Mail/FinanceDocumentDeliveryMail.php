<?php

namespace App\Mail;

use App\Data\ExportedFinanceDocumentPdf;
use App\Models\FinanceDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FinanceDocumentDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly FinanceDocument $document,
        public readonly string $portalUrl,
        public readonly ExportedFinanceDocumentPdf $pdf,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ucfirst($this->document->type).' — '.$this->document->reference_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.finance-document-delivery',
            with: [
                'document' => $this->document,
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
