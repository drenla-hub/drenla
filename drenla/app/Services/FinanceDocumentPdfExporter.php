<?php

namespace App\Services;

use App\Data\ExportedFinanceDocumentPdf;
use App\Models\FinanceDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class FinanceDocumentPdfExporter
{
    public function export(FinanceDocument $document): ExportedFinanceDocumentPdf
    {
        $directory = storage_path('app/private/finance');

        // Use a unique chrome profile per export run — avoids lock-file contention
        // if a previous Chrome process crashed without cleaning up.
        $chromeUserData = storage_path('app/private/finance/chrome-'.Str::random(8));

        File::ensureDirectoryExists($directory);
        File::ensureDirectoryExists($chromeUserData);

        $slug = Str::slug($document->reference_number ?: $document->type.'-'.$document->id);
        $pdfPath = $directory.'/'.$slug.'-'.now()->format('YmdHis').'.pdf';

        // Render straight to an HTML file and point Chrome at file:// — see
        // ProposalPdfExporter's docblock for the full reasoning (the print
        // template is fully self-contained, and an HTTP round-trip to this same
        // app deadlocks under `php artisan serve`'s single-threaded dev server).
        $htmlPath = $directory.'/'.$slug.'-'.now()->format('YmdHis').'-'.Str::random(6).'.html';
        File::put($htmlPath, View::make('admin.finance.print', [
            'document' => $document->loadMissing(['client', 'items', 'transactions']),
            'previewMode' => false,
        ])->render());

        $process = new Process([
            config('proposals.chrome_path'),
            '--headless=new',
            '--disable-gpu',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--no-first-run',
            '--disable-extensions',
            '--disable-background-networking',
            '--disable-sync',
            '--mute-audio',
            '--hide-scrollbars',
            '--virtual-time-budget=8000',
            '--print-to-pdf-no-header',
            '--user-data-dir='.$chromeUserData,
            '--print-to-pdf='.$pdfPath,
            'file://'.$htmlPath,
        ]);

        $process->setTimeout(120);

        try {
            $process->run();
        } finally {
            // Always clean up, even if run() throws — see ProposalPdfExporter.
            File::deleteDirectory($chromeUserData);
            File::delete($htmlPath);
        }

        if (! $process->isSuccessful() || ! File::exists($pdfPath)) {
            throw new \RuntimeException(
                'Finance document PDF export failed.'
                .($process->getErrorOutput() ? ' Chrome said: '.trim($process->getErrorOutput()) : '')
            );
        }

        return new ExportedFinanceDocumentPdf(
            path: $pdfPath,
            filename: ($document->reference_number ?: Str::slug($document->type.'-'.$document->id)).'.pdf',
        );
    }
}
